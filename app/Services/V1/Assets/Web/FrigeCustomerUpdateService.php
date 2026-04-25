<?php

namespace App\Services\V1\Assets\Web;

use App\Models\FrigeCustomerUpdate;
use App\Exports\FridgeCustomerUpdateExport;
use App\Helpers\DataAccessHelper;
use App\Helpers\CommonLocationFilter;
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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Pagination\Paginator;

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

    //         if ($item->customer_id) {
    //             $item->last_three_month_sales =
    //                 $this->calculateLastThreeMonthSales($item->customer_id);
    //         } else {
    //             $item->last_three_month_sales = 0;
    //         }
    //         return \App\Helpers\PetitApprovalHelper::attach($item, 'Frige_Customer_Update');
    //     });

    //     return $result;
    // }

    public function list(array $filters): LengthAwarePaginator
    {
        $user = auth()->user();
        $query = FrigeCustomerUpdate::query();
        $query = DataAccessHelper::filterAssets($query, $user);
        $fromDate = $filters['from_date'] ?? null;
        $toDate   = $filters['to_date'] ?? null;

        if (empty($fromDate) && empty($toDate)) {
            $fromDate = Carbon::now()->startOfMonth()->toDateString();
            $toDate   = Carbon::now()->endOfMonth()->toDateString();
        } else {
            $fromDate = $fromDate ?? Carbon::now()->startOfMonth()->toDateString();
            $toDate   = $toDate   ?? Carbon::now()->endOfMonth()->toDateString();
        }

        $query->whereBetween('created_at', [
            $fromDate . ' 00:00:00',
            $toDate   . ' 23:59:59'
        ]);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('osa_code', 'ILIKE', "%{$search}%")
                    ->orWhere('outlet_name', 'ILIKE', "%{$search}%")
                    ->orWhere('owner_name', 'ILIKE', "%{$search}%");
            });
        }

        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (!empty($filters['salesman_id'])) {
            $query->where('salesman_id', $filters['salesman_id']);
        }

        if (!empty($filters['route_id'])) {
            $query->where('route_id', $filters['route_id']);
        }

        if (isset($filters['status'])) {
            $direction = strtolower($filters['status']) === 1 ? 0 : 1;
            $query->orderBy('status', $direction);
        } else {
            $query->orderByDesc('id'); // default fallback
        }

        $limit = (int) ($filters['limit'] ?? 20);

        $result = $query->paginate($limit);

        // ✅ Transform
        $result->getCollection()->transform(function ($item) {

            $item->last_three_month_sales = $item->customer_id
                ? $this->calculateLastThreeMonthSales($item->customer_id)
                : 0;

            return \App\Helpers\PetitApprovalHelper::attach($item, 'Frige_Customer_Update');
        });

        return $result;
    }
    private function calculateLastThreeMonthSales(int $customerId): float
    {
        $latestInvoiceDate = DB::table('invoice_headers')
            ->where('customer_id', $customerId)
            ->max('created_at');

        if (!$latestInvoiceDate) {
            return 0;
        }

        $threeMonthsAgo = Carbon::parse($latestInvoiceDate)
            ->subMonths(3)
            ->toDateString();

        return (float) DB::table('invoice_headers as ih')
            ->join('invoice_details as id', 'ih.id', '=', 'id.header_id')
            ->where('ih.customer_id', $customerId)
            ->where('ih.created_at', '>=', $threeMonthsAgo)
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

    public function export(Request $request)
    {

        $format    = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';

        if (!in_array($format, ['csv', 'xlsx'])) {
            throw new \Exception('Invalid format. Use csv or xlsx only.');
        }
        $date = now()->format('Ymd');
        $directory = 'exports';

        $files = Storage::disk('public')->files($directory);
        $todayFiles = array_filter($files, function ($file) use ($date) {
            return str_contains($file, "Fridge_customer_update_{$date}_");
        });

        $numbers = [];

        foreach ($todayFiles as $file) {
            if (preg_match('/_(\d+)\.(csv|xlsx)$/', $file, $matches)) {
                $numbers[] = (int) $matches[1];
            }
        }

        $next = !empty($numbers) ? max($numbers) + 1 : 1;
        $sequence = str_pad($next, 2, '0', STR_PAD_LEFT);

        $filename = "Fridge_customer_update_{$date}_{$sequence}.{$extension}";
        $path     = $directory . '/' . $filename;
        $filters = $request->input('filter', []);

        $fromDate = $filters['from_date'] ?? null;
        $toDate   = $filters['to_date'] ?? null;

        if (empty($fromDate) && empty($toDate)) {
            $fromDate = Carbon::now()->startOfMonth()->toDateString();
            $toDate   = Carbon::now()->endOfMonth()->toDateString();
        }
        $parseIds = function ($value) {
            if (empty($value)) return [];
            return array_map('intval', array_filter(array_map('trim', explode(',', $value))));
        };

        // ✅ Resolve warehouse (main filter logic)
        $resolvedWarehouseIds = CommonLocationFilter::resolveWarehouseIds([
            'company_id'   => $filters['company_id'] ?? null,
            'region_id'    => $filters['region_id'] ?? null,
            'area_id'      => $filters['area_id'] ?? null,
            'warehouse_id' => $parseIds($filters['warehouse_id'] ?? null),
            'route_id'     => $filters['route_id'] ?? null,
        ]);

        // ✅ Optional salesman (if passed)
        $salesmanIds = $parseIds($filters['salesman_id'] ?? null);

        // ✅ Export call
        $export = new FridgeCustomerUpdateExport(
            $fromDate,
            $toDate,
            $resolvedWarehouseIds,
            $salesmanIds
        );

        // ✅ Store file
        if ($format === 'csv') {
            \Maatwebsite\Excel\Facades\Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
        } else {
            \Maatwebsite\Excel\Facades\Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
        }

        $fullUrl = rtrim(config('app.url'), '/') . '/storage/app/public/' . $path;

        return [
            'status' => 'success',
            'download_url' => $fullUrl,
        ];
    }

    // public function export(Request $request): array
    // {

    //     $format = strtolower($request->input('format', 'xlsx'));

    //     if (!in_array($format, ['csv', 'xlsx'])) {
    //         throw new \Exception('Invalid format. Use csv or xlsx only.');
    //     }

    //     $filename = 'fridge_customer_update_' . now()->format('Ymd_His') . '.' . $format;
    //     $path     = 'exports/' . $filename;
    //     $query = FrigeCustomerUpdate::with([
    //         'salesman:id,osa_code,name',
    //         'route:id,route_code,route_name',
    //         'warehouse:id,warehouse_code,warehouse_name',
    //         'customer:id,osa_code,name',
    //         'outletChannel:id,outlet_channel'

    //     ]);

    //     if ($request->filled('search')) {
    //         $search = trim($request->search);

    //         $query->where(function ($q) use ($search) {
    //             $q->where('osa_code', 'ILIKE', "%{$search}%")
    //                 ->orWhere('outlet_name', 'ILIKE', "%{$search}%")
    //                 ->orWhere('owner_name', 'ILIKE', "%{$search}%")
    //                 ->orWhere('contact_number', 'ILIKE', "%{$search}%")
    //                 ->orWhere('asset_number', 'ILIKE', "%{$search}%")
    //                 ->orWhere('serial_no', 'ILIKE', "%{$search}%")
    //                 ->orWhere('brand', 'ILIKE', "%{$search}%")

    //                 ->orWhereHas('salesman', function ($s) use ($search) {
    //                     $s->where('osa_code', 'ILIKE', "%{$search}%")
    //                         ->orWhere('name', 'ILIKE', "%{$search}%");
    //                 })

    //                 ->orWhereHas('route', function ($r) use ($search) {
    //                     $r->where('route_code', 'ILIKE', "%{$search}%")
    //                         ->orWhere('route_name', 'ILIKE', "%{$search}%");
    //                 })

    //                 ->orWhereHas('customer', function ($c) use ($search) {
    //                     $c->where('osa_code', 'ILIKE', "%{$search}%")
    //                         ->orWhere('name', 'ILIKE', "%{$search}%");
    //                 });
    //         });
    //     }

    //     /* ========= DATE FILTER ========= */
    //     if ($request->filled('from_date') && $request->filled('to_date')) {
    //         $query->whereBetween('created_at', [
    //             $request->from_date,
    //             $request->to_date
    //         ]);
    //     }

    //     $export = new FridgeCustomerUpdateExport($query);

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

    public function generateAndStoreFridgeCustomerPdf($uuid)
    {
        $data = DB::table('frige_customer_update')->where('uuid', $uuid)->first();

        if (!$data) {
            return null;
        }

        $imageFields = [
            'national_id_file' => 'National Id Front',
            'national_id1_file' => 'National Id Back',
            'password_photo_file' => 'Password Photo',
            'outlet_address_proof_file' => 'Outlet Address Proof',
            'trading_licence_file' => 'Trading License',
            'lc_letter_file' => 'Lc Letter',
            'outlet_stamp_file' => 'Outlet Stamp',
            'sign__customer_file' => 'Sign Customer',
            'sign_salesman_file' => 'Sign Salesman',
            'fridge_scan_img' => 'Fridge Scan Image',
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

        $pdf = Pdf::loadView('fridge-customer-images', [
            'images' => $images,
            'uuid' => $uuid,
        ]);

        $fileName = "fridge_customer_update/{$uuid}.pdf";

        Storage::disk('public')->put($fileName, $pdf->output());

        return $fileName;
    }

    private function parseIds($value): array
    {
        if (is_array($value)) {
            return array_map('intval', $value);
        }

        return array_map(
            'intval',
            array_filter(array_map('trim', explode(',', $value)))
        );
    }
    public function globalFilter(int $perPage = 50, array $filters = [])
    {
        $user   = auth()->user();
        $filter = $filters['filter'] ?? [];

        if (!empty($filters['current_page'])) {
            Paginator::currentPageResolver(function () use ($filters) {
                return (int) $filters['current_page'];
            });
        }

        $query = FrigeCustomerUpdate::with([
            'warehouse:id,warehouse_code,warehouse_name',
            'customer:id,name,osa_code',
            'salesman:id,name,osa_code',
        ])->latest();

        $query = DataAccessHelper::filterAgentTransaction($query, $user);

        // =========================
        // ✅ LOCATION FILTER
        // =========================
        if (!empty($filter)) {

            $warehouseIds = CommonLocationFilter::resolveWarehouseIds([
                'company_id'   => $filter['company_id'] ?? null,
                'region_id'    => $filter['region_id'] ?? null,
                'area_id'      => $filter['area_id'] ?? null,
                'warehouse_id' => !empty($filter['warehouse_id']) ? $this->parseIds($filter['warehouse_id']) : null,
                'route_id'     => $filter['route_id'] ?? null,
            ]);

            if (!empty($warehouseIds)) {
                $query->whereIn('warehouse_id', $warehouseIds);
            }
        }

        // =========================
        // ✅ SALESMAN FILTER (FIXED)
        // =========================
        if (!empty($filter['salesman_id'])) {
            $salesmanIds = $this->parseIds($filter['salesman_id']);
            $query->whereIn('salesman_id', $salesmanIds);
        }

        // =========================
        // ✅ DATE FILTER
        // =========================
        $fromDate = $filter['from_date'] ?? null;
        $toDate   = $filter['to_date'] ?? null;

        if ($fromDate && $toDate) {
            $query->whereBetween('created_at', [
                $fromDate . ' 00:00:00',
                $toDate   . ' 23:59:59'
            ]);
        } elseif ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        } elseif ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        return $query->paginate($perPage);
    }
}
