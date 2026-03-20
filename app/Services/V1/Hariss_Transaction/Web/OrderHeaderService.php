<?php

namespace App\Services\V1\Hariss_transaction\Web;

use App\Models\Hariss_Transaction\Web\HTOrderHeader;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;

use App\Helpers\CommonLocationFilter;
use App\Helpers\DataAccessHelper;

class OrderHeaderService
{
    public function getAll(int $perPage, array $filters = [], bool $dropdown = false)
    {
        $query = HTOrderHeader::latest();
        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('order_code', 'LIKE', "%$search%")
                    ->orWhere('comment', 'LIKE', "%$search%")
                    ->orWhere('status', 'LIKE', "%$search%");
            });
        }

        foreach (
            [
                'customer_id',
                'salesman_id',
                'country_id',
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
                $query->whereDate('created_at', '>=', $fromDate)
                    ->whereDate('created_at', '<=', $toDate);
            } elseif ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            } elseif ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            }
        } else {
            $query->whereDate('created_at', Carbon::today());
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        if ($dropdown) {
            return $query->get()->map(function ($item) {
                return [
                    'id'    => $item->id,
                    'label' => $item->order_code,
                    'value' => $item->id,
                ];
            });
        }
        return $query->paginate($perPage);
    }


    public function getByUuid(string $uuid)
    {
        try {

            $current = HTOrderHeader::with([
                'details' => function ($q) {
                    $q->with(['item', 'uom']);
                },
            ])->where('uuid', $uuid)->first();

            if (!$current) {
                return null;
            }
            $previousUuid = HTOrderHeader::where('id', '<', $current->id)
                ->orderBy('id', 'desc')
                ->value('uuid');

            $nextUuid = HTOrderHeader::where('id', '>', $current->id)
                ->orderBy('id', 'asc')
                ->value('uuid');

            $current->previous_uuid = $previousUuid;
            $current->next_uuid = $nextUuid;

            return $current;
        } catch (\Exception $e) {
            Log::error("OrderHeaderService::getByUuid Error: " . $e->getMessage());
            return null;
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

        $query = HTOrderHeader::latest('id');

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

        // Salesman filter
        if (!empty($filter['salesman_id'])) {

            $salesmanIds = is_array($filter['salesman_id'])
                ? $filter['salesman_id']
                : explode(',', $filter['salesman_id']);

            $query->whereIn('salesman_id', $salesmanIds);
        }

        // Status filter
        if (isset($filter['status'])) {
            $query->where('status', $filter['status']);
        }

        // Order date filter
        if (!empty($filter['from_date'])) {
            $query->whereDate('order_date', '>=', $filter['from_date']);
        }

        if (!empty($filter['to_date'])) {
            $query->whereDate('order_date', '<=', $filter['to_date']);
        }
        return $query->paginate($perPage);
    }
}
