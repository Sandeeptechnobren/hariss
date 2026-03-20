<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\RouteVisit;
use App\Models\RouteVisitHeader;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Error;
use App\Models\Salesman;
use App\Helpers\DataAccessHelper;

class RouteVisitService
{
    public function getAll(int $perPage = 50, array $filters = [])
    {
        try {
            $user = auth()->user();
            $query = RouteVisit::query()->latest();
            $query = DataAccessHelper::filterRouteVisit($query, $user);
            if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
                $query->whereBetween('from_date', [
                    $filters['from_date'],
                    $filters['to_date']
                ]);
            } elseif (!empty($filters['from_date'])) {
                $query->where('from_date', '>=', $filters['from_date']);
            } elseif (!empty($filters['to_date'])) {
                $query->where('to_date', '<=', $filters['to_date']);
            }

            if (isset($filters['status'])) {
                $statuses = $filters['status'];

                if (is_string($statuses)) {
                    $statuses = explode(',', $statuses);
                }
                $statuses = array_map('intval', (array) $statuses);

                $query->whereIn('status', $statuses);
            }

            foreach ($filters as $field => $value) {
                if (!empty($value) && !in_array($field, ['from_date', 'to_date', 'status'])) {
                    if ($field === 'customer') {
                        $ids = is_array($value) ? $value : explode(',', $value);
                        $query->whereIn($field, $ids);
                    } elseif ($field === 'osa_code') {
                        $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                    } else {
                        $query->where($field, $value);
                    }
                }
            }

            return $query->paginate($perPage);
        } catch (Throwable $e) {
            Log::error("Failed to fetch route visits", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'filters' => $filters,
            ]);

            throw new \Exception("Something went wrong while fetching route visits, please try again.");
        }
    }


    // public function generateHeaderCode(): string
    // {
    //     do {
    //         $last = RouteVisit::withTrashed()->latest('id')->first();
    //         $nextNumber = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
    //         $osa_code = 'RVH' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    //     } while (RouteVisit::withTrashed()->where('osa_code', $osa_code)->exists());

    //     return $osa_code;
    // }
    // public function generateDetailCode(): string
    // {
    //     do {
    //         $last = RouteVisit::withTrashed()->latest('id')->first();
    //         $nextNumber = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
    //         $osa_code = 'RVD' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    //     } while (RouteVisit::withTrashed()->where('osa_code', $osa_code)->exists());

    //     return $osa_code;
    // }

    public function generateHeaderCode(): string
    {
        return DB::transaction(function () {

            DB::statement('LOCK TABLE route_visit_headers IN EXCLUSIVE MODE');

            $lastCode = RouteVisitHeader::withTrashed()
                ->where('osa_code', 'like', 'RVH%')
                ->orderByRaw("CAST(SUBSTRING(osa_code, 4) AS INTEGER) DESC")
                ->value('osa_code');

            $next = $lastCode
                ? ((int) preg_replace('/\D/', '', $lastCode)) + 1
                : 1;

            return 'RVH' . str_pad($next, 3, '0', STR_PAD_LEFT);
        });
    }

    public function generateDetailCode(): string
    {
        return DB::transaction(function () {

            DB::statement('LOCK TABLE route_visit IN EXCLUSIVE MODE');

            $lastCode = RouteVisit::withTrashed()
                ->where('osa_code', 'like', 'RVD%')
                ->orderByRaw("CAST(SUBSTRING(osa_code, 4) AS INTEGER) DESC")
                ->value('osa_code');

            $next = $lastCode
                ? ((int) preg_replace('/\D/', '', $lastCode)) + 1
                : 1;

            return 'RVD' . str_pad($next, 3, '0', STR_PAD_LEFT);
        });
    }

    public function create(array $data)
    {
        DB::beginTransaction();

        try {
            $createdRecords = [];

            $customerType     = $data['customer_type'] ?? null;
            $globalDays       = $data['global_days'] ?? null;
            $merchandiserId   = $data['merchandiser_id'] ?? null;

            if (!isset($data['customers']) || !is_array($data['customers'])) {
                throw new \Exception("customers array is required");
            }

            /**
             * 🔥 ROUTE BASED DELETE (ONLY ONCE)
             * Route id first customer se li ja rahi hai
             */
            $routeId = $data['customers'][0]['route'] ?? null;

            if ($routeId) {

                // Get existing header ids for this route
                $existingHeaderIds = RouteVisit::where('route', $routeId)
                    ->pluck('header_id')
                    ->filter()
                    ->unique()
                    ->toArray();

                if (!empty($existingHeaderIds)) {

                    // Delete route_visit details
                    RouteVisit::where('route', $routeId)->delete();

                    // Delete related headers
                    RouteVisitHeader::whereIn('id', $existingHeaderIds)->delete();
                }
            }

            /**
             * 🔹 CREATE NEW HEADER
             */
            $header = RouteVisitHeader::create([
                'uuid'         => Str::uuid()->toString(),
                'osa_code'     => $this->generateHeaderCode(),
                'created_user' => Auth::id(),
            ]);

            foreach ($data['customers'] as $customerData) {

                if (empty($customerData['customer_id'])) {
                    throw new \Exception("customer_id is required for each record");
                }

                $exists = RouteVisit::where('customer_id', $customerData['customer_id'])
                    ->where('header_id', $header->id)
                    ->exists();

                if ($exists) {
                    throw new \Exception(
                        "Customer already exists in this route visit batch"
                    );
                }

                $days = !empty($customerData['days'])
                    ? $customerData['days']
                    : $globalDays;

                $recordData = [
                    'header_id'       => $header->id,
                    'uuid'            => Str::uuid()->toString(),
                    'osa_code'        => $this->generateDetailCode(),
                    'customer_type'   => $customerType,
                    'customer_id'     => $customerData['customer_id'],
                    'company_id'      => $customerData['company_id'] ?? null,
                    'region'          => $customerData['region'] ?? null,
                    'area'            => $customerData['area'] ?? null,
                    'warehouse'       => $customerData['warehouse'] ?? null,
                    'route'           => $customerData['route'] ?? null,
                    'days'            => $days,
                    'from_date'       => $customerData['from_date'] ?? null,
                    'to_date'         => $customerData['to_date'] ?? null,
                    'status'          => $customerData['status'] ?? 1,
                    'merchandiser_id' => $merchandiserId,
                    'created_user'    => Auth::id(),
                ];

                $recordData['flag'] = match ($customerType) {
                    1 => 'agent_customer',
                    2 => 'merchandisor',
                    default => 'unknown',
                };

                $createdRecords[] = RouteVisit::create($recordData);
            }

            DB::commit();

            return [
                'header'  => $header,
                'details' => $createdRecords
            ];
        } catch (Throwable $e) {

            DB::rollBack();

            Log::error("RouteVisit creation failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data'  => $data,
                'user'  => Auth::id(),
            ]);

            throw new \Exception($e->getMessage());
        }
    }


    public function findByUuid(string $uuid)
    {
        if (!Str::isUuid($uuid)) {
            return null;
        }

        $header = RouteVisitHeader::query()
            ->where('uuid', $uuid)
            ->first();
        if (! $header) {
            return null;
        }

        $details = RouteVisit::query()
            ->where('header_id', $header->id)
            ->whereNull('deleted_at')
            ->get();
        // dd($details);

        if ($details->count() === 0) {
            return null;
        }

        if ($details->count() === 1) {
            return $details->first();
        }

        return $details;
    }

    public function updateByUuid(string $uuid, array $validated)
    {
        // dd($validated);
        if (!Str::isUuid($uuid)) {
            throw new \Exception("Invalid UUID format");
        }

        $header = RouteVisitHeader::where('uuid', $uuid)->first();

        if (! $header) {
            throw new \Exception("Route Visit not found for uuid: {$uuid}");
        }

        if (empty($validated['customers']) || !is_array($validated['customers'])) {
            throw new \Exception("customers array is required");
        }

        DB::beginTransaction();

        try {
            $customerType = $validated['customer_type'] ?? null;
            $globalDays   = $validated['global_days'] ?? null;
            $merchandiserId = $validated['merchandiser_id'] ?? null;

            $affectedIds = [];

            foreach ($validated['customers'] as $customerData) {

                if (empty($customerData['customer_id'])) {
                    throw new \Exception("customer_id is required");
                }

                $routeVisit = RouteVisit::where('header_id', $header->id)
                    ->where('customer_id', $customerData['customer_id'])
                    ->whereNull('deleted_at')
                    ->first();

                if (array_key_exists('days', $customerData)) {
                    $days = $customerData['days'];
                } elseif (!empty($globalDays)) {
                    $days = $globalDays;
                } else {
                    $days = null;
                }

                $payload = [];

                $fields = [
                    'company_id',
                    'region',
                    'area',
                    'warehouse',
                    'route',
                    'from_date',
                    'to_date',
                    'status',
                ];
                // dd($customerData);
                foreach ($fields as $field) {
                    if (array_key_exists($field, $customerData)) {
                        $payload[$field] = is_array($customerData[$field])
                            ? implode(',', $customerData[$field])
                            : $customerData[$field];
                    }
                }
                if (!empty($merchandiserId)) {
                    $payload['merchandiser_id'] = $merchandiserId;
                }
                if ($days !== null) {
                    $payload['days'] = $days;
                }

                if ($customerType !== null) {
                    $payload['customer_type'] = $customerType;
                }

                $payload['updated_user'] = auth()->id();
                if ($routeVisit) {
                    $routeVisit->update($payload);
                    $affectedIds[] = $routeVisit->id;
                } else {
                    $newPayload = array_merge($payload, [
                        'header_id'    => $header->id,
                        'customer_id'  => $customerData['customer_id'],
                        'uuid'         => Str::uuid()->toString(),
                        'osa_code'     => $this->generateDetailCode(),
                        'created_user' => auth()->id(),
                    ]);

                    if ($customerType == 1) {
                        $newPayload['flag']   = 'agent_customer';
                        $newPayload['status'] = $newPayload['status'] ?? 1;
                    } elseif ($customerType == 2) {
                        $newPayload['flag']   = 'merchandisor';
                        $newPayload['status'] = $newPayload['status'] ?? 0;
                    }

                    $new = RouteVisit::create($newPayload);
                    $affectedIds[] = $new->id;
                }
            }

            DB::commit();

            $records = RouteVisit::with([
                'header',
                'agentCustomer',
                'companyCustomer',
                'merchandiser',
            ])
                ->whereIn('id', $affectedIds)
                ->get();

            return $records->count() === 1
                ? $records->first()
                : $records;
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("RouteVisit update failed", [
                'error' => $e->getMessage(),
                'uuid'  => $uuid,
                'data'  => $validated,
            ]);

            throw new \Exception(
                "Something went wrong while updating Route Visits.",
                0,
                $e
            );
        }
    }


    public function update(string $customer_id = null, array $data)
    {
        DB::beginTransaction();

        try {
            $updatedRecords = [];

            $customerType = $data['customer_type'] ?? null;
            $globalDays   = $data['global_days'] ?? null;

            /**
             * 🔹 CASE 1: BULK UPSERT (customers array)
             */
            if (!empty($data['customers']) && is_array($data['customers'])) {

                foreach ($data['customers'] as $customerData) {

                    if (empty($customerData['customer_id'])) {
                        throw new \Exception("customer_id is required for each record");
                    }

                    /**
                     * 🔹 UPSERT with customer_type constraint
                     */
                    $routeVisit = RouteVisit::where('customer_id', $customerData['customer_id'])
                        ->when($customerType, fn($q) => $q->where('customer_type', $customerType))
                        ->first();

                    if (! $routeVisit) {
                        $routeVisit = new RouteVisit();
                        $routeVisit->customer_id   = $customerData['customer_id'];
                        $routeVisit->customer_type = $customerType;

                        // 🔹 Generate osa_code for new record
                        $lastId = RouteVisit::max('id') ?? 0;
                        $routeVisit->osa_code = 'RV' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
                    }

                    /**
                     * 🔹 Prepare update data
                     */
                    $updateData = $customerData;

                    // 🔹 DAYS PRIORITY
                    if (!empty($customerData['days'])) {
                        $updateData['days'] = is_array($customerData['days'])
                            ? implode(',', $customerData['days'])
                            : $customerData['days'];
                    } elseif (!empty($globalDays)) {
                        $updateData['days'] = $globalDays;
                    } else {
                        unset($updateData['days']);
                    }

                    // 🔹 FLAG
                    $updateData['flag'] = match ($customerType) {
                        1 => 'agent_customer',
                        2 => 'merchandisor',
                        default => 'unknown',
                    };

                    // 🔹 Default status for new record
                    if (! $routeVisit->exists) {
                        $updateData['status'] = $updateData['status'] ?? 1;
                    }

                    $routeVisit->fill($updateData);
                    $routeVisit->save();

                    $updatedRecords[] = $routeVisit->fresh();
                }

                /**
                 * 🔹 CASE 2: SINGLE CUSTOMER UPSERT (customer_id param)
                 */
            } elseif ($customer_id) {

                $routeVisit = RouteVisit::where('customer_id', $customer_id)
                    ->when($customerType, fn($q) => $q->where('customer_type', $customerType))
                    ->first();

                if (! $routeVisit) {
                    $routeVisit = new RouteVisit();
                    $routeVisit->customer_id   = $customer_id;
                    $routeVisit->customer_type = $customerType;

                    $lastId = RouteVisit::max('id') ?? 0;
                    $routeVisit->osa_code = 'RV' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
                }

                if (!empty($data['days'])) {
                    $data['days'] = is_array($data['days'])
                        ? implode(',', $data['days'])
                        : $data['days'];
                } elseif (!empty($globalDays)) {
                    $data['days'] = $globalDays;
                } else {
                    unset($data['days']);
                }

                $routeVisit->fill($data);
                $routeVisit->save();

                $updatedRecords[] = $routeVisit->fresh();
            }

            DB::commit();
            return $updatedRecords;
        } catch (Throwable $e) {
            dd($e);
            DB::rollBack();

            Log::error("RouteVisit upsert failed", [
                'error' => $e->getMessage(),
                'data' => $data,
                'customer_id' => $customer_id,
            ]);

            throw new \Exception(
                "Something went wrong while updating Route Visits.",
                0,
                $e
            );
        }


        // public function update(string $customer_id = null, array $data)
        // {
        //     DB::beginTransaction();

        //     try {
        //         $updatedRecords = [];

        //         if (isset($data['customers']) && is_array($data['customers'])) {
        //             $customerType = $data['customer_type'] ?? null;

        //             foreach ($data['customers'] as $customerData) {
        //                 if (empty($customerData['customer_id'])) {
        //                     throw new \Exception("customer_id is required for each record in bulk update");
        //                 }

        //                 $routeVisit = RouteVisit::where('customer_id', $customerData['customer_id'])->first();

        //                 if (!$routeVisit) {
        //                     throw new \Exception("Route Visit not found for customer_id: {$customerData['customer_id']}");
        //                 }

        //                 $updateData = array_merge($customerData, [
        //                     'customer_type' => $customerType,
        //                 ]);

        //                 if (isset($updateData['days']) && is_array($updateData['days'])) {
        //                     $updateData['days'] = implode(',', $updateData['days']);
        //                 }

        //                 $updateData['flag'] = match ($customerType) {
        //                     1 => 'agent_customer',
        //                     2 => 'merchandisor',
        //                     default => 'unknown',
        //                 };
        //                 $routeVisit->fill($updateData);
        //                 $routeVisit->save();

        //                 $updatedRecords[] = $routeVisit->fresh();
        //             }
        //         } elseif ($customer_id) {
        //             $routeVisit = RouteVisit::where('customer_id', $customer_id)->first();

        //             if (!$routeVisit) {
        //                 throw new \Exception("Route Visit not found for customer_id: {$customer_id}");
        //             }

        //             if (isset($data['days']) && is_array($data['days'])) {
        //                 $data['days'] = implode(',', $data['days']);
        //             }

        //             $routeVisit->fill($data);
        //             $routeVisit->save();

        //             $updatedRecords[] = $routeVisit->fresh();
        //         }

        //         DB::commit();

        //         return $updatedRecords;
        //     } catch (Throwable $e) {
        //         DB::rollBack();

        //         Log::error("RouteVisit bulk update failed", [
        //             'error' => $e->getMessage(),
        //             'data' => $data,
        //             'customer_id' => $customer_id,
        //         ]);

        //         throw new \Exception("Something went wrong while updating Route Visits.", 0, $e);
        //     }
    }



    public function deleteByUuid(string $uuid): void
    {
        $routeVisit = $this->findByUuid($uuid);
        if (!$routeVisit) {
            throw new \Exception("Route Visit not found or invalid UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $routeVisit->update([
                'deleted_at' => now(),
            ]);
            $routeVisit->delete();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            $friendlyMessage = $e instanceof Error
                ? "Server error occurred."
                : "Something went wrong, please try again.";

            Log::error("RouteVisit delete failed", [
                'error' => $e->getMessage(),
                'uuid' => $uuid,
            ]);

            throw new \Exception($friendlyMessage, 0, $e);
        }
    }

    public function getAlll($filters = [])
    {
        $query = Salesman::select('id', 'osa_code', 'name')
            ->where('sub_type', 6); // ✅ Only subtype = 1

        // Optional filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $query->whereRaw('LOWER(name) LIKE ?', ['%' . $search . '%']);
        }

        // Optional pagination
        if (!empty($filters['per_page'])) {
            return $query->paginate($filters['per_page']);
        }

        return $query->get();
    }

    public function getByMerchandiser($merchandiserId, $filters = [])
    {
        $query = DB::table('tbl_company_customer')
            ->select('id', 'osa_code', 'business_name as name')
            ->where('merchandiser_id', $merchandiserId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where('business_name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->get();
    }

    public function export($format = 'csv')
    {
        $format = strtolower($format);
        if (!in_array($format, ['csv', 'xls'])) {
            return [
                'status' => false,
                'message' => 'Invalid format. Use csv or xls only.',
            ];
        }

        $data = DB::table('route_visit')
            ->leftJoin('salesman', 'route_visit.merchandiser_id', '=', 'salesman.id')
            ->leftJoin('route_visit_headers', 'route_visit.header_id', '=', 'route_visit_headers.id')
            ->select(

                /* ROUTE VISIT ID */
                // DB::raw('route_visit_headers.osa_code AS "Route Visit Code"'),

                /* CUSTOMER TYPE WITH LABEL */
                DB::raw("
                CASE
                    WHEN route_visit.customer_type = '1'
                    THEN 'Field Customer'
                    ELSE 'Merchandiser Customer'
                END AS \"CUSTOMER_TYPE\"
            "),

           DB::raw("(
                    SELECT STRING_AGG(route_code || ' - ' || route_name, ', ')
                    FROM tbl_route
                    WHERE id::text = ANY(string_to_array(route_visit.route::text, ','))
                ) AS \"Route\""),

                /* CUSTOMER */
                    DB::raw("COALESCE(
                (
                    SELECT STRING_AGG(osa_code || ' - ' || business_name, ', ')
                    FROM tbl_company_customer
                    WHERE id::text = ANY(string_to_array(route_visit.customer_id::text, ','))
                ),
                (
                    SELECT STRING_AGG(osa_code || ' - ' || name, ', ')
                    FROM agent_customers
                    WHERE id::text = ANY(string_to_array(route_visit.customer_id::text, ','))
                )
            ) AS \"Customer\""),
                /* COMPANY */
                // DB::raw("(SELECT STRING_AGG(company_code, ', ')
                // FROM tbl_company
                // WHERE id::text = ANY(string_to_array(route_visit.company_id::text, ',')))
                // AS \"Company Code\""),

                // DB::raw("(SELECT STRING_AGG(company_name, ', ')
                // FROM tbl_company
                // WHERE id::text = ANY(string_to_array(route_visit.company_id::text, ',')))
                // AS \"Company Name\""),

                // /* REGION */
                // DB::raw("(SELECT STRING_AGG(region_code, ', ')
                // FROM tbl_region
                // WHERE id::text = ANY(string_to_array(route_visit.region::text, ',')))
                // AS \"Region Code\""),

                // DB::raw("(SELECT STRING_AGG(region_name, ', ')
                // FROM tbl_region
                // WHERE id::text = ANY(string_to_array(route_visit.region::text, ',')))
                // AS \"Region Name\""),

                // /* AREA */
                // DB::raw("(SELECT STRING_AGG(area_code, ', ')
                // FROM tbl_areas
                // WHERE id::text = ANY(string_to_array(route_visit.area::text, ',')))
                // AS \"Area Code\""),

                // DB::raw("(SELECT STRING_AGG(area_name, ', ')
                // FROM tbl_areas
                // WHERE id::text = ANY(string_to_array(route_visit.area::text, ',')))
                // AS \"Area Name\""),

                // /* WAREHOUSE */
                // DB::raw("(SELECT STRING_AGG(warehouse_code, ', ')
                // FROM tbl_warehouse
                // WHERE id::text = ANY(string_to_array(route_visit.warehouse::text, ',')))
                // AS \"Distributor Code\""),

                // DB::raw("(SELECT STRING_AGG(warehouse_name, ', ')
                // FROM tbl_warehouse
                // WHERE id::text = ANY(string_to_array(route_visit.warehouse::text, ',')))
                // AS \"Distributor Name\""),

                // /* MERCHANDISER */
                // DB::raw('salesman.name AS "Merchandiser"'),

                /* OPTIONAL */
                DB::raw("
                    CASE
                        WHEN position('Sunday' in route_visit.days) > 0
                        THEN 'YES' ELSE 'NO'
                    END AS \"SUNDAY\"
                "),

                DB::raw("
                    CASE
                        WHEN position('Monday' in route_visit.days) > 0
                        THEN 'YES' ELSE 'NO'
                    END AS \"MONDAY\"
                "),

                DB::raw("
                    CASE
                        WHEN position('Tuesday' in route_visit.days) > 0
                        THEN 'YES' ELSE 'NO'
                    END AS \"TUESDAY\"
                "),

                DB::raw("
                    CASE
                        WHEN position('Wednesday' in route_visit.days) > 0
                        THEN 'YES' ELSE 'NO'
                    END AS \"WEDNESDAY\"
                "),

                DB::raw("
                    CASE
                        WHEN position('Thursday' in route_visit.days) > 0
                        THEN 'YES' ELSE 'NO'
                    END AS \"THURSDAY\"
                "),

                DB::raw("
                    CASE
                        WHEN position('Friday' in route_visit.days) > 0
                        THEN 'YES' ELSE 'NO'
                    END AS \"FRIDAY\"
                "),

                DB::raw("
                    CASE
                        WHEN position('Saturday' in route_visit.days) > 0
                        THEN 'YES' ELSE 'NO'
                    END AS \"SATURDAY\"
                "),

                DB::raw('route_visit.from_date AS "From Date"'),
                DB::raw('route_visit.to_date AS "To Date"'),
                DB::raw("
                    CASE
                        WHEN route_visit.status = 1
                        THEN 'Active'
                        ELSE 'Inactive'
                    END AS \"Status\"
                ")
            )
            ->orderBy('route_visit.id', 'desc')
            ->get();

        if ($data->isEmpty()) {
            return [
                'status' => false,
                'message' => 'No data found in route_visit table.',
            ];
        }

        $directory = 'app/exports';
        Storage::makeDirectory($directory);

        $fileName = 'route_visit_' . now()->format('Ymd_His') . '.' . $format;
        $filePath = storage_path("{$directory}/{$fileName}");

        $arrayData = json_decode(json_encode($data), true);

        if ($format === 'csv') {
            $this->generateCsv($filePath, $arrayData);
        } else {
            $this->generateXls($filePath, $arrayData);
        }

        $fileUrl = str_replace('/public', '', url("storage/app/public/exports/{$fileName}"));

        return [
            'status' => true,
            'message' => 'Route Visit data exported successfully',
            'file_url' => $fileUrl,
            // 'file_path' => $filePath,
        ];
    }


    private function generateCsv($filePath, array $data): void
    {
        $handle = fopen($filePath, 'w');

        // Write header row
        fputcsv($handle, array_keys($data[0]));


        foreach ($data as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
    }
    private function generateXls($filePath, array $data): void
    {
        $handle = fopen($filePath, 'w');
        fwrite($handle, implode("\t", array_keys($data[0])) . "\n");
        foreach ($data as $row) {
            fwrite($handle, implode("\t", $row) . "\n");
        }

        fclose($handle);
    }

    // public function globalSearch(?string $keyword, int $perPage = 50)
    // {
    //     $query = RouteVisit::query();

    //     // If no keyword → return full paginated list
    //     if (empty($keyword)) {
    //         return $query->paginate($perPage);
    //     }

    //     $query->where(function ($q) use ($keyword) {

    //         $searchableFields = [
    //             'osa_code',
    //             'customer_type',
    //             'merchandiser_id',
    //             'status',
    //             'company_id',
    //             'region'
    //         ];

    //         foreach ($searchableFields as $field) {
    //             $q->orWhereRaw("CAST({$field} AS TEXT) ILIKE ?", ['%' . $keyword . '%']);
    //         }

    //         $q->orWhereHas('agentCustomer', function ($sub) use ($keyword) {
    //             $sub->where('name', 'ILIKE', '%' . $keyword . '%');
    //         });

    //         $q->orWhereHas('companyCustomer', function ($sub) use ($keyword) {
    //             $sub->where('business_name', 'ILIKE', '%' . $keyword . '%')
    //                 ->orWhere('osa_code', 'ILIKE', '%' . $keyword . '%');
    //         });

    //         $q->orWhereHas('merchandiser', function ($sub) use ($keyword) {
    //             $sub->where('name', 'ILIKE', '%' . $keyword . '%')
    //                 ->orWhere('osa_code', 'ILIKE', '%' . $keyword . '%');
    //         });
    //     });

    //     return $query->paginate($perPage);
    // }


    public function globalSearch(?string $query, int $perPage = 10)
    {
        $builder = RouteVisitHeader::query()
            ->whereNull('deleted_at')
            ->with([
                'routeVisits' => function ($q) {
                    $q->whereNull('deleted_at');
                }
            ])
            ->orderBy('id', 'desc');

        /**
         * 🔍 Global search (single query param)
         */
        if (!empty($query)) {
            $builder->where(function ($h) use ($query) {

                // Header level search
                $h->where('osa_code', 'ILIKE', "%{$query}%");

                // Route visit level search
                $h->orWhereHas('routeVisits', function ($rv) use ($query) {
                    $rv->whereNull('deleted_at')
                        ->where(function ($q) use ($query) {
                            $q->where('osa_code', 'ILIKE', "%{$query}%")
                                // ->orWhere('customer_type', 'ILIKE', "%{$query}%")
                                ->orWhere('region', 'ILIKE', "%{$query}%")
                                ->orWhere('route', 'ILIKE', "%{$query}%")
                                ->orWhereRaw("from_date::text ILIKE ?", ["%{$query}%"])
                                ->orWhereRaw("to_date::text ILIKE ?", ["%{$query}%"]);

                            $customerTypeMap = [
                                'Field Customer' => '1',
                                'Merchandiser'   => '2',
                            ];
                            $normalizedQuery = strtolower(trim($query));

                            if (isset($customerTypeMap[$normalizedQuery])) {
                                $q->orWhere('customer_type', $customerTypeMap[$normalizedQuery]);
                            }
                        });
                });
            });
        }

        return $builder->paginate($perPage);
    }



    public function list(array $filters = [], int $perPage = 10)
    {
        $query = RouteVisitHeader::query()
            ->whereNull('deleted_at')
            ->with([
                'routeVisits' => function ($q) {
                    $q->whereNull('deleted_at');
                }
            ])
            ->orderBy('id', 'desc');

        // 🔹 Optional filters
        if (!empty($filters['osa_code'])) {
            $query->where('osa_code', 'ILIKE', '%' . $filters['osa_code'] . '%');
        }

        if (!empty($filters['created_user'])) {
            $query->where('created_user', $filters['created_user']);
        }

        return $query->paginate($perPage);
    }
}
