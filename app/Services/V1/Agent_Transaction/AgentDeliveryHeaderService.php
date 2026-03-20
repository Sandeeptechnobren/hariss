<?php

namespace App\Services\V1\Agent_Transaction;

use App\Models\Agent_Transaction\AgentDeliveryHeaders;
use App\Models\Agent_Transaction\AgentDeliveryDetails;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;
use App\Models\Agent_Transaction\OrderHeader;
use App\Models\Agent_Transaction\OrderDetail;
use App\Models\HtappWorkflowRequest;
use App\Helpers\DataAccessHelper;
use App\Helpers\CommonLocationFilter;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;

class AgentDeliveryHeaderService
{
    // public function store(array $data)
    // {
    //     DB::beginTransaction();
    //     try {

    //         $deliveryCode = $this->generateDeliveryCode(
    //             array_key_exists('delivery_code', $data) ? $data['delivery_code'] : null
    //         );

    //         $header = AgentDeliveryHeaders::create([
    //             'uuid'          => Str::uuid(),
    //             'delivery_code' => $deliveryCode,
    //             'warehouse_id'  => $data['warehouse_id'] ?? null,
    //             'customer_id'   => $data['customer_id'] ?? null,
    //             'currency'      => $data['currency'] ?? null,
    //             'country_id'    => $data['country_id'] ?? null,
    //             'route_id'      => $data['route_id'] ?? null,
    //             'salesman_id'   => $data['salesman_id'] ?? null,
    //             'gross_total'   => $data['gross_total'] ?? null,
    //             'vat'           => $data['vat'] ?? null,
    //             'discount'      => $data['discount'] ?? null,
    //             'net_amount'    => $data['net_amount'] ?? null,
    //             'total'         => $data['total'] ?? null,
    //             'order_code'    => $data['order_code'] ?? null,
    //             'comment'       => $data['comment'] ?? null,
    //             'status'        => $data['status'] ?? 1,
    //         ]);

    //         foreach ($data['details'] as $detail) {
    //             AgentDeliveryDetails::create([
    //                 'uuid'          => Str::uuid(),
    //                 'header_id'     => $header->id,
    //                 'item_id'       => $detail['item_id'],
    //                 'uom_id'        => $detail['uom_id'],
    //                 'discount_id'   => $detail['discount_id'] ?? null,
    //                 'promotion_id'  => $detail['promotion_id'] ?? null,
    //                 'parent_id'     => $detail['parent_id'] ?? null,
    //                 'item_price'    => $detail['item_price'] ?? null,
    //                 'quantity'      => $detail['quantity'] ?? null,
    //                 'vat'           => $detail['vat'] ?? null,
    //                 'discount'      => $detail['discount'] ?? null,
    //                 'gross_total'   => $detail['gross_total'] ?? null,
    //                 'net_total'     => $detail['net_total'] ?? null,
    //                 'total'         => $detail['total'] ?? null,
    //                 'is_promotional' => $detail['is_promotional'] ?? false,
    //             ]);
    //         }

    //         $orderHeader = OrderHeader::where('order_code', $data['order_code'])->firstOrFail();

    //         $orderHeader->update([
    //             'warehouse_id' => $data['warehouse_id'],
    //             'customer_id'  => $data['customer_id'],
    //             'salesman_id'  => $data['salesman_id'],
    //             'delivery_date' => now(),
    //             'gross_total'  => $data['gross_total'] ?? null,
    //             'vat'          => $data['vat'],
    //             'net_amount'   => $data['net_amount'],
    //             'total'        => $data['total'],
    //             'discount'     => $data['discount'] ?? null,
    //             'status'       => 2, // DELIVERY DONE
    //             'comment'      => $data['comment'],
    //         ]);

    //         OrderDetail::where('header_id', $orderHeader->id)->delete();

    //         foreach ($data['details'] as $detail) {
    //             OrderDetail::create([
    //                 'header_id'    => $orderHeader->id,
    //                 'item_id'      => $detail['item_id'],
    //                 'item_price'   => $detail['item_price'],
    //                 'quantity'     => $detail['quantity'],
    //                 'vat'          => $detail['vat'],
    //                 'uom_id'       => $detail['uom_id'],
    //                 'discount'     => $detail['discount'] ?? null,
    //                 'discount_id'  => $detail['discount_id'] ?? null,
    //                 'gross_total'  => $detail['gross_total'] ?? null,
    //                 'net_total'    => $detail['net_total'],
    //                 'total'        => $detail['total'],
    //             ]);
    //         }

    //         $orderHeader->update(['order_flag' => 2]);


    //         DB::commit();
    //         return $header->load('details');
    //     } catch (Throwable $e) {
    //         DB::rollBack();

    //         Log::error('Delivery creation failed', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //         ]);

    //         return [
    //             'status'  => 'error',
    //             'code'    => 500,
    //             'message' => 'Delivery creation failed: ' . $e->getMessage(),
    //         ];
    //     }
    // }
    // public function store(array $data)
    // {
    //     DB::beginTransaction();

    //     try {

    //         $deliveryCode = $this->generateDeliveryCode(
    //             array_key_exists('delivery_code', $data) ? $data['delivery_code'] : null
    //         );
    //         $order=OrderHeader::where('order_code',$data['order_code'])->firstOrFail();

    //         $approval_status=HtappWorkflowRequest::where('process_type','order')->where('process_id',$order->id)->where('status','APPROVED')->exists();
    //         // if(!$approval_status){
    //         //     return 'Approval pending for this order';
    //         // }
    //         if (!$approval_status) {
    //                 throw new \Exception('Approval pending for this order');
    //             }
    //         dd("pass");
    //         $header = AgentDeliveryHeaders::create([
    //             'uuid'          => Str::uuid(),
    //             'delivery_code' => $deliveryCode,
    //             'warehouse_id'  => $data['warehouse_id'] ?? null,
    //             'customer_id'   => $data['customer_id'] ?? null,
    //             'currency'      => $data['currency'] ?? null,
    //             'country_id'    => $data['country_id'] ?? null,
    //             'route_id'      => $data['route_id'] ?? null,
    //             'salesman_id'   => $data['salesman_id'] ?? null,
    //             'gross_total'   => $data['gross_total'] ?? null,
    //             'vat'           => $data['vat'] ?? null,
    //             'discount'      => $data['discount'] ?? null,
    //             'net_amount'    => $data['net_amount'] ?? null,
    //             'total'         => $data['total'] ?? null,
    //             'order_code'    => $data['order_code'] ?? null,
    //             'comment'       => $data['comment'] ?? null,
    //             'status'        => $data['status'] ?? 1,
    //         ]);

    //         foreach ($data['details'] as $detail) {

    //             AgentDeliveryDetails::create([
    //                 'uuid'      => Str::uuid(),
    //                 'header_id' => $header->id,
    //                 'item_id'   => $detail['item_id'],
    //                 'uom_id'    => $detail['uom_id'],
    //                 'item_price'  => $this->valueOrZero($detail, 'item_price'),
    //                 'quantity'    => $this->valueOrZero($detail, 'quantity'),
    //                 'vat'         => $this->valueOrZero($detail, 'vat'),
    //                 'gross_total' => $this->valueOrZero($detail, 'gross_total'),
    //                 'net_total'   => $this->valueOrZero($detail, 'net_total'),
    //                 'total'       => $this->valueOrZero($detail, 'total'),

    //                 'discount'       => $detail['discount'] ?? null,
    //                 'discount_id'    => $detail['discount_id'] ?? null,
    //                 'promotion_id'   => $detail['promotion_id'] ?? null,
    //                 'parent_id'      => $detail['parent_id'] ?? null,
    //                 'is_promotional' => (bool) ($detail['is_promotional'] ?? false),
    //             ]);
    //         }

    //         $orderHeader = OrderHeader::where('order_code', $data['order_code'])->lockForUpdate()->firstOrFail();

    //         $orderHeader->update([
    //             'warehouse_id'  => $data['warehouse_id'],
    //             'customer_id'   => $data['customer_id'],
    //             'salesman_id'   => $data['salesman_id'],
    //             'delivery_date' => now(),
    //             'gross_total'   => $data['gross_total'] ?? null,
    //             'vat'           => $data['vat'],
    //             'net_amount'    => $data['net_amount'],
    //             'total'         => $data['total'],
    //             'discount'      => $data['discount'] ?? null,
    //             'status'        => 2,
    //             'comment'       => $data['comment'],
    //         ]);

    //         OrderDetail::where('header_id', $orderHeader->id)->delete();

    //         foreach ($data['details'] as $detail) {

    //             OrderDetail::create([
    //                 'header_id' => $orderHeader->id,
    //                 'item_id'   => $detail['item_id'],
    //                 'uom_id'    => $detail['uom_id'],
    //                 'item_price'  => $this->valueOrZero($detail, 'item_price'),
    //                 'quantity'    => $this->valueOrZero($detail, 'quantity'),
    //                 'vat'         => $this->valueOrZero($detail, 'vat'),
    //                 'gross_total' => $this->valueOrZero($detail, 'gross_total'),
    //                 'net_total'   => $this->valueOrZero($detail, 'net_total'),
    //                 'total'       => $this->valueOrZero($detail, 'total'),
    //                 'discount'       => $detail['discount'] ?? null,
    //                 'discount_id'    => $detail['discount_id'] ?? null,
    //                 'is_promotional' => (bool) ($detail['is_promotional'] ?? false),
    //             ]);
    //         }

    //         $orderHeader->update(['order_flag' => 2]);

    //         DB::commit();
    //         $workflow = DB::table('htapp_workflow_assignments')
    //             ->where('process_type', 'Agent_Delivery_Headers')
    //             ->where('is_active', true)
    //             ->first();

    //         if ($workflow) {
    //             app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)
    //                 ->startApproval([
    //                     'workflow_id'  => $workflow->workflow_id,
    //                     'process_type' => 'Agent_Delivery_Headers',
    //                     'process_id'   => $header->id
    //                 ]);
    //         }

    //         return $header->load('details');
    //     } catch (Throwable $e) {

    //         DB::rollBack();

    //         Log::error('Delivery creation failed', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //         ]);

    //         return [
    //             'status'  => 'error',
    //             'code'    => 500,
    //             'message' => 'Delivery creation failed: ' . $e->getMessage(),
    //         ];
    //     }
    // }
    public function store(array $data)
    {
        DB::beginTransaction();

        try {

            $deliveryCode = $this->generateDeliveryCode($data['delivery_code'] ?? null);

            $order = OrderHeader::where('order_code', $data['order_code'])->firstOrFail();

            $approval_status = HtappWorkflowRequest::where('process_type', 'order')
                ->where('process_id', $order->id)
                // ->where('status', 'APPROVED')
                ->first();
                // dd($approval_status);

            if ($approval_status && $approval_status->status !== 'APPROVED') {
                throw new \Exception('Approval pending for this order');
            }

            $header = AgentDeliveryHeaders::create([
                'uuid'          => Str::uuid(),
                'delivery_code' => $deliveryCode,
                'warehouse_id'  => $data['warehouse_id'] ?? null,
                'customer_id'   => $data['customer_id'] ?? null,
                'currency'      => $data['currency'] ?? null,
                'country_id'    => $data['country_id'] ?? null,
                'route_id'      => $data['route_id'] ?? null,
                'salesman_id'   => $data['salesman_id'] ?? null,
                'gross_total'   => $data['gross_total'] ?? null,
                'vat'           => $data['vat'] ?? null,
                'discount'      => $data['discount'] ?? null,
                'net_amount'    => $data['net_amount'] ?? null,
                'total'         => $data['total'] ?? null,
                'order_code'    => $data['order_code'],
                'comment'       => $data['comment'] ?? null,
                'status'        => $data['status'] ?? 1,
            ]);
            // $order->update(['order_flag' => 2]);


            foreach ($data['details'] as $detail) {
                AgentDeliveryDetails::create([
                    'uuid'        => Str::uuid(),
                    'header_id'   => $header->id,
                    'item_id'     => $detail['item_id'],
                    'uom_id'      => $detail['uom_id'],
                    'item_price'  => $this->valueOrZero($detail, 'item_price'),
                    'quantity'    => $this->valueOrZero($detail, 'quantity'),
                    'vat'         => $this->valueOrZero($detail, 'vat'),
                    'gross_total' => $this->valueOrZero($detail, 'gross_total'),
                    'net_total'   => $this->valueOrZero($detail, 'net_total'),
                    'total'       => $this->valueOrZero($detail, 'total'),
                    'discount'    => $detail['discount'] ?? null,
                    'discount_id' => $detail['discount_id'] ?? null,
                    'is_promotional' => (bool) ($detail['is_promotional'] ?? false),
                ]);
            }
            // $orderHeader = OrderHeader::where('order_code', $data['order_code'])->lockForUpdate()->firstOrFail();
            $orderHeader = OrderHeader::where('order_code', $data['order_code'])->firstOrFail();

            $orderHeader->update([
                'warehouse_id'  => $data['warehouse_id'],
                'customer_id'   => $data['customer_id'],
                'salesman_id'   => $data['salesman_id'],
                'delivery_date' => now(),
                'gross_total'   => $data['gross_total'] ?? null,
                'vat'           => $data['vat'],
                'net_amount'    => $data['net_amount'],
                'total'         => $data['total'],
                'discount'      => $data['discount'] ?? null,
                'status'     => 2,
                'order_flag'     => 2,
                'comment'       => $data['comment'],
            ]);

            OrderDetail::where('header_id', $orderHeader->id)->delete();

            foreach ($data['details'] as $detail) {
                OrderDetail::create([
                    'header_id'   => $orderHeader->id,
                    'item_id'     => $detail['item_id'],
                    'uom_id'      => $detail['uom_id'],
                    'item_price'  => $this->valueOrZero($detail, 'item_price'),
                    'quantity'    => $this->valueOrZero($detail, 'quantity'),
                    'vat'         => $this->valueOrZero($detail, 'vat'),
                    'gross_total' => $this->valueOrZero($detail, 'gross_total'),
                    'net_total'   => $this->valueOrZero($detail, 'net_total'),
                    'total'       => $this->valueOrZero($detail, 'total'),
                    'discount'    => $detail['discount'] ?? null,
                    'discount_id' => $detail['discount_id'] ?? null,
                    'is_promotional' => (bool) ($detail['is_promotional'] ?? false),
                ]);
            }
            DB::commit();

            $workflow = DB::table('htapp_workflow_assignments')
                ->where('process_type', 'Agent_Delivery_Headers')
                ->where('is_active', true)
                ->first();

            if ($workflow) {
                app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)
                    ->startApproval([
                        'workflow_id'  => $workflow->workflow_id,
                        'process_type' => 'Agent_Delivery_Headers',
                        'process_id'   => $header->id
                    ]);
            }

            return $header->load('details');
        } catch (Throwable $e) {

            DB::rollBack();

            Log::error('Delivery creation failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
    private function valueOrZero(array $data, string $key)
    {
        return array_key_exists($key, $data) && $data[$key] !== null
            ? $data[$key]
            : 0;
    }

    private function generateDeliveryCode(?string $manualCode = null): string
    {
        if (!empty($manualCode)) {
            return $manualCode;
        }
        $prefix = 'DL';
        $last = AgentDeliveryHeaders::where('delivery_code', 'LIKE', "{$prefix}%")
            ->orderByDesc('id')
            ->value('delivery_code');
        if ($last) {
            $number = (int) substr($last, strlen($prefix));
            $next = $number + 1;
        } else {
            $next = 1;
        }
        return $prefix . str_pad($next, 3, '0', STR_PAD_LEFT);
    }
    // public function all($perPage = 50)
    // {
    //     try {
    //         $user = auth()->user();
    //         $query = AgentDeliveryHeaders::with([
    //             'details',
    //             'details.item.itemUoms',
    //             'warehouse:id,warehouse_name,warehouse_code',
    //             'country:id,country_name,country_code',
    //             'route:id,route_name,route_code',
    //         ])->latest();
    //         $query = DataAccessHelper::filterAgentTransaction($query, $user);
    //         return $query->paginate($perPage);
    //     } catch (Throwable $e) {
    //         throw new \Exception("Failed to fetch delivery headers: " . $e->getMessage());
    //     }
    // }
    public function all($perPage = 50)
    {
        try {
            $user = auth()->user();
            $filters = request()->all();
            $query = AgentDeliveryHeaders::with([
                'details',
                'details.item.itemUoms',
                'warehouse:id,warehouse_name,warehouse_code',
                'country:id,country_name,country_code',
                'route:id,route_name,route_code',
            ])->latest();

            if (!empty($filters['warehouse_id']) && !empty($filters['invoice_date'])) {
                $query->where('status', 1);
            }

            $query = DataAccessHelper::filterAgentTransaction($query, $user);
            if (!empty($filters['filter']) && is_array($filters['filter'])) {

                $warehouseIds = CommonLocationFilter::resolveWarehouseIds([
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
            if (!empty($filters['warehouse_id'])) {

                $warehouseIds = is_array($filters['warehouse_id'])
                    ? $filters['warehouse_id']
                    : explode(',', $filters['warehouse_id']);

                $warehouseIds = array_map('intval', $warehouseIds);

                $query->whereIn('warehouse_id', $warehouseIds);
            }
            if (!empty($filters['salesman_id'])) {

                $salesmanIds = is_array($filters['salesman_id'])
                    ? $filters['salesman_id']
                    : explode(',', $filters['salesman_id']);

                $salesmanIds = array_map('intval', $salesmanIds);

                $query->whereIn('salesman_id', $salesmanIds);
            }
            $fromDate = $filters['from_date'] ?? null;
            $toDate   = $filters['to_date'] ?? null;

            if ($fromDate || $toDate) {

                if ($fromDate) {
                    $query->whereDate('created_at', '>=', $fromDate);
                }

                if ($toDate) {
                    $query->whereDate('created_at', '<=', $toDate);
                }
            } else {
                if (empty($filters['filter'])) {
                    $query->whereDate('created_at', Carbon::today());
                }
            }

            $deliveries = $query->paginate($perPage);

            $deliveries->getCollection()->transform(function ($delivery) {

                $workflowRequest = \App\Models\HtappWorkflowRequest::where('process_type', 'Agent_Delivery_Headers')
                    ->where('process_id', $delivery->id)
                    ->orderBy('id', 'DESC')
                    ->first();


                if ($workflowRequest) {

                    $currentStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                        ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
                        ->orderBy('step_order')
                        ->first();
                    $totalSteps = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)->count();
                    $completedSteps = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                        ->where('status', 'APPROVED')
                        ->count();


                    $lastApprovedStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                        ->where('status', 'APPROVED')
                        ->orderBy('step_order', 'DESC')
                        ->first();

                    $delivery->approval_status = $lastApprovedStep ? $lastApprovedStep->message : 'Initiated';
                    $delivery->current_step = $currentStep ? $currentStep->title : null;
                    $delivery->progress = $totalSteps > 0 ? ($completedSteps . '/' . $totalSteps) : null;
                } else {
                    $delivery->approval_status = null;
                    $delivery->current_step = null;
                    $delivery->progress = null;
                }
                return $delivery;
            });

            return $deliveries;
        } catch (Throwable $e) {
            throw new \Exception("Failed to fetch delivery headers: " . $e->getMessage());
        }
    }

    public function globalFilter($perPage = 50)
    {
        try {
            $user = auth()->user();
            $filters = request()->all();
            $filter  = $filters['filter'] ?? [];
            if (!empty($filters['current_page'])) {
                Paginator::currentPageResolver(function () use ($filters) {
                    return (int) $filters['current_page'];
                });
            }
            $query = AgentDeliveryHeaders::with([
                'details',
                'details.item.itemUoms',
                'warehouse:id,warehouse_name,warehouse_code',
                'country:id,country_name,country_code',
                'route:id,route_name,route_code',
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

            return $query->paginate($perPage);
        } catch (Throwable $e) {
            throw new \Exception("Failed to fetch delivery headers: " . $e->getMessage());
        }
    }



    public function findByUuid(string $uuid)
    {
        $current = AgentDeliveryHeaders::with('details')
            ->where('uuid', $uuid)
            ->firstOrFail();
        $previousUuid = AgentDeliveryHeaders::where('id', '<', $current->id)
            ->orderBy('id', 'desc')
            ->value('uuid');
        $nextUuid = AgentDeliveryHeaders::where('id', '>', $current->id)
            ->orderBy('id', 'asc')
            ->value('uuid');
        $current->previous_uuid = $previousUuid;
        $current->next_uuid = $nextUuid;
        return $current;
    }
    public function updateByUuid(string $uuid, array $data)
    {
        DB::beginTransaction();
        try {
            $header = AgentDeliveryHeaders::where('uuid', $uuid)->firstOrFail();
            $header->update([
                'warehouse_id' => $data['warehouse_id'] ?? $header->warehouse_id,
                'customer_id' => $data['customer_id'] ?? $header->customer_id,
                'currency' => $data['currency'] ?? $header->currency,
                'country_id' => $data['country_id'] ?? $header->country_id,
                'route_id' => $data['route_id'] ?? $header->route_id,
                'salesman_id' => $data['salesman_id'] ?? $header->salesman_id,
                'gross_total' => $data['gross_total'] ?? $header->gross_total,
                'vat' => $data['vat'] ?? $header->vat,
                'discount' => $data['discount'] ?? $header->discount,
                'net_amount' => $data['net_amount'] ?? $header->net_amount,
                'total' => $data['total'] ?? $header->total,
                'delivery_date' => $data['delivery_date'] ?? $header->delivery_date,
                'comment' => $data['comment'] ?? $header->comment,
                'status' => $data['status'] ?? $header->status,
            ]);
            if (!empty($data['details']) && is_array($data['details'])) {
                $existingDetailUuids = $header->details()->pluck('uuid')->toArray();
                $updatedUuids = [];
                foreach ($data['details'] as $detail) {
                    if (!empty($detail['uuid'])) {
                        $existingDetail = AgentDeliveryDetails::where('uuid', $detail['uuid'])->first();
                        if ($existingDetail) {
                            $existingDetail->update([
                                'item_id' => $detail['item_id'],
                                'uom_id' => $detail['uom_id'],
                                'discount_id' => $detail['discount_id'] ?? null,
                                'promotion_id' => $detail['promotion_id'] ?? null,
                                'parent_id' => $detail['parent_id'] ?? null,
                                'item_price' => $detail['item_price'] ?? 0,
                                'quantity' => $detail['quantity'] ?? 0,
                                'vat' => $detail['vat'] ?? 0,
                                'discount' => $detail['discount'] ?? 0,
                                'gross_total' => $detail['gross_total'] ?? 0,
                                'net_total' => $detail['net_total'] ?? 0,
                                'total' => $detail['total'] ?? 0,
                                'is_promotional' => $detail['is_promotional'] ?? false,
                            ]);
                            $updatedUuids[] = $detail['uuid'];
                        }
                    } else {
                        $newDetail = AgentDeliveryDetails::create([
                            'uuid' => Str::uuid(),
                            'header_id' => $header->id,
                            'item_id' => $detail['item_id'],
                            'uom_id' => $detail['uom_id'],
                            'discount_id' => $detail['discount_id'] ?? null,
                            'promotion_id' => $detail['promotion_id'] ?? null,
                            'parent_id' => $detail['parent_id'] ?? null,
                            'item_price' => $detail['item_price'] ?? 0,
                            'quantity' => $detail['quantity'] ?? 0,
                            'vat' => $detail['vat'] ?? 0,
                            'discount' => $detail['discount'] ?? 0,
                            'gross_total' => $detail['gross_total'] ?? 0,
                            'net_total' => $detail['net_total'] ?? 0,
                            'total' => $detail['total'] ?? 0,
                            'is_promotional' => $detail['is_promotional'] ?? false,
                        ]);
                        $updatedUuids[] = $newDetail->uuid;
                    }
                }
                $detailsToDelete = array_diff($existingDetailUuids, $updatedUuids);
                if (!empty($detailsToDelete)) {
                    AgentDeliveryDetails::whereIn('uuid', $detailsToDelete)->delete();
                }
            }
            DB::commit();
            return $header->load('details');
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Delivery update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Delivery update failed: ' . $e->getMessage(),
            ];
        }
    }
    public function deleteByUuid(string $uuid): bool
    {
        return DB::transaction(function () use ($uuid) {
            $header = AgentDeliveryHeaders::where('uuid', $uuid)->firstOrFail();
            $header->details()->delete();
            return $header->delete();
        });
    }
}
