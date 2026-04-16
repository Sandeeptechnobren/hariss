<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\Salesman;
use App\Models\Agent_Transaction\InvoiceHeader;
use App\Models\Warehouse;
use App\Models\Route;
use App\Exports\SalesmanInvoicesExport;
use Illuminate\Support\Facades\Storage;
use App\Exports\SalesmanOrderExport;
use App\Exports\SalesmanPoExport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Error;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Illuminate\Support\Facades\Hash;
use App\Exports\SalesmanExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\DataAccessHelper;
use App\Models\Agent_Transaction\OrderHeader;
use App\Models\Hariss_Transaction\Web\PoOrderHeader;

class SalesmanService
{
    public function all(int $perPage = 50, array $filters = [], bool $dropdown = false)
    {
        $user = auth()->user();

        $query = Salesman::with([
            'route:id,route_code,route_name',
            'salesmanType:id,salesman_type_code,salesman_type_name'
        ]);

        // ❌ Ensure this does NOT filter by status
        $query = DataAccessHelper::filterSalesmen($query, $user);

        // Priority status (0 or 1)
        $priorityStatus = array_key_exists('status', $filters)
            ? (int) $filters['status']
            : null;

        $query->select(
            $dropdown
                ? ['id', 'name', 'osa_code']
                : [
                    'id',
                    'uuid',
                    'osa_code',
                    'name',
                    'designation',
                    'contact_no',
                    'type',
                    'route_id',
                    'warehouse_id',
                    'status',
                    'reason',
                    'is_block',
                    'forceful_login'
                ]
        );

        // Apply OTHER filters only
        foreach ($filters as $field => $value) {

            if ($field === 'status') {
                continue; // 🔴 NEVER filter status
            }

            if ($value !== null && $value !== '') {

                // ✅ ADD ONLY THIS BLOCK
                if ($field === 'salesman_id') {
                    $ids = is_string($value) && str_contains($value, ',')
                        ? array_map('intval', explode(',', $value))
                        : (array) $value;

                    $query->whereIn('id', array_filter($ids));
                    continue;
                }
                if ($field === 'route_id') {
                    $routeIds = is_string($value) && str_contains($value, ',')
                        ? array_map('intval', explode(',', $value))
                        : (array) $value;

                    $query->whereIn('route_id', array_filter($routeIds));
                    continue;
                }

                if (in_array($field, ['osa_code', 'name', 'designation', 'username'])) {
                    $query->whereRaw(
                        "LOWER({$field}) LIKE ?",
                        ['%' . strtolower($value) . '%']
                    );
                } else {
                    $query->where($field, $value);
                }
            }
        }


        // ✅ STATUS SORTING (THIS WILL WORK)
        if ($priorityStatus !== null) {
            $query->orderByRaw(
                "CASE 
                WHEN status = {$priorityStatus} THEN 0 
                ELSE 1 
             END"
            );
        }

        // Secondary sort
        $query->orderBy('id', 'DESC');

        return $dropdown
            ? $query->get()
            : $query->paginate($perPage);
    }
    // public function all(int $perPage = 50, array $filters = [], bool $dropdown = false)
    // {
    //     $user = auth()->user();
    //     $query = Salesman::with([
    //         'route:id,route_code,route_name',
    //         'salesmanType:id,salesman_type_code,salesman_type_name'
    //         // 'warehouse:id,warehouse_code,warehouse_name'
    //     ])->orderBy('id', 'DESC');
    //     $query = DataAccessHelper::filterSalesmen($query, $user);
    //     if ($dropdown) {
    //         $query->select(['id', 'name', 'osa_code']);
    //     } else {
    //         $query->select([
    //             'id',
    //             'uuid',
    //             'osa_code',
    //             'name',
    //             'designation',
    //             'contact_no',
    //             'type',
    //             'route_id',
    //             'warehouse_id',
    //             'status',
    //             'reason',
    //             'is_block',
    //             'forceful_login'
    //         ]);
    //     }
    //     foreach ($filters as $field => $value) {
    //         if (!empty($value)) {
    //             if (in_array($field, ['osa_code', 'name', 'designation', 'username'])) {
    //                 $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
    //             } else {
    //                 $query->where($field, $value);
    //             }
    //         }
    //     }
    //     if ($dropdown) {
    //         return $query->get();
    //     } else {
    //         return $query->paginate($perPage);
    //     }
    // }
    public function findByUuid(string $uuid)
    {
        $salesman = Salesman::with([
            'route:id,route_code,route_name',
            'warehouse:id,warehouse_code,warehouse_name',
            'salesmanType:id,salesman_type_code,salesman_type_name',
            'subtype:id,osa_code,name'
        ])
            ->where('uuid', $uuid)
            ->first();
        if (!$salesman) {
            throw new ModelNotFoundException("Salesman not found with UUID: {$uuid}");
        }
        return $salesman;
    }
    public function generateCode(): string
    {
        do {
            $last = Salesman::withTrashed()->latest('id')->first();
            $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $osa_code = 'SA' . str_pad($next, 3, '0', STR_PAD_LEFT);
        } while (Salesman::where('osa_code', $osa_code)->exists());
        return $osa_code;
    }
    public function create(array $data): Salesman
    {
        DB::beginTransaction();
        try {
            $data = array_merge($data, [
                'uuid' => $data['uuid'] ?? Str::uuid()->toString(),
                'password' => Hash::make($data['password']),
                'created_user' => Auth::user()->id,
            ]);

            if (empty($data['osa_code'])) {
                $data['osa_code'] = $this->generateCode();
            }
            $salesman = Salesman::create($data);
            DB::commit();
            return $salesman;
        } catch (Throwable $e) {
            DB::rollBack();
            $friendlyMessage = $e instanceof Error ? "Server error occurred." : "Something went wrong, please try again.";
            Log::error('Salesman creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
                'user' => Auth::id(),
            ]);
            throw new \Exception($friendlyMessage, 0, $e);
        }
    }

    // public function updateByUuid(string $uuid, array $data): Salesman
    //     {
    //         DB::beginTransaction();
    //         try {
    //             $salesman = $this->findByUuid($uuid);
    //             $salesman->update($data);
    //             DB::commit();
    //             return $salesman;
    //         } catch (Throwable $e) {
    //             DB::rollBack();
    //             $friendlyMessage = $e instanceof Error ? "Server error occurred." : "Something went wrong, please try again.";
    //             Log::error('Salesman update failed', [
    //                 'error' => $e->getMessage(),
    //                 'uuid'  => $uuid,
    //                 'data'  => $data,
    //             ]);

    //             throw new \Exception($friendlyMessage, 0, $e);
    //         }
    //     }
    public function updateByUuid(string $uuid, array $data): Salesman
    {
        DB::beginTransaction();
        try {
            $salesman = $this->findByUuid($uuid);
            if (isset($data['warehouse_id']) && is_array($data['warehouse_id'])) {
                $data['warehouse_id'] = implode(',', $data['warehouse_id']);
            }
            if (isset($data['password'])) {
                $data['password'] = bcrypt($data['password']);
            }
            $salesman->update($data);
            DB::commit();
            return $salesman;
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Salesman update failed', [
                'error' => $e->getMessage(),
                'uuid'  => $uuid,
                'data'  => $data,
            ]);
            throw new \Exception("Something went wrong while updating.", 0, $e);
        }
    }
    public function deleteByUuid(string $uuid): bool
    {
        DB::beginTransaction();
        try {
            $salesman = $this->findByUuid($uuid);
            $salesman->delete();

            DB::commit();
            return true;
        } catch (Throwable $e) {
            DB::rollBack();
            $friendlyMessage = $e instanceof Error ? "Server error occurred." : "Something went wrong, please try again.";

            Log::error('Salesman delete failed', [
                'error' => $e->getMessage(),
                'uuid'  => $uuid,
            ]);

            throw new \Exception($friendlyMessage, 0, $e);
        }
    }
    public function export(string $format = 'xlsx', ?string $fromDate = null, ?string $toDate = null, ?string $search = null, array $filters = [], array $columns = []): array
    {
        try {
            $allowedFormats = ['xlsx', 'csv'];
            if (!in_array($format, $allowedFormats)) {
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
            }

            $extension = $format === 'csv' ? 'csv' : 'xlsx';
            $fileName  = 'salesmen_export_' . now()->format('Ymd_His') . '.' . $extension;
            $path      = 'salesmenexports/' . $fileName;

            $export = new SalesmanExport($fromDate, $toDate, $search, $filters, $columns);

            if ($format === 'csv') {
                Excel::store($export, $path, 'public', ExcelExcel::CSV);
            } else {
                Excel::store($export, $path, 'public', ExcelExcel::XLSX);
            }

            $appUrl = rtrim(config('app.url'), '/');
            $downloadUrl = $appUrl . '/storage/app/public/' . $path;

            return [
                'status'       => 'success',
                'code'         => 200,
                'message'      => 'Salesmen export generated successfully',
                'download_url' => $downloadUrl
            ];
        } catch (\Throwable $e) {

            return [
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to generate salesmen export',
                'error'   => $e->getMessage()
            ];
        }
    }
    public function updateSalesmenStatus(array $salesmanIds, $status)
    {
        $updated = Salesman::whereIn('id', $salesmanIds)->update(['status' => $status]);
        return $updated > 0;
    }
    public function globalSearch(int $perPage = 50, ?string $keyword = null)
    {
        try {
            $user = auth()->user();
            $query = Salesman::with(['route']);
            if (!empty($keyword)) {
                $query->where(function ($q) use ($keyword) {
                    $searchableFields = [
                        'name',
                        'osa_code',
                        'contact_no',
                        'email',
                        'designation',
                        'status'
                    ];
                    foreach ($searchableFields as $field) {
                        $q->orWhereRaw("CAST({$field} AS TEXT) ILIKE ?", ['%' . $keyword . '%']);
                    }
                    $warehouseIds = Warehouse::where('warehouse_name', 'ILIKE', "%{$keyword}%")
                        ->pluck('id')
                        ->toArray();
                    if (!empty($warehouseIds)) {
                        $q->orWhereIn('warehouse_id', $warehouseIds);
                    }
                    $routeIds = Route::where('route_name', 'ILIKE', "%{$keyword}%")
                        ->pluck('id')
                        ->toArray();
                    if (!empty($routeIds)) {
                        $q->orWhereIn('route_id', $routeIds);
                    }
                });
            }
            $query = DataAccessHelper::filterSalesmen($query, $user);
            return $query->paginate($perPage);
        } catch (\Exception $e) {
            throw new \Exception("Failed to search salesmen: " . $e->getMessage());
        }
    }

    // public function salespersalesman(string $uuid, int $perPage = 50, bool $dropdown = false)
    // {
    //     try {
    //         $salesmanId = Salesman::where('uuid', $uuid)->value('id');

    //         if (!$salesmanId) {
    //             throw new \Exception("Salesman not found for given UUID.");
    //         }

    //         $query = InvoiceHeader::with([
    //             'warehouse:id,warehouse_code,warehouse_name',
    //             'customer:id,osa_code,name',
    //             'route:id,route_code,route_name'
    //         ])->where('salesman_id', $salesmanId)
    //           ->latest();

    //         if ($dropdown) {
    //             return $query->select(['id', 'invoice_number'])->get();
    //         }

    //         return $query->paginate($perPage);

    //     } catch (\Exception $e) {
    //         throw new \Exception("Failed to fetch sales for salesman: " . $e->getMessage());
    //     }
    // }
    // public function salespersalesman(string $uuid, int $perPage = 50, bool $dropdown = false, ?string $from = null, ?string $to = null)
    // {
    //     try {
    //         $salesmanId = Salesman::where('uuid', $uuid)->value('id');
    //         if (!$salesmanId) {
    //             throw new \Exception("Salesman not found for given UUID.");
    //         }
    //         $query = InvoiceHeader::with([
    //             'warehouse:id,warehouse_code,warehouse_name',
    //             'customer:id,osa_code,name',
    //             'route:id,route_code,route_name',
    //             'details.item:id,erp_code,name',
    //             'details.uoms:id,name,osa_code'
    //         ])
    //             ->where('salesman_id', $salesmanId)
    //             ->latest();
    //         if ($from && $to) {
    //             $query->whereBetween('created_at', [
    //                 date('Y-m-d 00:00:00', strtotime($from)),
    //                 date('Y-m-d 23:59:59', strtotime($to))
    //             ]);
    //         } elseif ($from) {
    //             $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($from)));
    //         } elseif ($to) {
    //             $query->whereDate('created_at', '<=', date('Y-m-d', strtotime($to)));
    //         }
    //         if ($dropdown) {
    //             return $query->select(['id', 'invoice_number'])->get();
    //         }
    //         return $query->paginate($perPage);
    //     } catch (\Exception $e) {
    //         throw new \Exception("Failed to fetch sales for salesman: " . $e->getMessage());
    //     }
    // }
    public function salespersalesman(
        string $uuid,
        int $perPage = 50,
        bool $dropdown = false,
        ?string $fromDate = null,
        ?string $toDate = null
    ) {
        try {
            $salesmanId = Salesman::where('uuid', $uuid)->value('id');

            if (!$salesmanId) {
                throw new \Exception("Salesman not found for given UUID.");
            }

            // $from = $fromDate
            //     ? now()->parse($fromDate)->startOfDay()
            //     : now()->startOfMonth();

            // $to = $toDate
            //     ? now()->parse($toDate)->endOfDay()
            //     : now()->endOfMonth();
            $from = $fromDate
                ? \Carbon\Carbon::parse($fromDate)->startOfDay()
                : now()->startOfDay();

            $to = $toDate
                ? \Carbon\Carbon::parse($toDate)->endOfDay()
                : now()->endOfDay();
            $query = InvoiceHeader::with([
                'warehouse:id,warehouse_code,warehouse_name',
                'customer:id,osa_code,name',
                'route:id,route_code,route_name',
                'details.item:id,erp_code,name',
                'details.uoms:id,name,osa_code'
            ])
                ->where('salesman_id', $salesmanId)
                ->whereBetween('invoice_date', [$from, $to])
                ->latest();

            if ($dropdown) {
                return $query->select(['id', 'invoice_number'])->get();
            }

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch sales for salesman: " . $e->getMessage());
        }
    }


    public function exportInvoicesBySalesman(string $uuid, string $format = 'csv')
    {
        try {
            $salesmanId = Salesman::where('uuid', $uuid)->value('id');
            if (!$salesmanId) {
                throw new Exception("Salesman not found for the given UUID.");
            }
            $timestamp = now()->format('Ymd_His');
            $fileName = "salesman_invoices_{$timestamp}.{$format}";
            $filePath = "exports/{$fileName}";
            $writerType = $format === 'xlsx'
                ? \Maatwebsite\Excel\Excel::XLSX
                : \Maatwebsite\Excel\Excel::CSV;
            $success = \Maatwebsite\Excel\Facades\Excel::store(
                new \App\Exports\SalesmanInvoicesExport($salesmanId),
                $filePath,
                'public',
                $writerType
            );
            if (!$success) {
                throw new Exception(strtoupper($format) . ' export failed.');
            }
            $appUrl = rtrim(config('app.url'), '/');
            $downloadUrl = "{$appUrl}/storage/app/public/{$filePath}";
            return [
                'download_url' => $downloadUrl,
            ];
        } catch (Exception $e) {
            throw new Exception("Failed to export invoices: " . $e->getMessage());
        }
    }

    // public function salesmanOrder(string $uuid, int $perPage = 50, bool $dropdown = false)
    // {
    //     try {
    //         $salesmanId = Salesman::where('uuid', $uuid)->value('id');
    //         if (!$salesmanId) {
    //             throw new \Exception("Salesman not found for the given UUID.");
    //         }
    //         $query = \App\Models\Agent_Transaction\OrderHeader::with([
    //             'warehouse:id,warehouse_code,warehouse_name',
    //             'customer:id,osa_code,name',
    //             'route:id,route_code,route_name',
    //             'details:id,header_id,item_id,quantity,unit_price,total_amount'
    //         ])
    //         ->where('salesman_id', $salesmanId)
    //         ->latest();
    //         if ($dropdown) {
    //             return $query->select(['id', 'order_number'])->get();
    //         }
    //         return $query->paginate($perPage);

    //     } catch (\Exception $e) {
    //         throw new \Exception("Failed to fetch orders for salesman: " . $e->getMessage());
    //     }
    // }
    public function salesmanOrder(
        string $uuid,
        int $perPage = 50,
        bool $dropdown = false,
        ?string $from = null,
        ?string $to = null
    ) {
        try {
            $salesmanId = Salesman::where('uuid', $uuid)->value('id');
            if (!$salesmanId) {
                throw new \Exception("Salesman not found for the given UUID.");
            }
            $query = \App\Models\Agent_Transaction\OrderHeader::with([
                'warehouse:id,warehouse_code,warehouse_name',
                'customer:id,osa_code,name',
                'salesman:id,osa_code,name',
                'route:id,route_code,route_name',
                'details:id,header_id,item_id,quantity,item_price,total',
            ])
                ->where('salesman_id', $salesmanId)
                ->latest();
            if ($from && $to) {
                $query->whereBetween('created_at', [
                    date('Y-m-d 00:00:00', strtotime($from)),
                    date('Y-m-d 23:59:59', strtotime($to)),
                ]);
            } elseif ($from) {
                $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($from)));
            } elseif ($to) {
                $query->whereDate('created_at', '<=', date('Y-m-d', strtotime($to)));
            }
            if ($dropdown) {
                return $query->select(['id', 'order_number'])->get();
            }
            return $query->paginate($perPage);
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch orders for salesman: " . $e->getMessage());
        }
    }
    public function exportBySalesmanUuid(string $uuid): string
    {
        $salesman = Salesman::where('uuid', $uuid)->firstOrFail();
        $headers = OrderHeader::with([
            'warehouse:id,warehouse_name',
            'customer:id,name',
            'route:id,route_name',
            'salesman:id,name',
            'country:id,country_name',
            'details.item:id,name',
            'details.uoms:id,name',
            'details.discounts:id,discount_name',
            // 'details.promotion:id,promotion_name',
            // 'details.parent:id,name',
        ])
            ->where('salesman_id', $salesman->id)
            ->get();
        $fileName = 'salesman_orders_' . Str::random(8) . '.xlsx';
        $folder   = 'salesman_order';
        Excel::store(
            new SalesmanOrderExport($headers),
            "{$folder}/{$fileName}",
            'public'
        );
        return asset("storage/{$folder}/{$fileName}");
    }

    public function export_po(string $uuid): string
    {
        $salesman = Salesman::where('uuid', $uuid)->firstOrFail();
        $headers = PoOrderHeader::with([
            'warehouse:id,warehouse_name',
            'customer:id,business_name',
            'salesman:id,name',
            'company:id,company_name',
            'details.item:id,name',
            'details.uom:id,name',
            // 'details.discounts:id,discount_name',
            // 'details.promotion:id,promotion_name',
            // 'details.parent:id,name',
        ])
            ->where('salesman_id', $salesman->id)
            ->get();
        $fileName = 'salesman_Po_' . Str::random(8) . '.xlsx';
        $folder   = 'salesman_Po';
        Excel::store(
            new SalesmanPoExport($headers),
            "{$folder}/{$fileName}",
            'public'
        );
        return asset("storage/{$folder}/{$fileName}");
    }
}
