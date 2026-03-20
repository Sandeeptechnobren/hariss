<?php

namespace App\Services\V1\Agent_transaction;

use App\Models\Agent_Transaction\InvoiceHeader;
use App\Models\Agent_Transaction\InvoiceDetail;
use App\Models\Agent_Transaction\OrderHeader;
use App\Models\ItemUOM;
use App\Models\Agent_Transaction\AgentDeliveryHeaders;
use App\Models\HtappWorkflowRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\Company;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\AgentCustomer;
use App\Helpers\DataAccessHelper;
use App\Helpers\CommonLocationFilter;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Throwable;
use Illuminate\Pagination\Paginator;

class InvoiceService
{
    public function create(array $data): ?InvoiceHeader
    {
        try {
            if (!empty($data['delivery_id'])) {
                $approvalStatus = HtappWorkflowRequest::where('process_id', $data['delivery_id'])
                    ->where('process_type', 'Agent_Delivery_Headers')
                    ->where('status', 'APPROVED')
                    ->exists();

                // if (!$approvalStatus) {
                if ($approvalStatus && $approvalStatus->status !== 'APPROVED') {
                    throw new HttpResponseException(
                        response()->json([
                            'status'  => 'error',
                            'message' => 'Approval pending for the selected delivery'
                        ], 400)
                    );
                }

                $delivery = AgentDeliveryHeaders::where('id', $data['delivery_id'])->first();
                $delivery->update(['status' => 2]);

                $invoiceExists = InvoiceHeader::where('delivery_id', $data['delivery_id'])->exists();

                if ($invoiceExists) {
                    throw new HttpResponseException(
                        response()->json([
                            'status'  => 'error',
                            'message' => 'Invoice already Created for this delivery.'
                        ], 400)
                    );
                }
            }

            DB::beginTransaction();

            $warehouse_data = Warehouse::where('id', $data['warehouse_id'])->first();
            $company_data = Company::where('id', $warehouse_data->company)->first();
            $route_id = AgentCustomer::where('id', $data['customer_id'])->value('route_id');

            if (!empty($data['delivery_id'])) {

                $delivery = AgentDeliveryHeaders::where('id', $data['delivery_id'])->first();

                $delivery->update([
                    'status'   => 2,
                    'route_id' => $route_id
                ]);

                OrderHeader::where('order_code', $delivery->order_code)
                    ->update([
                        'order_flag' => 3,
                        'route_id'   => $route_id
                    ]);
            }

            $header = InvoiceHeader::create([
                'invoice_code'        => $data['invoice_code'] ?? null,
                'warehouse_id'        => $data['warehouse_id'],
                'company_id'          => $warehouse_data->company,
                'currency_name'       => $company_data->selling_currency ?? null,
                'order_id'            => $data['order_id'] ?? null,
                'delivery_id'         => $data['delivery_id'] ?? null,
                'customer_id'         => $data['customer_id'],
                'route_id'            => $route_id,
                'salesman_id'         => $data['salesman_id'] ?? null,
                'latitude'            => $data['latitude'] ?? null,
                'longitude'           => $data['longitude'] ?? null,
                'invoice_mob_number'  => $data['invoice_mob_number'] ?? null,
                'ura_invoice_id'      => $data['ura_invoice_id'] ?? null,
                'ura_invoice_no'      => $data['ura_invoice_no'] ?? null,
                'ura_antifake_code'   => $data['ura_antifake_code'] ?? null,
                'ura_qr_code'         => $data['ura_qr_code'] ?? null,
                'invoice_type'        => $data['invoice_type'] ?? 1,
                'status'              => $data['status'] ?? 1,
                'invoice_date'        => $data['invoice_date'] ?? now()->toDateString(),
                'invoice_time'        => $data['invoice_time'] ?? now()->toTimeString(),
                'gross_total'         => $data['gross_total'] ?? 0,
                'vat'                 => $data['vat'] ?? 0,
                'pre_vat'             => $data['pre_vat'] ?? 0,
                'net_total'           => $data['net_total'] ?? 0,
                'promotion_id'        => $data['promotion_id'] ?? null,
                'discount_id'         => $data['discount_id'] ?? null,
                'discount'            => $data['discount'] ?? 0,
                'promotion_total'     => $data['promotion_total'] ?? 0,
                'total_amount'        => $data['total_amount'] ?? 0,
                'purchaser_name'      => $data['purchaser_name'] ?? null,
                'purchaser_contact'   => $data['purchaser_contact'] ?? null,
            ]);
// dd($data);
            if (!empty($data['details']) && is_array($data['details'])) {

                $grouped = [];

                foreach ($data['details'] as $detail) {

                    $qty = $this->valueOrZero($detail, 'quantity');

                    if ($qty <= 0) {
                        throw new \Exception('Quantity must be greater than zero');
                    }

                    $key = $detail['item_id'] . '_' . $detail['uom'];

                    if (!isset($grouped[$key])) {
                        $grouped[$key] = [
                            'item_id' => $detail['item_id'],
                            'uom'     => $detail['uom'],
                            'qty'     => 0
                        ];
                    }

                    $grouped[$key]['qty'] += $qty;
                }

                $itemIds = array_column($grouped, 'item_id');
                $uomIds  = array_column($grouped, 'uom');

                $itemUoms = ItemUOM::whereIn('item_id', $itemIds)
                    ->whereIn('uom_id', $uomIds)
                    ->get()
                    ->keyBy(function ($row) {
                        return $row->item_id . '_' . $row->uom_id;
                    });
// dd($itemUoms);
                $stocks = WarehouseStock::where('warehouse_id', $data['warehouse_id'])
                    ->whereIn('item_id', $itemIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('item_id');

                foreach ($grouped as $key => $row) {

                    if (!isset($itemUoms[$key])) {
                        throw new \Exception("UOM mapping not found for Item ID {$row['item_id']}");
                    }

                    $upc = $itemUoms[$key]->upc ?? 1;

                    $baseQty = $row['qty'] * $upc;

                    if (!isset($stocks[$row['item_id']])) {
                        throw new \Exception(
                            "Stock not found for Item ID {$row['item_id']} in Warehouse ID {$data['warehouse_id']}"
                        );
                    }

                    $stock = $stocks[$row['item_id']];

                    if ($stock->qty < $baseQty) {
                        throw new \Exception(
                            "Insufficient stock for Item ID {$row['item_id']}. Available: {$stock->qty}, Required: {$baseQty}"
                        );
                    }

                    // dd($baseQty);
                    $stock->qty -= $baseQty;
                    $stock->save();
                }

                foreach ($data['details'] as $detail) {

                    $qty = $this->valueOrZero($detail, 'quantity');

                    InvoiceDetail::create([
                        'header_id'  => $header->id,
                        'item_id'    => $detail['item_id'],
                        'uom'        => $detail['uom'],
                        'quantity'   => $qty,
                        'itemvalue'  => $this->valueOrZero($detail, 'item_price'),
                        'vat'        => $this->valueOrZero($detail, 'vat'),
                        'pre_vat'    => $this->valueOrZero($detail, 'pre_vat'),
                        'net_total'  => $this->valueOrZero($detail, 'net_total'),
                        'item_total' => $this->valueOrZero($detail, 'item_total'),
                        'promotion_id'  => $detail['promotion_id'] ?? null,
                        'parent'        => $detail['parent'] ?? null,
                        'approver_id'   => $detail['approver_id'] ?? null,
                        'approved_date' => $detail['approved_date'] ?? null,
                        'rejected_by'   => $detail['rejected_by'] ?? null,
                        'rm_approver_id' => $detail['rm_approver_id'] ?? null,
                        'rm_reject_id'  => $detail['rm_reject_id'] ?? null,
                        'rmaction_date' => $detail['rmaction_date'] ?? null,
                        'comment_for_rejection' => $detail['comment_for_rejection'] ?? null,
                        'status'        => $detail['status'] ?? 1,
                    ]);
                }
            }

            DB::commit();

            app(\App\Services\V1\Loyality_Management\LoyalityPointService::class)
                ->createFromInvoice($header);

            $workflow = DB::table('htapp_workflow_assignments')
                ->where('process_type', 'Invoice_Header')
                ->where('is_active', true)
                ->first();

            if ($workflow) {
                app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)
                    ->startApproval([
                        'workflow_id'  => $workflow->workflow_id,
                        'process_type' => 'Invoice_Header',
                        'process_id'   => $header->id
                    ]);
            }

            return $header->load('details');
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('InvoiceService::create Error: ' . $e->getMessage());

            throw $e;
        }
    }
    // public function create(array $data): ?InvoiceHeader
    // {
    //     try {

    //         if (!empty($data['delivery_id'])) {
    //             $approvalStatus = HtappWorkflowRequest::where('process_id', $data['delivery_id'])
    //                 ->where('process_type', 'Agent_Delivery_Headers')
    //                 ->where('status', 'APPROVED')
    //                 ->exists();
    //             if (!$approvalStatus) {
    //                 throw new HttpResponseException(
    //                     response()->json([
    //                         'status'  => 'error',
    //                         'message' => 'Approval pending for the selected delivery'
    //                     ], 400)
    //                 );
    //             }
    //             $delivery = AgentDeliveryHeaders::where('id', $data['delivery_id'])->first();
    //             $delivery->update(['status' => 2]);
    //             $invoiceExists = InvoiceHeader::where('delivery_id', $data['delivery_id'])->exists();
    //             if ($invoiceExists) {
    //                 throw new HttpResponseException(
    //                     response()->json([
    //                         'status'  => 'error',
    //                         'message' => 'Invoice already Created for this delivery.'
    //                     ], 400)
    //                 );
    //             }
    //             // OrderHeader::where('order_code', $delivery->order_code)->update(['order_flag' => 3]);
    //         }

    //         DB::beginTransaction();
    //         $warehouse_data = Warehouse::where('id', $data['warehouse_id'])->first();
    //         $company_data = Company::where('id', $warehouse_data->company)->first();
    //         $route_id = AgentCustomer::where('id', $data['customer_id'])->value('route_id');
    //         if (!empty($data['delivery_id'])) {
    //             $delivery = AgentDeliveryHeaders::where('id', $data['delivery_id'])->first();
    //             OrderHeader::where('order_code', $delivery->order_code)
    //                 ->update([
    //                     'order_flag' => 3,
    //                     'route_id'   => $route_id
    //                 ]);
    //         }
    //         $header = InvoiceHeader::create([
    //             'invoice_code'        => $data['invoice_code'] ?? null,
    //             'warehouse_id'        => $data['warehouse_id'],
    //             'company_id'          => $warehouse_data->company,
    //             'currency_name'       => $company_data->selling_currency ?? null,
    //             'order_id'            => $data['order_id'] ?? null,
    //             'delivery_id'         => $data['delivery_id'] ?? null,
    //             'customer_id'         => $data['customer_id'],
    //             'route_id'            => $route_id,
    //             'salesman_id'         => $data['salesman_id'] ?? null,
    //             'latitude'            => $data['latitude'] ?? null,
    //             'longitude'           => $data['longitude'] ?? null,
    //             'invoice_mob_number'  => $data['invoice_mob_number'] ?? null,
    //             'ura_invoice_id'      => $data['ura_invoice_id'] ?? null,
    //             'ura_invoice_no'      => $data['ura_invoice_no'] ?? null,
    //             'ura_antifake_code'   => $data['ura_antifake_code'] ?? null,
    //             'ura_qr_code'         => $data['ura_qr_code'] ?? null,
    //             'invoice_type'        => $data['invoice_type'] ?? 1,
    //             'status'              => $data['status'] ?? 1,
    //             'invoice_date'        => $data['invoice_date'] ?? now()->toDateString(),
    //             'invoice_time'        => $data['invoice_time'] ?? now()->toTimeString(),
    //             'gross_total'         => $data['gross_total'] ?? 0,
    //             'vat'                 => $data['vat'] ?? 0,
    //             'pre_vat'             => $data['pre_vat'] ?? 0,
    //             'net_total'           => $data['net_total'] ?? 0,
    //             'promotion_id'        => $data['promotion_id'] ?? null,
    //             'discount_id'         => $data['discount_id'] ?? null,
    //             'discount'            => $data['discount'] ?? 0,
    //             'promotion_total'     => $data['promotion_total'] ?? 0,
    //             'total_amount'        => $data['total_amount'] ?? 0,
    //             'purchaser_name'      => $data['purchaser_name'] ?? null,
    //             'purchaser_contact'   => $data['purchaser_contact'] ?? null,
    //         ]);
    //         if (!empty($data['details']) && is_array($data['details'])) {
    //             foreach ($data['details'] as $detail) {
    //                 $qty = $this->valueOrZero($detail, 'quantity');
    //                 if ($qty <= 0) {
    //                     throw new \Exception('Quantity must be greater than zero');
    //                 }
    //                 $stock = WarehouseStock::where('warehouse_id', $data['warehouse_id'])
    //                     ->where('item_id', $detail['item_id'])
    //                     ->lockForUpdate()
    //                     ->first();
    //                 if (!$stock) {
    //                     throw new \Exception(
    //                         "Stock not found for Item ID {$detail['item_id']} in Warehouse ID {$data['warehouse_id']}"
    //                     );
    //                 }
    //                 if ($stock->qty < $qty) {
    //                     throw new \Exception(
    //                         "Insufficient stock for Item ID {$detail['item_id']}. Available: {$stock->qty}, Required: {$qty}"
    //                     );
    //                 }
    //                 $stock->qty = $stock->qty - $qty;
    //                 $stock->save();
    //                 InvoiceDetail::create([
    //                     'header_id'  => $header->id,
    //                     'item_id'    => $detail['item_id'],
    //                     'uom'        => $detail['uom'],
    //                     'quantity'   => $qty,
    //                     'itemvalue'  => $this->valueOrZero($detail, 'item_price'),
    //                     'vat'        => $this->valueOrZero($detail, 'vat'),
    //                     'pre_vat'    => $this->valueOrZero($detail, 'pre_vat'),
    //                     'net_total'  => $this->valueOrZero($detail, 'net_total'),
    //                     'item_total' => $this->valueOrZero($detail, 'item_total'),
    //                     'promotion_id'  => $detail['promotion_id'] ?? null,
    //                     'parent'        => $detail['parent'] ?? null,
    //                     'approver_id'   => $detail['approver_id'] ?? null,
    //                     'approved_date' => $detail['approved_date'] ?? null,
    //                     'rejected_by'   => $detail['rejected_by'] ?? null,
    //                     'rm_approver_id' => $detail['rm_approver_id'] ?? null,
    //                     'rm_reject_id'  => $detail['rm_reject_id'] ?? null,
    //                     'rmaction_date' => $detail['rmaction_date'] ?? null,
    //                     'comment_for_rejection' => $detail['comment_for_rejection'] ?? null,
    //                     'status'        => $detail['status'] ?? 1,
    //                 ]);
    //             }
    //         }

    //         DB::commit();

    //         app(\App\Services\V1\Loyality_Management\LoyalityPointService::class)
    //             ->createFromInvoice($header);
    //         // app(\App\Services\V1\Settings\Web\TierService::class)
    //         //     ->calculateCustomerTier($header->customer_id);
    //         $workflow = DB::table('htapp_workflow_assignments')
    //             ->where('process_type', 'Invoice_Header')
    //             ->where('is_active', true)
    //             ->first();
    //         if ($workflow) {
    //             app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)
    //                 ->startApproval([
    //                     'workflow_id'  => $workflow->workflow_id,
    //                     'process_type' => 'Invoice_Header',
    //                     'process_id'   => $header->id
    //                 ]);
    //         }
    //         return $header->load('details');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('InvoiceService::create Error: ' . $e->getMessage());
    //         throw $e;
    //     }
    // }
   
    private function valueOrZero(array $data, string $key)
    {
        return array_key_exists($key, $data) && $data[$key] !== null
            ? $data[$key]
            : 0;
    }

    // public function getAll(int $perPage = 50, array $filters = [], bool $dropdown = false)
    // {
    //     $user = auth()->user();
    //     $query = InvoiceHeader::with([
    //         'warehouse:id,warehouse_code,warehouse_name',
    //         'customer:id,name,osa_code',
    //         'salesman:id,name,osa_code',
    //         'details:item_id,header_id,uom,quantity,itemvalue,vat,pre_vat,net_total,item_total,promotion_id,parent,status',
    //     ]);
    //     $query = DataAccessHelper::filterAgentTransaction($query, $user);
    //     if (!empty($filters['warehouse_id'])) {
    //         $query->where('warehouse_id', $filters['warehouse_id']);
    //     }
    //     if (!empty($filters['customer_id'])) {
    //         $query->where('customer_id', $filters['customer_id']);
    //     }
    //     if (!empty($filters['salesman_id'])) {
    //         $query->where('salesman_id', $filters['salesman_id']);
    //     }
    //     if (!empty($filters['invoice_code'])) {
    //         $query->where('invoice_code', 'LIKE', '%' . $filters['invoice_code'] . '%');
    //     }
    //     if (!empty($filters['from_date'])) {
    //         $query->whereDate('invoice_date', '>=', $filters['from_date']);
    //     }
    //     if (!empty($filters['to_date'])) {
    //         $query->whereDate('invoice_date', '<=', $filters['to_date']);
    //     }
    //     if (!empty($filters['status'])) {
    //         $query->where('status', $filters['status']);
    //     }
    //     $sortBy = $filters['sort_by'] ?? 'invoice_date';
    //     $sortOrder = $filters['sort_order'] ?? 'desc';
    //     $query->orderBy($sortBy, $sortOrder);
    //     if ($dropdown) {
    //         return $query->get()->map(function ($invoice) {
    //             return [
    //                 'id'    => $invoice->id,
    //                 'label' => $invoice->invoice_code,
    //                 'value' => $invoice->id,
    //             ];
    //         });
    //     }
    //     return $query->paginate($perPage);
    // }
    public function getAll(int $perPage = 50, array $filters = [], bool $dropdown = false)
    {
        $user = auth()->user();

        $query = InvoiceHeader::with([
            'warehouse:id,warehouse_code,warehouse_name',
            'customer:id,name,osa_code',
            'salesman:id,name,osa_code',
            'route:id,route_name,route_code',
            'details:item_id,header_id,uom,quantity,itemvalue,vat,pre_vat,net_total,item_total,promotion_id,parent,status',
        ])->latest();

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
        if (!empty($filters['customer_id'])) {

            $customerIds = is_array($filters['customer_id'])
                ? $filters['customer_id']
                : explode(',', $filters['customer_id']);

            $customerIds = array_map('intval', $customerIds);

            $query->whereIn('customer_id', $customerIds);
        }
        if (!empty($filters['salesman_id'])) {

            $salesmanIds = is_array($filters['salesman_id'])
                ? $filters['salesman_id']
                : explode(',', $filters['salesman_id']);

            $salesmanIds = array_map('intval', $salesmanIds);

            $query->whereIn('salesman_id', $salesmanIds);
        }
        if (!empty($filters['invoice_code'])) {
            $query->where('invoice_code', 'LIKE', '%' . $filters['invoice_code'] . '%');
        }
        $fromDate = $filters['from_date'] ?? null;
        $toDate   = $filters['to_date'] ?? null;

        if ($fromDate || $toDate) {

            if ($fromDate) {
                $query->whereDate('invoice_date', '>=', $fromDate);
            }

            if ($toDate) {
                $query->whereDate('invoice_date', '<=', $toDate);
            }
        } else {

            // 👉 Agar filter object NA ho tabhi current date lagao
            if (empty($filters['filter'])) {
                $query->whereDate('invoice_date', Carbon::today());
            }
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $sortBy = $filters['sort_by'] ?? 'invoice_date';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        if ($dropdown) {
            return $query->get()->map(function ($invoice) {
                return [
                    'id'    => $invoice->id,
                    'label' => $invoice->invoice_code,
                    'value' => $invoice->id,
                ];
            });
        }

        $invoices = $query->paginate($perPage);

        $invoices->getCollection()->transform(function ($invoice) {

            $workflowRequest = \App\Models\HtappWorkflowRequest::where('process_type', 'Invoice_Header')
                ->where('process_id', $invoice->id)
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

                $invoice->approval_status = $lastApprovedStep ? $lastApprovedStep->message : 'Initiated';
                $invoice->current_step = $currentStep ? $currentStep->title : null;
                $invoice->progress = $totalSteps > 0 ? ($completedSteps . '/' . $totalSteps) : null;
            } else {
                $invoice->approval_status = null;
                $invoice->current_step = null;
                $invoice->progress = null;
            }

            return $invoice;
        });

        return $invoices;
    }


    public function globalFilter(int $perPage = 50, array $filters = [])
    {
        $user = auth()->user();

        // 🔑 Read everything from filter object
        $filter = $filters['filter'] ?? [];
        if (!empty($filters['current_page'])) {
            Paginator::currentPageResolver(function () use ($filters) {
                return (int) $filters['current_page'];
            });
        }
        $query = InvoiceHeader::with([
            'warehouse:id,warehouse_code,warehouse_name',
            'customer:id,name,osa_code',
            'salesman:id,name,osa_code',
            'details:item_id,header_id,uom,quantity,itemvalue,vat,pre_vat,net_total,item_total,promotion_id,parent,status',
        ])->latest();

        // ✅ Agent-based access
        $query = DataAccessHelper::filterAgentTransaction($query, $user);

        // ✅ Location-based filter (company / region / area / warehouse / route)
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

        // ✅ Warehouse filter (inside filter)
        if (!empty($filter['warehouse_id'])) {
            $warehouseIds = is_array($filter['warehouse_id'])
                ? $filter['warehouse_id']
                : explode(',', $filter['warehouse_id']);

            $query->whereIn('warehouse_id', array_map('intval', $warehouseIds));
        }

        // ✅ Salesman filter (inside filter)
        if (!empty($filter['salesman_id'])) {
            $salesmanIds = is_array($filter['salesman_id'])
                ? $filter['salesman_id']
                : explode(',', $filter['salesman_id']);

            $query->whereIn('salesman_id', array_map('intval', $salesmanIds));
        }

        // ✅ Date range (inside filter)
        if (!empty($filter['from_date'])) {
            $query->whereDate('invoice_date', '>=', $filter['from_date']);
        }

        if (!empty($filter['to_date'])) {
            $query->whereDate('invoice_date', '<=', $filter['to_date']);
        }

        return $query->paginate($perPage);
    }


    // public function getAll(int $perPage = 50, array $filters = [], bool $dropdown = false)
    // {
    //     $query = InvoiceHeader::select([
    //         'id as header_id',
    //         'invoice_code',
    //         'currency_id',
    //         'currency_name',
    //         'company_id',
    //         'order_number',
    //         'delivery_number',
    //         'warehouse_id',
    //         'route_id',
    //         'customer_id',
    //         'salesman_id',
    //         'invoice_date',
    //         'invoice_time',
    //         'invoice_type',
    //         'gross_total',
    //         'vat',
    //         'pre_vat',
    //         'net_total',
    //         'promotion_total',
    //         'discount',
    //         'total_amount',
    //         'status',
    //         'uuid',
    //     ])
    //     ->with([
    //         'warehouse:id,warehouse_code,warehouse_name',
    //         'route:id,route_code,route_name',
    //         'customer:id,osa_code,name',
    //         'salesman:id,osa_code,name',
    //         'details:item_id,header_id,uom,quantity,itemvalue,vat,pre_vat,net_total,item_total,promotion_id,parent,status',
    //     ]);

    //     if (!empty($filters['warehouse_id'])) {
    //         $query->where('warehouse_id', $filters['warehouse_id']);
    //     }
    //     if (!empty($filters['customer_id'])) {
    //         $query->where('customer_id', $filters['customer_id']);
    //     }
    //     if (!empty($filters['salesman_id'])) {
    //         $query->where('salesman_id', $filters['salesman_id']);
    //     }
    //     if (!empty($filters['invoice_code'])) {
    //         $query->where('invoice_code', 'LIKE', '%' . $filters['invoice_code'] . '%');
    //     }
    //     if (!empty($filters['from_date'])) {
    //         $query->whereDate('invoice_date', '>=', $filters['from_date']);
    //     }
    //     if (!empty($filters['to_date'])) {
    //         $query->whereDate('invoice_date', '<=', $filters['to_date']);
    //     }
    //     if (!empty($filters['status'])) {
    //         $query->where('status', $filters['status']);
    //     }

    //     $sortBy = $filters['sort_by'] ?? 'invoice_date';
    //     $sortOrder = $filters['sort_order'] ?? 'desc';

    //     $query->orderBy($sortBy, $sortOrder);

    //     if ($dropdown) {
    //         return $query->get()->map(function ($invoice) {
    //             return [
    //                 'id'    => $invoice->header_id,
    //                 'label' => $invoice->invoice_code,
    //                 'value' => $invoice->header_id,
    //             ];
    //         });
    //     }

    //     return $query->paginate($perPage);
    // }

    public function getByUuid(string $uuid)
    {
        try {
            $current = InvoiceHeader::with([
                'warehouse:id,warehouse_name,warehouse_code,warehouse_manager_contact,warehouse_email,town_village,street,landmark,address,city,tin_no',
                'route:id,route_name,route_code',
                'customer:id,name,osa_code,street',
                'salesman:id,name,osa_code',
                'order:id,order_code',
                'details' => function ($q) {
                    $q->select(
                        'id',
                        'header_id',
                        'item_id',
                        'uom',
                        'quantity',
                        'itemvalue',
                        'vat',
                        'pre_vat',
                        'net_total',
                        'item_total',
                        'promotion_id',
                        'parent',
                        'status'
                    );
                }
            ])->where('uuid', $uuid)->first();

            if (!$current) {
                return null;
            }

            $previousUuid = InvoiceHeader::where('id', '<', $current->id)
                ->orderBy('id', 'desc')
                ->value('uuid');

            $nextUuid = InvoiceHeader::where('id', '>', $current->id)
                ->orderBy('id', 'asc')
                ->value('uuid');

            return [
                'current'  => $current,
                'previous' => $previousUuid,
                'next'     => $nextUuid,
            ];
        } catch (Exception $e) {
            Log::error('InvoiceService::getByUuid Error: ' . $e->getMessage());
            return null;
        }
    }
    public function updateOrdersStatus(array $invoiceUuids, int $status): bool
    {
        return InvoiceHeader::whereIn('uuid', $invoiceUuids)
            ->update(['status' => $status]) > 0;
    }
}
