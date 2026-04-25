<?php

namespace App\Services\V1\Assets\Web;

use App\Models\CallRegister;
use App\Models\AddChiller;
use App\Models\AgentCustomer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use App\Helpers\ApprovalHelper;
use App\Exports\CallRegisterExport;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\DataAccessHelper;
use App\Helpers\CommonLocationFilter;

class CallRegisterService
{
    public function getAll(int $perPage = 50, array $filters = [], bool $dropdown = false)
    {
        try {
            $query = CallRegister::with([
                'currentCustomer',  // important
            ]);
            if ($dropdown) {
                return $query
                    ->select('id', 'osa_code')
                    ->where('status', "Pending")   // if string use '1'
                    ->where('ticket_type', 'BD')
                    ->orderBy('id', 'desc')
                    ->get();
            }
            // dd($query);
            foreach ($filters as $key => $value) {
                if (empty($value)) continue;
                if (in_array($key, ['ticket_no', 'osa_code', 'outlet_name', 'owner_name'])) {
                    $query->whereRaw("LOWER({$key}) LIKE ?", ['%' . strtolower($value) . '%']);
                } else {
                    $query->where($key, $value);
                }
            }
            $query->orderBy('created_at', 'desc');
            // return $query->paginate($perPage);
            $result = $query->paginate($perPage);
            $result->getCollection()->transform(function ($item) {
                return ApprovalHelper::attach($item, 'Call_Register');
            });

            return $result;
        } catch (Throwable $e) {
            Log::error("CallRegister fetch failed", [
                'error'   => $e->getMessage(),
                'filters' => $filters
            ]);
            throw new \Exception("Failed to fetch Call Register list", 0, $e);
        }
    }

    public function generateCode(?string $inputCode = null): string
    {
        if (!empty($inputCode)) {
            return $inputCode;
        }
        do {
            $last = CallRegister::withTrashed()->latest('id')->first();
            $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $osa_code = 'BD' . str_pad($next, 3, '0', STR_PAD_LEFT);
        } while (CallRegister::withTrashed()->where('osa_code', $osa_code)->exists());

        return $osa_code;
    }
    public function create(array $data): CallRegister
    {
        DB::beginTransaction();
        try {
            $data = array_merge($data, [
                'osa_code' => $data['osa_code'] ?? $this->generateCode(),
                'uuid'     => $data['uuid'] ?? Str::uuid()->toString(),
                'created_user' => Auth::id(),
            ]);
            $record = CallRegister::create($data);
            $record->load([
                'assignedCustomer:id,osa_code,name,owner_name,district,town,contact_no,contact_no2'
            ]);
            DB::commit();
            $assignment = DB::table('htapp_workflow_assignments')->where('process_type', 'Call_Register')->where('is_active', true)->first();
            if ($assignment) {
                app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)->startApproval([
                    'process_type' => 'Call_Register',
                    'process_id'   => $record->id,
                ]);
            }
            return $record;
        } catch (Throwable $e) {
            dd($e);
            DB::rollBack();
            Log::error("CallRegister create failed", [
                'error' => $e->getMessage(),
                'data'  => $data,
                'user'  => Auth::id()
            ]);
            throw new \Exception("Something went wrong while creating Call Register", 0, $e);
        }
    }

    // public function findByUuid(string $uuid): ?CallRegister
    //     {
    //         if (!Str::isUuid($uuid)) {
    //             throw new \Exception("Invalid UUID format: {$uuid}");
    //         }

    //         return CallRegister::where('uuid', $uuid)->first();
    //     }
    public function findByUuid(string $uuid): ?CallRegister
    {
        if (!Str::isUuid($uuid)) {
            throw new \Exception("Invalid UUID format: {$uuid}");
        }

        $record = CallRegister::with([
            'serviceVisits:id,nature_of_call_id,uuid,osa_code,created_at',
            'warehouse:id,area_id,warehouse_code,warehouse_name',
            'warehouse.area:id,region_id',
            'warehouse.area.region:id',
        ])
            ->where('uuid', $uuid)
            ->first();

        if (!$record) {
            return null;
        }

        return ApprovalHelper::attach($record, 'Call_Register');
    }


    public function updateByUuid(string $uuid, array $data): CallRegister
    {
        $record =  CallRegister::with([
            'warehouse:id,area_id,warehouse_code,warehouse_name',
            'warehouse.area:id,region_id',
            'warehouse.area.region:id',
        ])
            ->where('uuid', $uuid)
            ->first();
        if (!$record) {
            throw new \Exception("Record not found for UUID: {$uuid}");
        }
        DB::beginTransaction();
        try {
            $data['updated_user'] = Auth::id();

            $record->fill($data);
            $record->save();

            DB::commit();

            return $record;
        } catch (Throwable $e) {

            dd($e);
            DB::rollBack();

            Log::error("CallRegister update failed", [
                'error' => $e->getMessage(),
                'uuid'  => $uuid,
                'data'  => $data
            ]);
            throw new \Exception("Something went wrong while updating Call Register", 0, $e);
        }
    }

    public function deleteByUuid(string $uuid): bool
    {
        $record = $this->findByUuid($uuid);
        if (!$record) {
            throw new \Exception("Record not found for UUID: {$uuid}");
        }
        DB::beginTransaction();
        try {
            $record->deleted_user = Auth::id();
            $record->save();
            $record->delete();
            DB::commit();
            return true;
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error("CallRegister delete failed", [
                'error' => $e->getMessage(),
                'uuid'  => $uuid,
            ]);
            throw new \Exception("Something went wrong while deleting Call Register", 0, $e);
        }
    }

    public function globalSearch(string $searchTerm = null, int $perPage = 20)
    {
        try {
            $query = CallRegister::query()
                ->with(['technician:id,osa_code,name']);


            if (!empty($searchTerm)) {

                $searchTerm = strtolower($searchTerm);
                $like      = '%' . $searchTerm . '%';

                $query->where(function ($q) use ($like) {
                    $q->orWhereRaw("LOWER(ticket_no) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(outlet_name) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(owner_name) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(model_number) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(chiller_serial_number) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(branding) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(osa_code) LIKE ?", [$like]);

                    $q->orWhereHas('technician', function ($t) use ($like) {
                        $t->whereRaw("LOWER(osa_code) LIKE ?", [$like])
                            ->orWhereRaw("LOWER(name) LIKE ?", [$like]);
                    });
                });
            }

            $query->orderBy('id', 'desc');

            return $query->paginate($perPage);
        } catch (Throwable $e) {

            Log::error("CallRegister global search failed", [
                'error' => $e->getMessage(),
                'search' => $searchTerm
            ]);

            throw new \Exception("Failed to search Call Register", 0, $e);
        }
    }


    public function getChillerBySerial(string $serial)
    {
        return AddChiller::select([
            'id',
            'serial_number',
            'osa_code',
            'assets_category',
            'model_number',
            'manufacturer',
            'branding',
            'country_id',
            'customer_id',
        ])
            ->with([
                'assetsCategory:id,osa_code,name',
                'modelNumber:id,code,name',
                'manufacture:id,osa_code,name',
                'brand:id,osa_code,name',
                'country:id,country_code,country_name',
                'customer:id,osa_code,name,owner_name,street,street,landmark,town,district,contact_no,contact_no2'
            ])
            ->where('serial_number', $serial)
            ->first();
    }

    // public function findCurrentCustomer(string $searchTerm)
    // {
    //     try {
    //         $searchTerm = trim($searchTerm);
    //         $search     = '%' . $searchTerm . '%';

    //         return AgentCustomer::query()
    //             ->select([
    //                 'id',
    //                 'osa_code',
    //                 'name',
    //                 'owner_name',
    //                 'contact_no',
    //                 'contact_no2',
    //                 'whatsapp_no',
    //                 'email',
    //                 'town',
    //                 'district',
    //                 'street',
    //                 'latitude',
    //                 'longitude',
    //                 'landmark',
    //                 'warehouse',
    //                 // 'created_user',
    //             ])
    //             ->with([
    //                 'getWarehouse:id,warehouse_code,warehouse_name,area_id,region_id',
    //                 'getWarehouse.area:id,created_user',
    //                 'getWarehouse.area.createdBy:id,username',
    //                 'getWarehouse.region:id,created_user',
    //                 'getWarehouse.region.createdBy:id,username',
    //             ])
    //             ->whereNull('deleted_at')
    //             ->where(function ($q) use ($search) {

    //                 $q->where('osa_code', 'ILIKE', $search)
    //                     ->orWhere('name', 'ILIKE', $search)
    //                     ->orWhere('owner_name', 'ILIKE', $search);
    //             })
    //             ->orderBy('id', 'desc')
    //             ->first();
    //     } catch (\Throwable $e) {
    //         // dd($e);
    //         Log::error('AgentCustomer findCurrentCustomer failed', [
    //             'search' => $searchTerm,
    //             'error'  => $e->getMessage(),
    //         ]);

    //         throw new \Exception('Failed to fetch customer details');
    //     }
    // }

    public function findCurrentCustomer(string $searchTerm)
    {
        try {

            $searchTerm = trim($searchTerm);
            $search     = '%' . $searchTerm . '%';

            $customer = AgentCustomer::query()
                ->select([
                    'id',
                    'osa_code',
                    'name',
                    'owner_name',
                    'contact_no',
                    'contact_no2',
                    'whatsapp_no',
                    'email',
                    'town',
                    'district',
                    'street',
                    'latitude',
                    'longitude',
                    'landmark',
                    'warehouse',
                ])
                ->with([
                    'getWarehouse:id,warehouse_code,warehouse_name,area_id,region_id',
                    'getWarehouse.area:id,region_id',
                    'getWarehouse.area.region:id'
                ])
                ->whereNull('deleted_at')
                ->where(function ($q) use ($search) {
                    $q->where('osa_code', 'ILIKE', $search)
                        ->orWhere('name', 'ILIKE', $search)
                        ->orWhere('owner_name', 'ILIKE', $search);
                })
                ->orderBy('id', 'desc')
                ->first();

            if (!$customer) {
                return null;
            }

            // ✅ Get Warehouse → Area → Region
            $warehouse = $customer->getWarehouse;
            $area      = $warehouse?->area;
            $region    = $area?->region;

            // ✅ Get ASM & RM (JSON based match)
            $asm = $area?->getAsmUser();
            $rm  = $region?->getRmUser();

            // ✅ Attach Extra Data
            $customer->warehouse_code = $warehouse?->warehouse_code;
            $customer->warehouse_name = $warehouse?->warehouse_name;

            $customer->current_asm = [
                'id'   => $asm?->id,
                'name' => $asm?->name,
                'code' => $asm?->username,
            ];

            $customer->current_rm = [
                'id'   => $rm?->id,
                'name' => $rm?->name,
                'code' => $rm?->username,
            ];

            // 🔥 Attach last 3 month sales
            $customer->last_3_month_sales =
                $this->calculateLastThreeMonthSales($customer->id);

            return $customer;
        } catch (\Throwable $e) {

            Log::error('AgentCustomer findCurrentCustomer failed', [
                'search' => $searchTerm,
                'error'  => $e->getMessage(),
            ]);

            throw new \Exception('Failed to fetch customer details');
        }
    }


    private function calculateLastThreeMonthSales(int $customerId): float
    {
        $latestInvoiceDate = DB::table('invoice_headers')
            ->where('customer_id', $customerId)
            ->max('invoice_date');

        if (!$latestInvoiceDate) {
            return 0;
        }

        $threeMonthsAgo = Carbon::parse($latestInvoiceDate)
            ->subMonths(3)
            ->toDateString();

        return (float) DB::table('invoice_headers as ih')
            ->join('invoice_details as id', 'ih.id', '=', 'id.header_id')
            ->where('ih.customer_id', $customerId)
            ->where('ih.invoice_date', '>=', $threeMonthsAgo)
            ->sum('id.item_total');
    }


    // public function export(Request $request): array
    // {
    //     $format = strtolower($request->input('format', 'xlsx'));

    //     if (!in_array($format, ['csv', 'xlsx'])) {
    //         throw new \Exception('Invalid format. Use csv or xlsx only.');
    //     }

    //     $filename = 'call_register_' . now()->format('Ymd_His') . '.' . $format;
    //     $path     = 'exports/' . $filename;

    //     $query = CallRegister::with([
    //         'technician:id,osa_code,name',
    //         // 'customer:id,osa_code,name'
    //     ])->whereNull('deleted_at');

    //     /* ============ SEARCH FILTER ============ */
    //     if ($request->filled('search')) {
    //         $search = trim($request->search);

    //         $query->where(function ($q) use ($search) {
    //             $q->where('osa_code', 'ILIKE', "%{$search}%")
    //                 ->orWhere('ticket_no', 'ILIKE', "%{$search}%")
    //                 ->orWhere('ticket_type', 'ILIKE', "%{$search}%")
    //                 ->orWhere('outlet_name', 'ILIKE', "%{$search}%")
    //                 ->orWhere('owner_name', 'ILIKE', "%{$search}%")
    //                 ->orWhere('asset_number', 'ILIKE', "%{$search}%")
    //                 ->orWhere('model_number', 'ILIKE', "%{$search}%")
    //                 ->orWhere('status', 'ILIKE', "%{$search}%")
    //                 ->orWhereHas('technician', function ($t) use ($search) {
    //                     $t->where('osa_code', 'ILIKE', "%{$search}%")
    //                         ->orWhere('name', 'ILIKE', "%{$search}%");
    //                 });
    //             // ->orWhereHas('customer', function ($c) use ($search) {
    //             //     $c->where('osa_code', 'ILIKE', "%{$search}%")
    //             //         ->orWhere('name', 'ILIKE', "%{$search}%");
    //             // });
    //         });
    //     }

    //     /* ============ DATE FILTER ============ */
    //     if ($request->filled('from_date') && $request->filled('to_date')) {
    //         $query->whereBetween('ticket_date', [
    //             $request->from_date,
    //             $request->to_date
    //         ]);
    //     }

    //     $export = new CallRegisterExport($query);

    //     Excel::store(
    //         $export,
    //         $path,
    //         'public',
    //         $format === 'csv'
    //             ? \Maatwebsite\Excel\Excel::CSV
    //             : \Maatwebsite\Excel\Excel::XLSX
    //     );

    //     $appUrl = rtrim(config('app.url'), '/');

    //     return [
    //         'download_url' => $appUrl . '/storage/app/public/' . $path
    //     ];
    // }

    public function export(Request $request): array
    {
        $format = strtolower($request->input('format', 'xlsx'));

        if (!in_array($format, ['csv', 'xlsx'])) {
            throw new \Exception('Invalid format. Use csv or xlsx only.');
        }

        // 🔥 Get filter array safely
        $filters = $request->input('filter', []);

        $filename = 'call_register_' . now()->format('Ymd_His') . '.' . $format;
        $path     = 'exports/' . $filename;

        $query = CallRegister::with([
            'technician:id,osa_code,name',
            'asset:id,serial_number,osa_code,model_number,manufacturer,branding,country_id,customer_id',
            'asset.assetsCategory:id,osa_code,name',
            'asset.modelNumber:id,code,name',
            'asset.manufacture:id,osa_code,name',
            'asset.brand:id,osa_code,name',
            'asset.country:id,country_code,country_name',
            'asset.customer:id,osa_code,name,owner_name,street,landmark,town,district,contact_no,contact_no2',
            'asset.asm:id,name',
            'asset.rm:id,name',
        ])->whereNull('deleted_at');

        /* ============ FILTERS ============ */

        if (!empty($filters['ticket_type'])) {
            $query->where('ticket_type', $filters['ticket_type']);
        }

        if (!empty($filters['technician_id'])) {

            $technicianIds = is_array($filters['technician_id'])
                ? $filters['technician_id']
                : array_map('trim', explode(',', $filters['technician_id']));

            $query->whereIn('technician_id', $technicianIds);
        }
        if (!empty($filters['call_status'])) {
            $query->where('status', $filters['call_status']);
        }

        /* ============ DATE FILTER ============ */
        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
            $query->whereBetween('ticket_date', [
                $filters['from_date'],
                $filters['to_date'],
            ]);
        }

        $export = new CallRegisterExport($query);

        Excel::store(
            $export,
            $path,
            'public',
            $format === 'csv'
                ? \Maatwebsite\Excel\Excel::CSV
                : \Maatwebsite\Excel\Excel::XLSX
        );

        $appUrl = rtrim(config('app.url'), '/');

        return [
            'download_url' => $appUrl . '/storage/app/public/' . $path
        ];
    }

    public function globalFilter(int $perPage = 50, array $filters = [])
    {
        try {
            $user = auth()->user();

            $filter = $filters['filter'] ?? [];
            $query = CallRegister::query();
            $query = DataAccessHelper::filterAgentTransaction($query, $user);


            if (!empty($filter)) {

                $warehouseIds = CommonLocationFilter::resolveWarehouseIds([
                    'company_id'   => $filter['company_id']   ?? null,
                    'region_id'    => $filter['region_id']    ?? null,
                    'area_id'      => $filter['area_id']      ?? null,
                    'warehouse_id' => $filter['warehouse_id'] ?? null,
                    'route_id'     => $filter['route_id']     ?? null,
                ]);

                if (!empty($warehouseIds)) {
                    $query->whereHas('assignedCustomer', function ($q) use ($warehouseIds) {
                        $q->whereIn('warehouse', $warehouseIds);
                    });
                }
            }
            if (!empty($filter['from_date']) && !empty($filter['to_date'])) {
                $query->whereBetween('ticket_date', [
                    $filter['from_date'],
                    $filter['to_date']
                ]);
            }
            if (!empty($filter['technician_id'])) {
                $techIds = is_array($filter['technician_id'])
                    ? $filter['technician_id']
                    : explode(',', $filter['technician_id']);

                $query->whereIn('technician_id', array_map('intval', $techIds));
            }

            if (!empty($filter['call_status'])) {
                $query->where('status', $filter['call_status']);
            }

            if (!empty($filter['ticket_type'])) {
                $query->where('ticket_type', $filter['ticket_type']);
            }

            $results = $query->latest('ticket_date')->paginate($perPage);

            return $results;
        } catch (\Throwable $e) {
            Log::error('CallRegisterService::globalFilter failed', [
                'filters' => $filters,
                'error'   => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
