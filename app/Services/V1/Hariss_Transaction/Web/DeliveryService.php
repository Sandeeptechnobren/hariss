<?php

namespace App\Services\V1\Hariss_Transaction\Web;

use App\Models\Hariss_Transaction\Web\HTDeliveryHeader;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Helpers\DataAccessHelper;
use App\Helpers\CommonLocationFilter;

class DeliveryService
{
    public function getAll(int $perPage, array $filters = [], bool $dropdown = false)
    {
        $query = HTDeliveryHeader::latest();

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('delivery_code', 'LIKE', "%$search%")
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
            if (isset($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        $fromDate = !empty($filters['from_date'])
            ? Carbon::parse($filters['from_date'])->toDateString()
            : now()->toDateString(); // ✅ default today

        $toDate = !empty($filters['to_date'])
            ? Carbon::parse($filters['to_date'])->toDateString()
            : now()->toDateString(); // ✅ default today

        if ($fromDate || $toDate) {

            if ($fromDate && $toDate) {
                $query->whereDate('created_at', '>=', $fromDate)
                    ->whereDate('created_at', '<=', $toDate);
            } elseif ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            } elseif ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            }
        }
        $sortBy    = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        if ($dropdown) {
            return $query->get()->map(function ($item) {
                return [
                    'id'    => $item->id,
                    'label' => $item->delivery_code,
                    'value' => $item->id,
                ];
            });
        }

        return $query->paginate($perPage);
    }


    public function getByUuid(string $uuid)
    {
        try {

            $current = HTDeliveryHeader::with([
                'details.item',
                'details.itemuom'
            ])->where('uuid', $uuid)->first();

            if (!$current) {
                return null;
            }
            $previousUuid = HTDeliveryHeader::where('id', '<', $current->id)
                ->orderBy('id', 'desc')
                ->value('uuid');

            $nextUuid = HTDeliveryHeader::where('id', '>', $current->id)
                ->orderBy('id', 'asc')
                ->value('uuid');

            $current->previous_uuid = $previousUuid;
            $current->next_uuid = $nextUuid;

            return $current;
        } catch (\Exception $e) {
            Log::error("DeliveryService::getByUuid Error: " . $e->getMessage());
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

        $query = HTDeliveryHeader::latest('id');

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
        if (!empty($filter['customer_id'])) {
            $query->where('customer_id', $filter['customer_id']);
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

        // Delivery date range
        if (!empty($filter['from_date'])) {
            $query->whereDate('delivery_date', '>=', $filter['from_date']);
        }

        if (!empty($filter['to_date'])) {
            $query->whereDate('delivery_date', '<=', $filter['to_date']);
        }

        return $query->paginate($perPage);
    }
}
