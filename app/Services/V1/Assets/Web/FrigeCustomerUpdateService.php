<?php

namespace App\Services\V1\Assets\Web;

use App\Models\FrigeCustomerUpdate;
use App\Exports\FridgeCustomerUpdateExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class FrigeCustomerUpdateService
{

    // public function list(array $filters): LengthAwarePaginator
    // {
    //     $query = FrigeCustomerUpdate::query()
    //         ->orderByDesc('id');

    //     if (!empty($filters) && isset($filters['filter']) && is_array($filters['filter'])) {

    //         $warehouseIds = \App\Helpers\CommonLocationFilter::resolveWarehouseIds([
    //             'company'   => $filters['filter']['company_id']   ?? null,
    //             'region'    => $filters['filter']['region_id']    ?? null,
    //             'area'      => $filters['filter']['area_id']      ?? null,
    //             'warehouse' => $filters['filter']['warehouse_id'] ?? null,
    //             'route'     => $filters['filter']['route_id']     ?? null,
    //         ]);

    //         if (!empty($warehouseIds)) {
    //             $query->whereIn('warehouse_id', $warehouseIds);
    //         }
    //     }
    //     if (!empty($filters['search'])) {
    //         $search = $filters['search'];
    //         $query->where(function ($q) use ($search) {
    //             $q->where('osa_code', 'ILIKE', "%{$search}%")
    //                 ->orWhere('outlet_name', 'ILIKE', "%{$search}%")
    //                 ->orWhere('owner_name', 'ILIKE', "%{$search}%");
    //         });
    //     }

    //     if (!empty($filters['osa_code'])) {
    //         $query->where('osa_code', 'ILIKE', '%' . $filters['osa_code'] . '%');
    //     }

    //     if (isset($filters['status'])) {
    //         $query->where('status', $filters['status']);
    //     }

    //     if (!empty($filters['salesman_id'])) {
    //         $query->where('salesman_id', $filters['salesman_id']);
    //     }

    //     if (!empty($filters['route_id'])) {
    //         $query->where('route_id', $filters['route_id']);
    //     }

    //     $limit = (int) ($filters['limit'] ?? 20);
    //     $result = $query->paginate($limit);

    //     $result->getCollection()->transform(function ($item) {

    //         $workflowRequest = \DB::table('htapp_workflow_requests')
    //             ->where('process_type', 'Frige_Customer_Update')
    //             ->where('process_id', $item->id)
    //             ->latest()
    //             ->first();

    //         $item->approval_status = null;
    //         $item->current_step    = null;
    //         $item->request_step_id = null;
    //         $item->progress        = null;

    //         if ($workflowRequest) {

    //             $currentStep = \DB::table('htapp_workflow_request_steps')
    //                 ->where('workflow_request_id', $workflowRequest->id)
    //                 ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
    //                 ->orderBy('step_order')
    //                 ->first();

    //             $totalSteps = \DB::table('htapp_workflow_request_steps')
    //                 ->where('workflow_request_id', $workflowRequest->id)
    //                 ->count();

    //             $approvedSteps = \DB::table('htapp_workflow_request_steps')
    //                 ->where('workflow_request_id', $workflowRequest->id)
    //                 ->where('status', 'APPROVED')
    //                 ->count();

    //             $lastApprovedStep = \DB::table('htapp_workflow_request_steps')
    //                 ->where('workflow_request_id', $workflowRequest->id)
    //                 ->where('status', 'APPROVED')
    //                 ->orderBy('step_order', 'desc')
    //                 ->first();

    //             $item->approval_status = $lastApprovedStep
    //                 ? $lastApprovedStep->message
    //                 : 'Initiated';

    //             $item->current_step    = $currentStep->title ?? null;
    //             $item->request_step_id = $currentStep->id ?? null;
    //             $item->progress        = $totalSteps > 0
    //                 ? "{$approvedSteps}/{$totalSteps}"
    //                 : null;
    //         }

    //         return $item;
    //     });

    //     return $result;
    // }

    public function list(array $filters): LengthAwarePaginator
    {
        $query = FrigeCustomerUpdate::query()
            ->orderByDesc('id');

        if (!empty($filters) && isset($filters['filter']) && is_array($filters['filter'])) {

            $warehouseIds = \App\Helpers\CommonLocationFilter::resolveWarehouseIds([
                'company'   => $filters['filter']['company_id']   ?? null,
                'region'    => $filters['filter']['region_id']    ?? null,
                'area'      => $filters['filter']['area_id']      ?? null,
                'warehouse' => $filters['filter']['warehouse_id'] ?? null,
                'route'     => $filters['filter']['route_id']     ?? null,
            ]);

            if (!empty($warehouseIds)) {
                $query->whereIn('warehouse_id', $warehouseIds);
            }
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('osa_code', 'ILIKE', "%{$search}%")
                    ->orWhere('outlet_name', 'ILIKE', "%{$search}%")
                    ->orWhere('owner_name', 'ILIKE', "%{$search}%");
            });
        }

        if (!empty($filters['osa_code'])) {
            $query->where('osa_code', 'ILIKE', '%' . $filters['osa_code'] . '%');
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['salesman_id'])) {
            $query->where('salesman_id', $filters['salesman_id']);
        }

        if (!empty($filters['route_id'])) {
            $query->where('route_id', $filters['route_id']);
        }

        $limit = (int) ($filters['limit'] ?? 20);
        $result = $query->paginate($limit);
        $result->getCollection()->transform(function ($item) {

            if ($item->customer_id) {
                $item->last_three_month_sales =
                    $this->calculateLastThreeMonthSales($item->customer_id);
            } else {
                $item->last_three_month_sales = 0;
            }
            return \App\Helpers\PetitApprovalHelper::attach($item, 'Frige_Customer_Update');
        });

        return $result;
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


    public function getByUuid(string $uuid): FrigeCustomerUpdate
    {
        if (!\Str::isUuid($uuid)) {
            throw new \Exception("Invalid UUID format: {$uuid}");
        }

        $asset = FrigeCustomerUpdate::where('uuid', $uuid)->firstOrFail();


        $workflowRequest = \DB::table('htapp_workflow_requests')
            ->where('process_type', 'Frige_Customer_Update')
            ->where('process_id', $asset->id)
            ->latest()
            ->first();

        $asset->approval_status = null;
        $asset->current_step    = null;
        $asset->request_step_id = null;
        $asset->progress        = null;

        if ($workflowRequest) {

            $currentStep = \DB::table('htapp_workflow_request_steps')
                ->where('workflow_request_id', $workflowRequest->id)
                ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
                ->orderBy('step_order')
                ->first();

            $totalSteps = \DB::table('htapp_workflow_request_steps')
                ->where('workflow_request_id', $workflowRequest->id)
                ->count();

            $approvedSteps = \DB::table('htapp_workflow_request_steps')
                ->where('workflow_request_id', $workflowRequest->id)
                ->where('status', 'APPROVED')
                ->count();

            $lastApprovedStep = \DB::table('htapp_workflow_request_steps')
                ->where('workflow_request_id', $workflowRequest->id)
                ->where('status', 'APPROVED')
                ->orderBy('step_order', 'desc')
                ->first();

            $asset->approval_status = $lastApprovedStep
                ? $lastApprovedStep->message
                : 'Initiated';

            $asset->current_step    = $currentStep->title ?? null;
            $asset->request_step_id = $currentStep->id ?? null;
            $asset->progress        = $totalSteps > 0
                ? "{$approvedSteps}/{$totalSteps}"
                : null;
        }

        return $asset;
    }


    public function updateByUuid(string $uuid, array $data): FrigeCustomerUpdate
    {
        DB::beginTransaction();
        // dd($data);
        try {
            $record = FrigeCustomerUpdate::where('uuid', $uuid)->first();

            if (! $record) {
                throw new ModelNotFoundException('Fridge customer update not found');
            }

            $fileFields = [
                'national_id_file',
                'password_photo_file',
                'outlet_address_proof_file',
                'trading_licence_file',
                'lc_letter_file',
                'outlet_stamp_file',
                'sign__customer_file',
                'national_id1_file',
                'password_photo1_file',
                'outlet_address_proof1_file',
                'trading_licence1_file',
                'lc_letter1_file',
                'outlet_stamp1_file',
                'sign_salesman_file',
                'fridge_scan_img',
            ];
            foreach ($fileFields as $field) {

                if (isset($data[$field]) && $data[$field] instanceof UploadedFile) {

                    // Delete old file if exists
                    if ($record->$field) {

                        // remove "storage/" before deleting from disk
                        $oldPath = str_replace('storage/', '', $record->$field);

                        Storage::disk('public')->delete($oldPath);
                    }

                    $filename = time() . '_' . Str::random(8) . '.' .
                        $data[$field]->getClientOriginalExtension();

                    $path = $data[$field]->storeAs('customer_frige', $filename, 'public');

                    // ✅ Store EXACT same format like create
                    $record->$field = 'storage/' . $path;
                }
            }


            // Non-file fields update
            foreach ($data as $key => $value) {
                if (!in_array($key, $fileFields)) {
                    $record->$key = $value;
                }
            }

            $record->save();

            DB::commit();

            return $record->refresh();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function export(Request $request): array
    {

        $format = strtolower($request->input('format', 'xlsx'));

        if (!in_array($format, ['csv', 'xlsx'])) {
            throw new \Exception('Invalid format. Use csv or xlsx only.');
        }

        $filename = 'fridge_customer_update_' . now()->format('Ymd_His') . '.' . $format;
        $path     = 'exports/' . $filename;
        $query = FrigeCustomerUpdate::with([
            'salesman:id,osa_code,name',
            'route:id,route_code,route_name',
            'warehouse:id,warehouse_code,warehouse_name',
            'customer:id,osa_code,name'
        ]);

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('osa_code', 'ILIKE', "%{$search}%")
                    ->orWhere('outlet_name', 'ILIKE', "%{$search}%")
                    ->orWhere('owner_name', 'ILIKE', "%{$search}%")
                    ->orWhere('contact_number', 'ILIKE', "%{$search}%")
                    ->orWhere('asset_number', 'ILIKE', "%{$search}%")
                    ->orWhere('serial_no', 'ILIKE', "%{$search}%")
                    ->orWhere('brand', 'ILIKE', "%{$search}%")

                    ->orWhereHas('salesman', function ($s) use ($search) {
                        $s->where('osa_code', 'ILIKE', "%{$search}%")
                            ->orWhere('name', 'ILIKE', "%{$search}%");
                    })

                    ->orWhereHas('route', function ($r) use ($search) {
                        $r->where('route_code', 'ILIKE', "%{$search}%")
                            ->orWhere('route_name', 'ILIKE', "%{$search}%");
                    })

                    ->orWhereHas('customer', function ($c) use ($search) {
                        $c->where('osa_code', 'ILIKE', "%{$search}%")
                            ->orWhere('name', 'ILIKE', "%{$search}%");
                    });
            });
        }

        /* ========= DATE FILTER ========= */
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('created_at', [
                $request->from_date,
                $request->to_date
            ]);
        }

        $export = new FridgeCustomerUpdateExport($query);

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

    public function globalSearch(string $searchTerm = null, int $perPage = 20)
    {
        try {
            $query = FrigeCustomerUpdate::query()
                ->with([
                    'salesman:id,osa_code,name',
                    'route:id,route_code,route_name',
                    'warehouse:id,warehouse_code,warehouse_name',
                    'customer:id,osa_code,name'
                ]);

            if (!is_null($searchTerm) && trim($searchTerm) !== '') {

                $searchTerm = strtolower(trim($searchTerm));
                $like = '%' . $searchTerm . '%';

                $query->where(function ($q) use ($like) {

                    /* OWN TABLE */
                    $q->orWhereRaw('LOWER(osa_code) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(outlet_name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(owner_name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(contact_number) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(asset_number) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(serial_no) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(brand) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(model) LIKE ?', [$like]);

                    /* SALESMAN */
                    $q->orWhereHas('salesman', function ($s) use ($like) {
                        $s->whereRaw('LOWER(osa_code) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(name) LIKE ?', [$like]);
                    });

                    /* ROUTE */
                    $q->orWhereHas('route', function ($r) use ($like) {
                        $r->whereRaw('LOWER(route_code) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(route_name) LIKE ?', [$like]);
                    });

                    /* CUSTOMER */
                    $q->orWhereHas('customer', function ($c) use ($like) {
                        $c->whereRaw('LOWER(osa_code) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(name) LIKE ?', [$like]);
                    });

                    /* WAREHOUSE */
                    $q->orWhereHas('warehouse', function ($w) use ($like) {
                        $w->whereRaw('LOWER(warehouse_code) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(warehouse_name) LIKE ?', [$like]);
                    });
                });
            }

            $query->orderBy('id', 'desc');

            return $query->paginate($perPage);
        } catch (\Throwable $e) {
            // dd($e);
            Log::error('FridgeCustomerUpdate global search failed', [
                'error'  => $e->getMessage(),
                'search' => $searchTerm,
            ]);

            throw new \Exception('Failed to search Fridge Customer Update', 0, $e);
        }
    }
}
