<?php

namespace App\Services\V1\Agent_Transaction;

use App\Models\WarehouseStock;

use App\Models\Agent_Transaction\StockAuditHeader;
use App\Models\Agent_Transaction\StockAuditDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Helpers\DataAccessHelper;
use App\Helpers\CommonLocationFilter;
use Throwable;
use Illuminate\Pagination\Paginator;

class StockAuditService
{
    public function getStock(array $filters)
    {
        $query = WarehouseStock::query()
            ->select('id', 'osa_code', 'warehouse_id', 'item_id', 'qty')
            ->with([
                'item:id,name,erp_code',
                'item.itemUoms:id,item_id,uom_id,name'
            ])
            ->where('warehouse_id', $filters['warehouse']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->whereHas('item', function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('erp_code', 'ILIKE', "%{$search}%");
            });
        }

        return $query->latest()->get();
    }


    public function store(array $data)
    {
        DB::beginTransaction();

        try {

            $header = StockAuditHeader::create([
                'uuid'                  => Str::uuid(),
                'osa_code'              => $data['osa_code'],
                'warehouse_id'          => $data['warehouse_id'],
                'auditer_name'          => $data['auditer_name'],
                'case_otc_invoice'      => $data['case_otc_invoice'] ?? null,
                'otc_invoice'           => $data['otc_invoice'] ?? null,
                'negative_balance_date' => $data['negative_balance_date'] ?? null,
            ]);

            $details = collect($data['items'])->map(function ($item) use ($header) {
                return [
                    'uuid'             => Str::uuid(),
                    'header_id'        => $header->id,
                    'item_id'          => $item['item_id'],
                    'uom_id'           => $item['uom_id'] ?? null,
                    'warehouse_stock'  => $item['warehouse_stock'] ?? 0,
                    'physical_stock'   => $item['physical_stock'] ?? 0,
                    'variance'         => $item['variance'] ?? 0,
                    'saleon_otc'       => $item['saleon_otc'] ?? 0,
                    'remarks'          => $item['remarks'] ?? null,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ];
            })->toArray();

            StockAuditDetail::insert($details);

            DB::commit();

            return $header->load('details');
        } catch (Throwable $e) {

            // ❌ Rollback
            DB::rollBack();

            // ✅ Log Error (Very Important)
            Log::error('StockAudit Store Failed', [
                'error'   => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
                'data'    => $data
            ]);

            // ❌ Throw Clean Exception
            throw new \Exception(
                config('app.debug')
                    ? $e->getMessage()
                    : 'Failed to create Stock Audit',
                0,
                $e
            );
        }
    }

    public function getAll(int $perPage = 10, array $filters = [])
    {
        try {
            $query = StockAuditHeader::query()
                ->with([
                    'details:id,header_id,item_id,warehouse_stock,physical_stock,variance',
                    'details.item:id,name,erp_code',
                    'details.item.primaryUom:id,item_id,uom_id,name'
                ]);

            // ✅ Warehouse filter
            if (!empty($filters['warehouse_id'])) {
                $query->where('warehouse_id', $filters['warehouse_id']);
            }

            // ✅ Search (auditer_name)
            if (!empty($filters['search'])) {
                $search = strtolower($filters['search']);

                $query->whereRaw("LOWER(auditer_name) LIKE ?", ["%{$search}%"]);
            }

            // ✅ Date range (safe handling)
            if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
                $query->whereBetween('created_at', [
                    $filters['from_date'],
                    $filters['to_date']
                ]);
            }

            // ✅ Sorting
            $query->orderBy('created_at', 'desc');

            // ✅ Pagination
            return $query->paginate($perPage);
        } catch (Throwable $e) {

            Log::error("StockAudit fetch failed", [
                'error'   => $e->getMessage(),
                'filters' => $filters
            ]);

            throw new \Exception("Failed to fetch Stock Audit list", 0, $e);
        }
    }

    public function getByUuid(string $uuid)
    {
        try {
            $record = StockAuditHeader::query()
                ->with([
                    'details:id,header_id,item_id,warehouse_stock,physical_stock,variance,remarks',
                    'details.item:id,name,erp_code',
                    'details.item.primaryUom:id,item_id,uom_id,name'
                ])

                ->where('uuid', $uuid)
                ->first();

            if (!$record) {
                throw new \Exception("Stock Audit not found");
            }

            return $record;
        } catch (\Throwable $e) {

            \Log::error("StockAudit getByUuid failed", [
                'uuid'  => $uuid,
                'error' => $e->getMessage()
            ]);

            throw new \Exception("Failed to fetch Stock Audit", 0, $e);
        }
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
        // $query = InvoiceHeader::with([
        //     'warehouse:id,warehouse_code,warehouse_name',
        //     'customer:id,name,osa_code',
        //     'salesman:id,name,osa_code',
        //     'details:item_id,header_id,uom,quantity,itemvalue,vat,pre_vat,net_total,item_total,promotion_id,parent,status',
        // ])->latest();

        $query = StockAuditHeader::query()
            ->with([
                'details:id,header_id,item_id,warehouse_stock,physical_stock,variance,remarks',
                'details.item:id,name,erp_code',
                'details.item.primaryUom:id,item_id,uom_id,name'
            ])->latest();
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
                $query->whereIn('warehouse_id', $warehouseIds);
            }
        }

        if (!empty($filter['from_date'])) {
            $query->whereDate('created_at', '>=', $filter['from_date']);
        }

        if (!empty($filter['to_date'])) {
            $query->whereDate('created_at', '<=', $filter['to_date']);
        }

        return $query->paginate($perPage);
    }
}
