<?php

namespace App\Services\V1\Hariss_Transaction\Web;

use App\Models\Hariss_Transaction\Web\HTInvoiceHeader;
use App\Models\Hariss_Transaction\Web\HTInvoiceDetail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use App\Helpers\DataAccessHelper;
use App\Helpers\CommonLocationFilter;


class InvoiceService
{
    public function getAll(int $perPage, array $filters = [], bool $dropdown = false)
    {
        $query = HTInvoiceHeader::latest();

        // 🔍 Global search
        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('invoice_code', 'LIKE', "%$search%")
                    ->orWhere('comment', 'LIKE', "%$search%")
                    ->orWhere('status', 'LIKE', "%$search%");
            });
        }

        // 🔎 Standard filters
        foreach (
            [
                'customer_id',
                'salesman_id',
                'status'
            ] as $field
        ) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        $fromDate = !empty($filters['from_date'])
            ? Carbon::parse($filters['from_date'])->toDateString()
            : null;

        $toDate = !empty($filters['to_date'])
            ? Carbon::parse($filters['to_date'])->toDateString()
            : null;

        if ($fromDate || $toDate) {

            if ($fromDate && $toDate) {
                $query->whereDate('invoice_date', '>=', $fromDate)
                    ->whereDate('invoice_date', '<=', $toDate);
            } elseif ($fromDate) {
                $query->whereDate('invoice_date', '>=', $fromDate);
            } elseif ($toDate) {
                $query->whereDate('invoice_date', '<=', $toDate);
            }
        } else {
            $query->whereDate('invoice_date', Carbon::today());
        }

        // 🔄 Sorting
        $sortBy    = $filters['sort_by'] ?? 'invoice_date';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // 🔻 Dropdown mode
        if ($dropdown) {
            return $query->get()->map(function ($item) {
                return [
                    'id'    => $item->id,
                    'label' => $item->invoice_code,
                    'value' => $item->id,
                ];
            });
        }

        return $query->paginate($perPage);
    }


    public function getByUuid(string $uuid)
    {
        try {

            $current = HTInvoiceHeader::with([
                'details.item',
                'details.itemuom'
            ])->where('uuid', $uuid)->first();

            if (!$current) {
                return null;
            }
            $previousUuid = HTInvoiceHeader::where('id', '<', $current->id)
                ->orderBy('id', 'desc')
                ->value('uuid');

            $nextUuid = HTInvoiceHeader::where('id', '>', $current->id)
                ->orderBy('id', 'asc')
                ->value('uuid');

            $current->previous_uuid = $previousUuid;
            $current->next_uuid = $nextUuid;

            return $current;
        } catch (\Exception $e) {
            Log::error("InvoiceService::getByUuid Error: " . $e->getMessage());
            return null;
        }
    }



    //     public function filterInvoiceDetails(array $filters, int $perPage = 50)
    //     {
    //         // dd($filters);
    //         if (!empty($filters['current_page'])) {
    //             Paginator::currentPageResolver(function () use ($filters) {
    //                 return (int) $filters['current_page'];
    //             });
    //         }
    //         $from = $filters['from_date'];
    //         $to   = $filters['to_date'];

    //         // Step 1: Get header IDs between date range
    //         $headerIds = DB::table('ht_invoice_header')
    //             ->whereBetween('invoice_date', [$from, $to])
    //             ->pluck('id')
    //             ->toArray();

    //         if (empty($headerIds)) {
    //             return [
    //                 "compiled_exists" => false,
    //                 "data" => collect([]) // no data found
    //             ];
    //         }

    //         // Allowed categories
    //         $allowedCategories = ['ZBAC', 'ZCAC', 'ZFAC', 'ZPAC', 'ZDAC', 'ZSAC', 'ZKAC'];

    //         // Step 2: Build main SQL for filtered invoice details
    //         $sql = "
    //     SELECT 
    //         h.id AS header_id,
    //         h.sap_id,
    //         w.warehouse_code,
    //         w.warehouse_name,
    //         h.invoice_code,
    //         h.invoice_date,
    //         d.item_category_dll,
    //         i.name,
    //         i.erp_code,
    //         d.quantity,
    //         ROUND(FLOOR(i.base_uom_vol * 10) / 10, 1) AS base_uom_vol_calc,
    //         ROUND(FLOOR(i.alter_base_uom_vol * 10) / 10, 1) AS alter_base_uom_vol_calc,
    //         ROUND(FLOOR(i.alter_base_uom_vol * quantity)) AS total_amount

    //     FROM ht_invoice_detail d
    //     LEFT JOIN ht_invoice_header h ON h.id = d.header_id
    //     LEFT JOIN tbl_warehouse w ON w.id = h.warehouse_id
    //     LEFT JOIN items i ON i.id = d.item_id

    //     WHERE d.header_id IN (" . implode(",", $headerIds) . ")
    //       AND d.item_category_dll IN ('ZBAC','ZCAC','ZFAC','ZPAC','ZDAC','ZSAC','ZKAC')

    //     GROUP BY 
    //         d.id,
    //         h.id,
    //         h.sap_id,
    //         w.warehouse_code,
    //         w.warehouse_name,
    //         h.invoice_code,
    //         h.invoice_date,
    //         d.item_category_dll,
    //         d.quantity,
    //         i.name,
    //         i.erp_code,
    //         i.base_uom_vol,
    //         i.alter_base_uom_vol

    //     ORDER BY h.id DESC
    // ";

    //         // dd($sql);
    //         $data = DB::table(DB::raw("($sql) AS subquery"))->paginate($perPage);

    //         return [
    //             "compiled_exists" => false,
    //             "data" => $data
    //         ];
    //     }


    public function filterInvoiceDetails(array $filters, int $perPage = 50)
    {
        $page = $filters['current_page'] ?? 1;

        $from = $filters['from_date'];
        $to   = $filters['to_date'];

        $headerIds = DB::table('ht_invoice_header')
            ->whereBetween('invoice_date', [$from, $to])
            ->pluck('id')
            ->toArray();

        /* ❗ FIX: NEVER RETURN COLLECTION */
        if (empty($headerIds)) {
            return [
                "compiled_exists" => false,
                "data" => new LengthAwarePaginator(
                    [],     // items
                    0,      // total
                    $perPage,
                    $page,
                    [
                        'path'  => request()->url(),
                        'query' => request()->query()
                    ]
                )
            ];
        }

        $sql = "
        SELECT 
            h.id AS header_id,
            h.sap_id,
            w.warehouse_code,
            w.warehouse_name,
            h.invoice_code,
            h.invoice_date,
            d.item_category_dll,
            i.name,
            i.erp_code,
            d.quantity,
            ROUND(FLOOR(i.base_uom_vol * 10) / 10, 1) AS base_uom_vol_calc,
            ROUND(FLOOR(i.alter_base_uom_vol * 10) / 10, 1) AS alter_base_uom_vol_calc,
            ROUND(FLOOR(i.alter_base_uom_vol * quantity)) AS total_amount
        FROM ht_invoice_detail d
        LEFT JOIN ht_invoice_header h ON h.id = d.header_id
        LEFT JOIN tbl_warehouse w ON w.id = h.warehouse_id
        LEFT JOIN items i ON i.id = d.item_id
        WHERE d.header_id IN (" . implode(",", $headerIds) . ")
          AND d.item_category_dll IN ('ZBAC','ZCAC','ZFAC','ZPAC','ZDAC','ZSAC','ZKAC')
        GROUP BY 
            d.id,
            h.id,
            h.sap_id,
            w.warehouse_code,
            w.warehouse_name,
            h.invoice_code,
            h.invoice_date,
            d.item_category_dll,
            d.quantity,
            i.name,
            i.erp_code,
            i.base_uom_vol,
            i.alter_base_uom_vol
        ORDER BY h.id DESC
    ";

        $data = DB::table(DB::raw("($sql) AS subquery"))
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            "compiled_exists" => false,
            "data" => $data
        ];
    }
    public function exportInvoiceDetails(array $filters, int $perPage = 50)
    {
        $from = $filters['from_date'];
        $to   = $filters['to_date'];

        // Step 1: Get header IDs between date range
        $headerIds = DB::table('ht_invoice_header')
            ->whereBetween('invoice_date', [$from, $to])
            ->pluck('id')
            ->toArray();

        if (empty($headerIds)) {
            return [
                "compiled_exists" => false,
                "data" => collect([]) // empty result
            ];
        }

        // Step 2: Build SQL
        $sql = "
        SELECT 
            h.id AS header_id,
            h.sap_id,
            w.warehouse_code,
            w.warehouse_name,
            h.invoice_code,
            h.invoice_date,
            d.item_category_dll,
            i.name,
            i.erp_code,
            d.quantity,

            ROUND(FLOOR(i.base_uom_vol * 10) / 10, 1) AS base_uom_vol_calc,
            ROUND(FLOOR(i.alter_base_uom_vol * 10) / 10, 1) AS alter_base_uom_vol_calc,

            ROUND(FLOOR(i.alter_base_uom_vol * d.quantity)) AS total_amount

        FROM ht_invoice_detail d
        LEFT JOIN ht_invoice_header h ON h.id = d.header_id
        LEFT JOIN tbl_warehouse w     ON w.id = h.warehouse_id
        LEFT JOIN items i             ON i.id = d.item_id
        
        WHERE d.header_id IN (" . implode(",", $headerIds) . ")
          AND d.item_category_dll IN ('ZBAC','ZCAC','ZFAC','ZPAC','ZDAC','ZSAC','ZKAC')

        GROUP BY 
            d.id,
            h.id,
            h.sap_id,
            w.warehouse_code,
            w.warehouse_name,
            h.invoice_code,
            h.invoice_date,
            d.item_category_dll,
            d.quantity,
            i.name,
            i.erp_code,
            i.base_uom_vol,
            i.alter_base_uom_vol

        ORDER BY h.id DESC
    ";

        // Step 3: Pagination OR full collection (for export)
        if (isset($filters["for_export"]) && $filters["for_export"] === true) {

            // ❗ FIX APPLIED HERE: Use pure SQL string
            $data = DB::select($sql);

            return [
                "compiled_exists" => false,
                "data" => collect($data)
            ];
        }

        // Normal paginated response (this stays same)
        $data = DB::table(DB::raw("($sql) AS subquery"))->paginate($perPage);

        return [
            "compiled_exists" => false,
            "data" => $data
        ];
    }

    public function globalFilter(int $perPage = 50, array $filters = [])
    {
        $user = auth()->user();

        $filter = $filters['filter'] ?? [];

        if (!empty($filters['current_page'])) {
            Paginator::currentPageResolver(function () use ($filters) {
                return (int) $filters['current_page'];
            });
        }

        $query = HTInvoiceHeader::latest('id');

        // Agent access
        $query = DataAccessHelper::filterAgentTransaction($query, $user);

        // Location filter (company → region → area → warehouse → route)
        if (!empty($filter)) {

            $warehouseIds = CommonLocationFilter::resolveWarehouseIds([
                'company_id'   => $filter['company_id'] ?? null,
                'region_id'    => $filter['region_id'] ?? null,
                'area_id'      => $filter['area_id'] ?? null,
                'warehouse_id' => $filter['warehouse_id'] ?? null,
                'route_id'     => $filter['route_id'] ?? null,
            ]);

            if (!empty($warehouseIds)) {

                $warehouseIds = is_array($warehouseIds)
                    ? $warehouseIds
                    : explode(',', $warehouseIds);

                $query->whereIn('warehouse_id', $warehouseIds);
            }
        }

        // Customer filter
        // Customer filter
        if (!empty($filter['customer_id'])) {

            $customerIds = is_array($filter['customer_id'])
                ? $filter['customer_id']
                : explode(',', $filter['customer_id']);

            $query->whereIn('customer_id', $customerIds);
        }

        // Salesman filter
        if (!empty($filter['salesman_id'])) {

            $salesmanIds = is_array($filter['salesman_id'])
                ? $filter['salesman_id']
                : explode(',', $filter['salesman_id']);

            $query->whereIn('salesman_id', $salesmanIds);
        }

        // Status filter
        if (isset($filter['status'])) {

            $statusIds = is_array($filter['status'])
                ? $filter['status']
                : explode(',', $filter['status']);

            $query->whereIn('status', $statusIds);
        }

        // Delivery date range
        if (!empty($filter['from_date'])) {
            $query->whereDate('invoice_date', '>=', $filter['from_date']);
        }

        if (!empty($filter['to_date'])) {
            $query->whereDate('invoice_date', '<=', $filter['to_date']);
        }

        return $query->paginate($perPage);
    }
}
