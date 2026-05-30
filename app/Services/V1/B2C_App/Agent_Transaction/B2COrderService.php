<?php

namespace App\Services\V1\B2C_App\Agent_Transaction;

use App\Models\Agent_Transaction\OrderHeader;
use App\Models\Agent_Transaction\OrderDetail;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Helpers\DataAccessHelper;
use App\Helpers\ApprovalHelper;
use App\Helpers\CommonLocationFilter;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;


class B2COrderService
{

    public function getAll($queryType = null)
    {
        $userId = auth()->id();

        $query = OrderHeader::with([
            'warehouse',
            'route',
            'warehouse.getCompany',
            'country',
            'customer',
            'salesman',
            'createdBy',
            'updatedBy',
            'details.item.itemUoms.uom',
            'details.uom',
            'details.discount',
            'details.promotion',
            'details.parent',
            'details.children'
        ])
            ->where('flag_user', $userId);

        // History Orders
        if ($queryType == 'history') {

            // Completed Orders
            $query->where('order_flag', 3);
        } else {

            $query->whereDate('created_at', now()->toDateString())
                ->where('order_flag', 1);
        }

        $orders = $query
            ->orderBy('created_at', 'desc')
            ->get();

        $orders->transform(function ($order) {

            $workflowRequest = \App\Models\HtappWorkflowRequest::where('process_type', 'order')
                ->where('process_id', $order->id)
                ->latest('id')
                ->first();

            if (!$workflowRequest) {
                return $order;
            }

            $currentStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
                ->orderBy('step_order')
                ->first();

            $totalSteps = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                ->count();

            $completedSteps = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                ->where('status', 'APPROVED')
                ->count();

            $lastApprovedStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                ->where('status', 'APPROVED')
                ->orderByDesc('step_order')
                ->first();

            $order->approval_status = $lastApprovedStep
                ? $lastApprovedStep->message
                : 'Initiated';

            $order->current_step = $currentStep
                ? $currentStep->title
                : null;

            $order->progress = $totalSteps > 0
                ? ($completedSteps . '/' . $totalSteps)
                : null;

            return $order;
        });

        return $orders;
    }

    public function globalFilter(int $perPage, array $filters = [])
    {
        $user = auth()->user();

        $filter = $filters['filter'] ?? [];
        if (!empty($filters['current_page'])) {
            Paginator::currentPageResolver(function () use ($filters) {
                return (int) $filters['current_page'];
            });
        }
        $query = OrderHeader::with([
            'warehouse',
            'route',
            'warehouse.getCompany',
            'country',
            'customer',
            'salesman',
            'createdBy',
            'updatedBy',
            'details.item.itemUoms',
            'details.uom',
            'details.discount',
            'details.promotion',
            'details.parent',
            'details.children'
        ])->latest();

        // ✅ Agent access
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
        // if (!empty($filter['warehouse_id'])) {
        //     $warehouseIds = is_array($filter['warehouse_id'])
        //         ? $filter['warehouse_id']
        //         : explode(',', $filter['warehouse_id']);

        //     $query->whereIn('warehouse_id', array_map('intval', $warehouseIds));
        // }

        if (!empty($filter['route_id'])) {
            $routeIds = is_array($filter['route_id'])
                ? $filter['route_id']
                : explode(',', $filter['route_id']);

            $query->whereIn('route_id', array_map('intval', $routeIds));
        }
        if (!empty($filter['salesman_id'])) {
            $salesmanIds = is_array($filter['salesman_id'])
                ? $filter['salesman_id']
                : explode(',', $filter['salesman_id']);

            $query->whereIn('salesman_id', array_map('intval', $salesmanIds));
        }

        if (!empty($filter['from_date'])) {
            $query->whereDate('created_at', '>=', $filter['from_date']);
        }

        if (!empty($filter['to_date'])) {
            $query->whereDate('created_at', '<=', $filter['to_date']);
        }

        // return $query->paginate($perPage);
        $paginated = $query->paginate($perPage);

        $paginated->getCollection()->transform(function ($item) {
            return ApprovalHelper::attach($item, 'order');
            // pass your process type here
        });

        return $paginated;
    }



    public function getByUuid(string $uuid)
    {
        try {
            $current = OrderHeader::with([
                'warehouse',
                'warehouse.getCompany',
                'route',
                'customer',
                'salesman',
                'createdBy',
                'updatedBy',
                'details' => function ($q) {
                    $q->with([
                        'item',
                        'uom',
                        'discount',
                        'promotion',
                        'children.item',
                        'children.uom',
                        'children.discount',
                        'children.promotion',
                    ]);
                },
            ])->where('uuid', $uuid)->first();
            if (!$current) {
                return null;
            }
            $previousUuid = OrderHeader::where('id', '<', $current->id)
                ->orderBy('id', 'desc')
                ->value('uuid');
            $nextUuid = OrderHeader::where('id', '>', $current->id)
                ->orderBy('id', 'asc')
                ->value('uuid');
            $current->previous_uuid = $previousUuid;
            $current->next_uuid = $nextUuid;
            return $current;
        } catch (\Exception $e) {
            \Log::error('OrderService::getByUuid Error: ' . $e->getMessage());
            return null;
        }
    }

    public function create(array $data): ?OrderHeader
    {
        try {
            DB::beginTransaction();

            if (empty($data['warehouse_id'])) {
                throw new \InvalidArgumentException('Warehouse is required to create an order.');
            }

            if (empty($data['customer_id'])) {
                throw new \InvalidArgumentException('Customer is required to create an order.');
            }

            if (empty($data['delivery_date'])) {
                throw new \InvalidArgumentException('Delivery date is required.');
            }

            if (empty($data['details']) || !is_array($data['details'])) {
                throw new \InvalidArgumentException('At least one order item is required.');
            }

            $warehouse = Warehouse::with('getCompany')
                ->where('id', $data['warehouse_id'])
                ->lockForUpdate()
                ->first();

            if (!$warehouse || !$warehouse->getCompany) {
                throw new \InvalidArgumentException('Invalid warehouse or company.');
            }

            $company = $warehouse->getCompany;

            $groupedItems = collect($data['details'])
                ->groupBy('item_id')
                ->map(fn($rows) => $rows->sum('quantity'));

            // foreach ($groupedItems as $itemId => $totalQty) {

            //     $stock = DB::table('tbl_warehouse_stocks')
            //         ->where('warehouse_id', $data['warehouse_id'])
            //         ->where('item_id', $itemId)
            //         ->lockForUpdate()
            //         ->first();

            //     if (!$stock) {
            //         throw new \Exception('Stock not found for item ' . $itemId);
            //     }

            //     if ($stock->qty < $totalQty) {
            //         throw new \Exception(
            //             'Insufficient stock for item ' . $itemId
            //         );
            //     }
            // }

            $header = OrderHeader::create([
                'order_code'    => $data['order_code'] ?? null,
                'warehouse_id'  => $data['warehouse_id'],
                'delivery_date' => $data['delivery_date'],
                'customer_id'   => $data['customer_id'],
                'comment'       => $data['comment'] ?? null,
                'status'        => $data['status'] ?? 1,
                'currency'      => $company->selling_currency,
                'vat'           => $data['vat'] ?? 0,
                'net_amount'    => $data['net_amount'] ?? 0,
                'total'         => $data['total'] ?? 0,
                // add these
                'flag_order'    => 'B2C Order',
                'flag_user'     => auth()->id(),
            ]);

            foreach ($data['details'] as $detail) {

                $isPromotion = (bool) ($detail['isPrmotion'] ?? false);

                OrderDetail::create([
                    'header_id'      => $header->id,
                    'item_id'        => $detail['item_id'],
                    'uom_id'         => $detail['uom_id'],
                    'status'         => $detail['status'] ?? 1,
                    'is_promotional' => $isPromotion,
                    'gross_total'    => $isPromotion
                        ? $this->valueOrZero($detail, 'gross_total')
                        : $detail['gross_total'] ?? null,
                    'net_total'      => $isPromotion
                        ? $this->valueOrZero($detail, 'net_total')
                        : $detail['net_total'],
                    'total'          => $isPromotion
                        ? $this->valueOrZero($detail, 'total')
                        : $detail['total'],
                    'item_price'     => $isPromotion
                        ? $this->valueOrZero($detail, 'item_price')
                        : $detail['item_price'],
                    'vat'            => $isPromotion
                        ? $this->valueOrZero($detail, 'vat')
                        : $detail['vat'],
                    'quantity'       => $isPromotion
                        ? $this->valueOrZero($detail, 'quantity')
                        : $detail['quantity'],
                ]);
            }
            // foreach ($groupedItems as $itemId => $totalQty) {
            //     DB::table('tbl_warehouse_stocks')
            //         ->where('warehouse_id', $data['warehouse_id'])
            //         ->where('item_id', $itemId)
            //         ->decrement('qty', $totalQty);
            // }

            DB::commit();
            $workflow = DB::table('htapp_workflow_assignments')
                ->where('process_type', 'order')
                ->where('is_active', true)
                ->first();

            if ($workflow) {
                app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)
                    ->startApproval([
                        'workflow_id'  => $workflow->workflow_id,
                        'process_type' => 'order',
                        'process_id'   => $header->id,
                    ]);
            }

            return $header->load('details');
        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        } catch (\Throwable $e) {
            // dd($e);
            DB::rollBack();

            Log::error('OrderService::create failed', [
                'error' => $e->getMessage(),
                'data'  => $data,
            ]);

            throw new \Exception(
                dd($e),
                'Unable to create order at the moment. Please check stock on that item'
            );
        }
    }
    private function valueOrZero(array $data, string $key)
    {
        return array_key_exists($key, $data) && $data[$key] !== null
            ? $data[$key]
            : 0;
    }


    public function getStatistics(array $filters = []): array
    {
        $query = OrderHeader::query();

        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return [
            'total_orders' => $query->count(),
            'total_amount' => (float) $query->sum('total'),
            'total_vat' => (float) $query->sum('vat'),
            'total_discount' => (float) $query->sum('discount'),
            'average_order_value' => (float) $query->avg('total'),
        ];
    }

    public function updateOrdersStatus(array $orderUuids, int $status): bool
    {
        return OrderHeader::whereIn('uuid', $orderUuids)
            ->update(['status' => $status]) > 0;
    }
}
