<?php

namespace App\Services\V1\Assets\Web;

use App\Models\ServiceVisit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Helpers\ApprovalHelper;
use Throwable;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ServiceVisitService
{
    // use App\Helpers\ApprovalHelper;

    public function getAll(int $perPage = 50, array $filters = [], bool $dropdown = false)
    {
        try {
            if ($dropdown) {
                return ServiceVisit::select('id', 'uuid', 'osa_code', 'ticket_type')
                    ->orderBy('created_at', 'desc')
                    ->get();
            }

            $query = ServiceVisit::with([
                'customer.getWarehouse.area.region',
                'customer.getWarehouse:id,warehouse_code,warehouse_name,area_id,region_id',
                'customer.getWarehouse.area:id,region_id',
                'customer.getWarehouse.area.region:id',
                'customer.getWarehouse:id,warehouse_code,warehouse_name',
                'natureOfCall.assignedCustomer'
            ]);
            foreach ($filters as $field => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                if (in_array($field, ['ticket_type', 'outlet_name', 'owner_name'])) {
                    $query->whereRaw(
                        "LOWER({$field}) LIKE ?",
                        ['%' . strtolower($value) . '%']
                    );
                } else {
                    $query->where($field, $value);
                }
            }

            if (empty($filters['from_date']) && empty($filters['to_date'])) {
                $query->whereBetween('created_at', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth(),
                ]);
            }

            // ✅ If user sends date filter
            if (!empty($filters['from_date'])) {
                $query->whereDate('created_at', '>=', $filters['from_date']);
            }

            if (!empty($filters['to_date'])) {
                $query->whereDate('created_at', '<=', $filters['to_date']);
            }
            $result = $query
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $result->getCollection()->transform(function ($item) {
                return ApprovalHelper::attach($item, 'Service_Visit');
            });

            return $result;
        } catch (\Exception $e) {
            throw new \Exception(
                "Failed to fetch service visits: " . $e->getMessage()
            );
        }
    }

    public function generateCode(string $prefix = 'SV'): string
    {
        do {
            $last = ServiceVisit::withTrashed()
                ->where('osa_code', 'like', $prefix . '%')
                ->latest('id')
                ->first();
            if ($last && $last->osa_code) {
                $number = (int) preg_replace('/\D/', '', $last->osa_code);
                $next   = $number + 1;
            } else {
                $next = 1;
            }
            $osa_code = $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
        } while (
            ServiceVisit::withTrashed()
            ->where('osa_code', $osa_code)
            ->exists()
        );

        return $osa_code;
    }

    // public function create(array $data): ServiceVisit
    // {
    //     return DB::transaction(function () use ($data) {
    //         if (!empty($data['osa_code'])) {
    //             $osaCode = strtoupper($data['osa_code']);
    //         } else {
    //             $prefix  = $data['prefix'] ?? 'BD';
    //             $osaCode = $this->generateCode($prefix);
    //         }
    //         $data = array_merge($data, [
    //             'uuid'         => $data['uuid'] ?? Str::uuid()->toString(),
    //             'osa_code'     => $osaCode,
    //             'created_user' => Auth::id(),
    //         ]);
    //         unset($data['prefix']);
    //         $appUrl = rtrim(config('app.url'), '/');
    //         $fileColumns = [
    //             'scan_image',
    //             'is_machine_in_working_img',
    //             'cleanliness_img',
    //             'condensor_coil_cleand_img',
    //             'gaskets_img',
    //             'light_working_img',
    //             'branding_no_img',
    //             'propper_ventilation_available_img',
    //             'leveling_positioning_img',
    //             'stock_availability_in_img',
    //             'cooler_image',
    //             'cooler_image2',
    //             'type_details_photo1',
    //             'type_details_photo2',
    //             'customer_signature',
    //         ];
    //         foreach ($fileColumns as $column) {
    //             if (
    //                 isset($data[$column]) &&
    //                 $data[$column] instanceof \Illuminate\Http\UploadedFile &&
    //                 $data[$column]->isValid()
    //             ) {
    //                 $filename = Str::random(20) . '.' . $data[$column]->getClientOriginalExtension();
    //                 $data[$column]->storeAs('service_visit', $filename, 'public');
    //                 $data[$column] = $appUrl . '/storage/app/public/service_visit/' . $filename;
    //             } else {
    //                 unset($data[$column]);
    //             }
    //         }
    //         return ServiceVisit::create($data);
    //     });
    // }

    public function create(array $data): ServiceVisit
    {
        return DB::transaction(function () use ($data) {

            if (!empty($data['osa_code'])) {
                $osaCode = strtoupper($data['osa_code']);
            } else {
                $prefix  = $data['prefix'] ?? 'BD';
                $osaCode = $this->generateCode($prefix);
            }

            $data = array_merge($data, [
                'uuid'         => $data['uuid'] ?? Str::uuid()->toString(),
                'osa_code'     => $osaCode,
                'created_user' => Auth::id(),
            ]);

            unset($data['prefix']);

            $appUrl = rtrim(config('app.url'), '/');

            $fileColumns = [
                'scan_image',
                'is_machine_in_working_img',
                'cleanliness_img',
                'condensor_coil_cleand_img',
                'gaskets_img',
                'light_working_img',
                'branding_no_img',
                'propper_ventilation_available_img',
                'leveling_positioning_img',
                'stock_availability_in_img',
                'cooler_image',
                'cooler_image2',
                'type_details_photo1',
                'type_details_photo2',
                'customer_signature',
            ];

            foreach ($fileColumns as $column) {
                if (
                    isset($data[$column]) &&
                    $data[$column] instanceof \Illuminate\Http\UploadedFile &&
                    $data[$column]->isValid()
                ) {
                    $filename = Str::random(20) . '.' . $data[$column]->getClientOriginalExtension();
                    $data[$column]->storeAs('service_visit', $filename, 'public');
                    $data[$column] = $appUrl . '/storage/app/public/service_visit/' . $filename;
                } else {
                    unset($data[$column]);
                }
            }

            $visit = ServiceVisit::create($data);
            $assignment = DB::table('htapp_workflow_assignments')
                ->where('process_type', 'Service_Visit')
                ->where('is_active', true)
                ->first();

            if ($assignment) {
                app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)
                    ->startApproval([
                        'process_type' => 'Service_Visit',
                        'process_id'   => $visit->id,
                    ]);
            }

            return $visit;
        });
    }


    /**
     * Find record by UUID
     */
    // public function findByUuid(string $uuid): ?ServiceVisit
    // {
    //     if (!Str::isUuid($uuid)) {
    //         throw new \Exception("Invalid UUID format: {$uuid}");
    //     }
    //     $serviceVisit=ServiceVisit::where('uuid',$uuid)->first();
    //     if(!$serviceVisit){
    //         return null;
    //     }

    //     return ServiceVisit::where('uuid', $uuid)->first();
    // }

    public function findByUuid(string $uuid): ?ServiceVisit
    {
        if (!Str::isUuid($uuid)) {
            throw new \Exception("Invalid UUID format: {$uuid}");
        }
        $serviceVisit = ServiceVisit::where('uuid', $uuid)->first();
        if (!$serviceVisit) {
            return null;
        }
        return ApprovalHelper::attach($serviceVisit, 'Service_Visit');
    }

    /**
     * Update record by UUID
     */
    // public function updateByUuid(string $uuid, array $data): ServiceVisit
    // {
    //     $record = $this->findByUuid($uuid);

    //     if (!$record) {
    //         throw new \Exception("Record not found or invalid UUID: {$uuid}");
    //     }

    //     DB::beginTransaction();

    //     try {

    //         $fileColumns = [
    //             'scan_image',
    //             'cooler_image',
    //             'cooler_image2',
    //             'type_details_photo1',
    //             'type_details_photo2',
    //             'customer_signature',
    //         ];

    //         foreach ($fileColumns as $col) {
    //             if (!empty($data[$col]) && $data[$col] instanceof \Illuminate\Http\UploadedFile) {

    //                 $filename = Str::random(40) . '.' . $data[$col]->getClientOriginalExtension();

    //                 $folder = "service_visit";

    //                 $data[$col]->storeAs($folder, $filename, 'public');

    //                 $appUrl = rtrim(config('app.url'), '/');
    //                 $data[$col] = $appUrl . '/storage/app/public/' . $folder . '/' . $filename;
    //             }
    //         }

    //         $data['updated_user'] = Auth::id();

    //         $record->fill($data);
    //         $record->save();

    //         DB::commit();
    //         return $record;
    //     } catch (\Throwable $e) {

    //         DB::rollBack();

    //         Log::error("Service Visit update failed", [
    //             'error'   => $e->getMessage(),
    //             'uuid'    => $uuid,
    //             'payload' => $data,
    //         ]);

    //         throw new \Exception("Something went wrong, please try again.", 0, $e);
    //     }
    // }


    public function updateByUuid(string $uuid, array $data): ServiceVisit
    {
        // $record = $this->findByUuid($uuid);
        $record =  ServiceVisit::get()
            ->where('uuid', $uuid)
            ->first();
        if (! $record) {
            throw new \Exception("Record not found or invalid UUID: {$uuid}");
        }

        return DB::transaction(function () use ($record, $data) {

            $appUrl = rtrim(config('app.url'), '/');

            $fileColumns = [
                'scan_image',
                'is_machine_in_working_img',
                'cleanliness_img',
                'condensor_coil_cleand_img',
                'gaskets_img',
                'light_working_img',
                'branding_no_img',
                'propper_ventilation_available_img',
                'leveling_positioning_img',
                'stock_availability_in_img',
                'cooler_image',
                'cooler_image2',
                'type_details_photo1',
                'type_details_photo2',
                'customer_signature',
            ];

            // 🔹 Handle file uploads exactly like create()
            foreach ($fileColumns as $column) {

                if (
                    isset($data[$column]) &&
                    $data[$column] instanceof \Illuminate\Http\UploadedFile &&
                    $data[$column]->isValid()
                ) {
                    $filename = Str::random(20) . '.' . $data[$column]->getClientOriginalExtension();

                    $data[$column]->storeAs('service_visit', $filename, 'public');

                    $data[$column] = $appUrl . '/storage/app/public/service_visit/' . $filename;
                } else {
                    // 🔴 CRITICAL: do NOT overwrite existing value
                    unset($data[$column]);
                }
            }

            // 🔹 Track updater
            $data['updated_user'] = Auth::id();

            // 🔹 Update ONLY provided fields
            $record->fill($data);
            $record->save();

            return $record;
        });
    }



    /**
     * Delete record by UUID
     */
    public function deleteByUuid(string $uuid): void
    {
        $record = $this->findByUuid($uuid);

        if (!$record) {
            throw new \Exception("Service Visit not found for UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $record->deleted_user = Auth::id();
            $record->save();
            $record->delete();

            DB::commit();
        } catch (Throwable $e) {

            DB::rollBack();

            Log::error("ServiceVisit delete failed", [
                'error' => $e->getMessage(),
                'uuid'  => $uuid,
            ]);

            throw new \Exception("Something went wrong while deleting service visit: " . $e->getMessage(), 0, $e);
        }
    }

    public function globalFilter(int $perPage = 50, array $filters = [])
    {
        try {

            $filter = $filters['filter'] ?? [];

            $query = ServiceVisit::latest();

            if (!empty($filter['from_date']) && !empty($filter['to_date'])) {

                $fromDate = $filter['from_date'] . ' 00:00:00';
                $toDate   = $filter['to_date'] . ' 23:59:59';
            } else {
                $fromDate = now()->startOfMonth()->format('Y-m-d 00:00:00');
                $toDate   = now()->endOfMonth()->format('Y-m-d 23:59:59');
            }

            $query->whereBetween('created_at', [$fromDate, $toDate]);

            if (!empty($filter['ticket_type'])) {
                $query->where('ticket_type', $filter['ticket_type']);
            }

            if (!empty($filter['technician_id'])) {
                $techIds = is_array($filter['technician_id'])
                    ? $filter['technician_id']
                    : explode(',', $filter['technician_id']);

                $query->whereIn('technician_id', array_map('intval', $techIds));
            }

            $results = $query->latest()->paginate($perPage);

            $results->getCollection()->transform(function ($item) {

                $item->makeHidden([
                    'created_user',
                ]);

                return ApprovalHelper::attach($item, 'Service_Visit');
            });

            return $results;
        } catch (Throwable $e) {
            Log::error('ServiceVisitService::globalFilter failed', [
                'filters' => $filters,
                'error'   => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function generateAndStoreServiceVisitPdf($uuid)
    {
        $data = DB::table('tbl_service_visit')->where('uuid', $uuid)->first();

        if (!$data) {
            return null;
        }

        $imageFields = [
            'scan_image' => 'Scan Image',
            'is_machine_in_working_img' => 'Machine Working',
            'cleanliness_img' => 'Cleanliness',
            'condensor_coil_cleand_img' => 'Condensor Coil Cleaned',
            'stock_availability_in_img' => 'Stock Availability',
            'cooler_image' => 'Cooler Image',
            'customer_signature' => 'Customer Signature',
            'type_details_photo1' => 'Type Details Photo',
        ];

        $images = [];

        foreach ($imageFields as $column => $label) {
            if (!empty($data->$column)) {
                $images[] = [
                    'label' => $label,
                    'path' => $data->$column,
                ];
            }
        }

        if (empty($images)) {
            return null;
        }

        $pdf = Pdf::loadView('service-visit-images', [
            'images' => $images,
            'uuid' => $uuid,
        ]);

        $fileName = "service_visit/{$uuid}.pdf";

        Storage::disk('public')->put($fileName, $pdf->output());

        return $fileName;
    }
}
