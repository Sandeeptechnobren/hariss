<?php

namespace App\Services\V1\Agent_Transaction;

use App\Models\SalesmanWarehouseHistory;
use Throwable;
use Log;
use Illuminate\Pagination\Paginator;
use App\Helpers\DataAccessHelper;
use App\Helpers\CommonLocationFilter;
use Carbon\Carbon;

class SalesmanWarehouseHistoryService
{
    public function list(int $perPage = 50, array $filters = [])
    {
        try {
            if (!empty($filters['current_page'])) {
                Paginator::currentPageResolver(function () use ($filters) {
                    return (int) $filters['current_page'];
                });
            }
            $query = SalesmanWarehouseHistory::with([
                'salesman:id,sub_type,osa_code,name',
                'salesman.subtype:id,osa_code,name'
            ]);


            // Optional filters
            if (!empty($filters['salesman_id'])) {
                $query->where('salesman_id', $filters['salesman_id']);
            }

            if (!empty($filters['warehouse_id'])) {
                $query->where('warehouse_id', $filters['warehouse_id']);
            }

            if (!empty($filters['manager_id'])) {
                $query->where('manager_id', $filters['manager_id']);
            }

            if (!empty($filters['route_id'])) {
                $query->where('route_id', $filters['route_id']);
            }

            return $query
                ->orderBy('id', 'desc')
                ->paginate($perPage);
        } catch (Throwable $e) {
            // dd($e);
            Log::error('Failed to fetch salesman warehouse history list', [
                'error'   => $e->getMessage(),
                'filters' => $filters,
            ]);

            throw new \Exception('Unable to fetch salesman warehouse history list.');
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

        $query = SalesmanWarehouseHistory::with([
            'warehouse:id,warehouse_code,warehouse_name',
            'salesman:id,name,osa_code',
            'manager:id,name',
            'route:id,route_name',
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

        if (!empty($filter['warehouse_id'])) {
            $warehouseIds = is_array($filter['warehouse_id'])
                ? $filter['warehouse_id']
                : explode(',', $filter['warehouse_id']);

            $query->whereIn('warehouse_id', array_map('intval', $warehouseIds));
        }

        if (!empty($filter['salesman_id'])) {
            $salesmanIds = is_array($filter['salesman_id'])
                ? $filter['salesman_id']
                : explode(',', $filter['salesman_id']);

            $query->whereIn('salesman_id', array_map('intval', $salesmanIds));
        }

        if (!empty($filter['manager_id'])) {
            $managerIds = is_array($filter['manager_id'])
                ? $filter['manager_id']
                : explode(',', $filter['manager_id']);

            $query->whereIn('manager_id', array_map('intval', $managerIds));
        }

        if (!empty($filter['route_id'])) {
            $routeIds = is_array($filter['route_id'])
                ? $filter['route_id']
                : explode(',', $filter['route_id']);

            $query->whereIn('route_id', array_map('intval', $routeIds));
        }
        if (!empty($filter['from_date']) && !empty($filter['to_date'])) {

            $from = Carbon::parse($filter['from_date'])->startOfDay();
            $to   = Carbon::parse($filter['to_date'])->endOfDay();

            $query->whereBetween('created_at', [$from, $to]);
        }

        return $query->paginate($perPage);
    }
}
