<?php

namespace App\Services\V1\Agent_transaction;

use App\Models\Agent_Transaction\ReturnHeader;
use App\Models\Agent_Transaction\InvoiceHeader;
use App\Models\Agent_Transaction\ReturnDetail;
use App\Models\Agent_Transaction\InvoiceDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Helpers\DataAccessHelper;
use App\Helpers\CommonLocationFilter;
use App\Models\WarehouseStock;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;

class ReturnService
{
    // public function create(array $data): ?ReturnHeader
    // {
    //     try {
    //         DB::beginTransaction();
    //         $header = ReturnHeader::create([
    //             'osa_code'     => $data['osa_code'] ?? null,
    //             'currency'     => $data['currency'] ?? null,
    //             'country_id'   => $data['country_id'] ?? null,
    //             'order_id'     => $data['order_id'] ?? null,
    //             'delivery_id'  => $data['delivery_id'] ?? null,
    //             'warehouse_id' => $data['warehouse_id'],
    //             'route_id'     => $data['route_id'] ?? null,
    //             'customer_id'  => $data['customer_id'],
    //             'salesman_id'  => $data['salesman_id'] ?? null,
    //             'gross_total'  => $data['gross_total'] ?? 0,
    //             'vat'          => $data['vat'] ?? 0,
    //             'net_amount'   => $data['net_amount'] ?? 0,
    //             'total'        => $data['total'] ?? 0,
    //             'discount'     => $data['discount'] ?? 0,
    //             'status'       => $data['status'] ?? 1,
    //         ]);
    //         if (!empty($data['details']) && is_array($data['details'])) {
    //             foreach ($data['details'] as $detail) {
    //                 ReturnDetail::create([
    //                     'header_id'     => $header->id,
    //                     'header_code'   => $header->osa_code,
    //                     'item_id'       => $detail['item_id'],
    //                     'uom_id'        => $detail['uom_id'],
    //                     'discount_id'   => $detail['discount_id'] ?? null,
    //                     'promotion_id'  => $detail['promotion_id'] ?? null,
    //                     'parent_id'     => $detail['parent_id'] ?? null,
    //                     'item_price'    => $detail['item_price'] ?? 0,
    //                     'item_quantity' => $detail['item_quantity'] ?? 0,
    //                     'return_type'   => $detail['return_type'] ?? 0,
    //                     'return_reason' => $detail['return_reason'] ?? null,
    //                     'vat'           => $detail['vat'] ?? 0,
    //                     'discount'      => $detail['discount'] ?? 0,
    //                     'gross_total'   => $detail['gross_total'] ?? 0,
    //                     'net_total'     => $detail['net_total'] ?? 0,
    //                     'total'         => $detail['total'] ?? 0,
    //                     'is_promotional' => $detail['is_promotional'] ?? false,
    //                     'status'        => $detail['status'] ?? 1,
    //                 ]);
    //             }
    //         }

    //         DB::commit();

    //         return $header->load('details');
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         Log::error('ReturnService::create Error: ' . $e->getMessage());
    //         throw $e;
    //     }
    // }
    public function create(array $data): ?ReturnHeader
    {
        try {
            DB::beginTransaction();

            $requestedReturns = collect($data['details'])
                ->groupBy('item_id')
                ->map(fn($items) => $items->sum('item_quantity'));

            foreach ($requestedReturns as $itemId => $reqQty) {

                $invoiceQty = InvoiceDetail::where('header_id', $data['invoice_id'])
                    ->where('item_id', $itemId)
                    ->sum('quantity');

                if ($invoiceQty <= 0) {
                    throw new \Exception("Item {$itemId} does not exist in invoice {$data['invoice_id']}");
                }

                $alreadyReturned = ReturnDetail::whereIn('header_id', function ($query) use ($data) {
                    $query->select('id')
                        ->from('return_header')
                        ->where('invoice_id', $data['invoice_id']);
                })
                    ->where('item_id', $itemId)
                    ->sum('item_quantity');
                // dd($alreadyReturned + $reqQty);
                if (($alreadyReturned + $reqQty) > $invoiceQty) {
                    throw new \Exception(
                        "Return quantity exceeds invoice quantity for item {$itemId}"
                    );
                }
            }

            $header = ReturnHeader::create([
                'osa_code'     => $data['osa_code'] ?? null,
                'currency'     => $data['currency'] ?? null,
                'country_id'   => $data['country_id'] ?? null,
                'order_id'     => $data['order_id'] ?? null,
                'invoice_id'   => $data['invoice_id'] ?? null,
                'delivery_id'  => $data['delivery_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'],
                'route_id'     => $data['route_id'] ?? null,
                'customer_id'  => $data['customer_id'],
                'salesman_id'  => $data['salesman_id'] ?? null,
                'gross_total'  => $data['gross_total'] ?? 0,
                'vat'          => $data['vat'] ?? 0,
                'net_amount'   => $data['net_amount'] ?? 0,
                'total'        => $data['total'] ?? 0,
                'discount'     => $data['discount'] ?? 0,
                'status'       => $data['status'] ?? 1,
            ]);

            foreach ($data['details'] as $detail) {

                $qty = (float) ($detail['item_quantity'] ?? 0);

                if ($qty <= 0) {
                    throw new \Exception('Item quantity must be greater than zero.');
                }

                ReturnDetail::create([
                    'header_id'      => $header->id,
                    'header_code'    => $header->osa_code,
                    'item_id'        => $detail['item_id'],
                    'uom_id'         => $detail['uom_id'],
                    'discount_id'    => $detail['discount_id'] ?? null,
                    'promotion_id'   => $detail['promotion_id'] ?? null,
                    'parent_id'      => $detail['parent_id'] ?? null,
                    'item_price'     => $detail['item_price'] ?? 0,
                    'item_quantity'  => $qty,
                    'return_type'    => $detail['return_type'] ?? null,
                    'return_reason'  => $detail['return_reason'] ?? null,
                    'vat'            => $detail['vat'] ?? 0,
                    'discount'       => $detail['discount'] ?? 0,
                    'gross_total'    => $detail['gross_total'] ?? 0,
                    'net_total'      => $detail['net_total'] ?? 0,
                    'total'          => $detail['total'] ?? 0,
                    'is_promotional' => $detail['is_promotional'] ?? false,
                    'status'         => $detail['status'] ?? 1,
                ]);

                $stock = WarehouseStock::where('warehouse_id', $header->warehouse_id)
                    ->where('item_id', $detail['item_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$stock) {
                    throw new \Exception(
                        "Stock not found for Item ID {$detail['item_id']} in Warehouse {$header->warehouse_id}"
                    );
                }

                if ($detail['return_type'] == 1) {
                    $stock->qty += $qty;
                } elseif ($detail['return_type'] == 2) {

                    if ($stock->qty < $qty) {
                        throw new \Exception(
                            "Insufficient stock for Item ID {$detail['item_id']} in Warehouse {$header->warehouse_id}"
                        );
                    }

                    $stock->qty -= $qty;
                }

                $stock->save();
            }

            DB::commit();

            $workflow = DB::table('htapp_workflow_assignments')
                ->where('process_type', 'Return_Header')
                ->where('is_active', true)
                ->first();

            if ($workflow) {
                app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)
                    ->startApproval([
                        "workflow_id"  => $workflow->workflow_id,
                        "process_type" => "Return_Header",
                        "process_id"   => $header->id
                    ]);
            }

            return $header->load('details');
        } catch (Exception $e) {

            DB::rollBack();
            Log::error('ReturnService::create Error: ' . $e->getMessage());
            throw $e;
        }
    }

    //     public function getAll(int $perPage, array $filters = [], bool $dropdown = false)
    //     {
    //         $user = auth()->user();
    //         $query = ReturnHeader::with([
    //             'warehouse:id,warehouse_code,warehouse_name',
    //             'route:id,route_code,route_name',
    //             'customer:id,osa_code,name',
    //             'salesman:id,name,osa_code',
    //             // 'returntype:id,return_type',
    //             'createdBy:id,name',
    //             'updatedBy:id,name',
    //             'details.item:id,code,name',
    //         ]);

    //         $query = DataAccessHelper::filterAgentTransaction($query, $user);
    //         if (!empty($filters['warehouse_id'])) {
    //             $query->where('warehouse_id', $filters['warehouse_id']);
    //         }

    //         if (!empty($filters['customer_id'])) {
    //             $query->where('customer_id', $filters['customer_id']);
    //         }

    //         if (!empty($filters['salesman_id'])) {
    //             $query->where('salesman_id', $filters['salesman_id']);
    //         }

    //         if (!empty($filters['osa_code'])) {
    //             $query->where('osa_code', 'LIKE', '%' . $filters['osa_code'] . '%');
    //         }

    //         if (!empty($filters['from_date'])) {
    //             $query->whereDate('created_at', '>=', $filters['from_date']);
    //         }

    //         if (!empty($filters['to_date'])) {
    //             $query->whereDate('created_at', '<=', $filters['to_date']);
    //         }

    //         if (!empty($filters['country_id'])) {
    //             $query->where('country_id', $filters['country_id']);
    //         }

    //         if (!empty($filters['status'])) {
    //             $query->where('status', $filters['status']);
    //         }

    //         $sortBy = $filters['sort_by'] ?? 'created_at';
    //         $sortOrder = $filters['sort_order'] ?? 'desc';
    //         $query->orderBy($sortBy, $sortOrder);

    //         if ($dropdown) {
    //             return $query->get()->map(function ($return) {
    //                 return [
    //                     'id'    => $return->id,
    //                     'label' => $return->osa_code,
    //                     'value' => $return->id,
    //                 ];
    //             });
    //         }
    // // dd($query);
    //         return $query->paginate($perPage);
    //     }
    public function getAll(int $perPage, array $filters = [], bool $dropdown = false)
    {
        $user = auth()->user();

        $query = ReturnHeader::with([
            'warehouse:id,warehouse_code,warehouse_name',
            'route:id,route_code,route_name',
            'customer:id,osa_code,name',
            'salesman:id,name,osa_code',
            'createdBy:id,name',
            'updatedBy:id,name',
            'details.item:id,code,name',
        ]);

        $query = DataAccessHelper::filterAgentTransaction($query, $user);

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
        if (!empty($filters['osa_code']))      $query->where('osa_code', 'LIKE', "%{$filters['osa_code']}%");
        $fromDate = $filters['from_date'] ?? null;
        $toDate   = $filters['to_date'] ?? null;

        if ($fromDate || $toDate) {

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
        } else {
            $query->whereDate('created_at', Carbon::today());
        }
        if (!empty($filters['country_id']))    $query->where('country_id', $filters['country_id']);
        if (!empty($filters['status']))        $query->where('status', $filters['status']);

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        if ($dropdown) {
            return $query->get()->map(function ($return) {
                return [
                    'id'    => $return->id,
                    'label' => $return->osa_code,
                    'value' => $return->id,
                ];
            });
        }

        $returns = $query->paginate($perPage);

        /**
         * ==========================================================
         * 🔥 Inject Approval Workflow Status (Saved Standard Logic)
         * ==========================================================
         */
        $returns->getCollection()->transform(function ($return) {

            $workflowRequest = \App\Models\HtappWorkflowRequest::where('process_type', 'Ht_Return_Header')
                ->where('process_id', $return->id)
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
                    ->orderBy('step_order', 'desc')
                    ->first();

                if ($lastApprovedStep) {
                    $return->approval_status = $lastApprovedStep->message;
                } else {
                    $return->approval_status = 'Initiated';
                }

                $return->current_step = $currentStep ? $currentStep->title : null;
                $return->progress     = $totalSteps > 0 ? ($completedSteps . '/' . $totalSteps) : null;
            } else {
                $return->approval_status = null;
                $return->current_step    = null;
                $return->progress        = null;
            }

            return $return;
        });

        return $returns;
    }


    public function globalFilter(int $perPage = 50, array $filters = [])
    {
        try {
            $user   = auth()->user();
            $filter = $filters['filter'] ?? [];
            if (!empty($filters['current_page'])) {
                Paginator::currentPageResolver(function () use ($filters) {
                    return (int) $filters['current_page'];
                });
            }

            $query = ReturnHeader::with([
                'warehouse:id,warehouse_code,warehouse_name',
                'route:id,route_code,route_name',
                'customer:id,osa_code,name',
                'salesman:id,name,osa_code',
                'createdBy:id,name',
                'updatedBy:id,name',
                'details.item:id,code,name',
            ])->latest();

            // ✅ Agent-based access
            $query = DataAccessHelper::filterAgentTransaction($query, $user);

            // ✅ Location filter
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

            // ✅ Warehouse
            // if (!empty($filter['warehouse_id'])) {
            //     $ids = is_array($filter['warehouse_id'])
            //         ? $filter['warehouse_id']
            //         : explode(',', $filter['warehouse_id']);

            //     $query->whereIn('warehouse_id', array_map('intval', $ids));
            // }

            if (!empty($filter['route_id'])) {
                $routeIds = is_array($filter['route_id'])
                    ? $filter['route_id']
                    : explode(',', $filter['route_id']);

                $query->whereIn('route_id', array_map('intval', $routeIds));
            }

            // ✅ Customer
            if (!empty($filter['customer_id'])) {
                $ids = is_array($filter['customer_id'])
                    ? $filter['customer_id']
                    : explode(',', $filter['customer_id']);

                $query->whereIn('customer_id', array_map('intval', $ids));
            }

            // ✅ Salesman
            if (!empty($filter['salesman_id'])) {
                $ids = is_array($filter['salesman_id'])
                    ? $filter['salesman_id']
                    : explode(',', $filter['salesman_id']);

                $query->whereIn('salesman_id', array_map('intval', $ids));
            }

            // ✅ OSA code
            if (!empty($filter['osa_code'])) {
                $query->where('osa_code', 'LIKE', "%{$filter['osa_code']}%");
            }

            // ✅ Status
            if (!empty($filter['status'])) {
                $query->where('status', $filter['status']);
            }

            // ✅ Date range
            if (!empty($filter['from_date'])) {
                $query->whereDate('created_at', '>=', $filter['from_date']);
            }

            if (!empty($filter['to_date'])) {
                $query->whereDate('created_at', '<=', $filter['to_date']);
            }

            return $query->paginate($perPage);
        } catch (Throwable $e) {
            throw new \Exception("Failed to fetch return headers: " . $e->getMessage());
        }
    }


    public function getByUuid(string $uuid)
    {
        try {
            return ReturnHeader::with([
                'warehouse',
                'route',
                'customer',
                'salesman',
                'createdBy',
                'updatedBy',
                'details.item',
                'details.discount',
                'details.promotion',
            ])->where('uuid', $uuid)->first();
        } catch (\Exception $e) {
            \Log::error('ReturnService::getByUuid Error: ' . $e->getMessage());
            return null;
        }
    }

    public function delete(string $uuid): bool
    {
        try {
            DB::beginTransaction();

            $header = ReturnHeader::where('uuid', $uuid)->first();

            if (! $header) {
                DB::rollBack();
                return false;
            }
            $header->details()->delete();

            $header->delete();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('ReturnService::delete Error: ' . $e->getMessage());
            return false;
        }
    }

    public function updateOrdersStatus(array $returnUuids, int $status): bool
    {
        return ReturnHeader::whereIn('uuid', $returnUuids)
            ->update(['status' => $status]) > 0;
    }

    public function update(string $uuid, array $data): ?ReturnHeader
    {
        try {
            DB::beginTransaction();
            $header = ReturnHeader::where('uuid', $uuid)->first();

            if (!$header) {
                DB::rollBack();
                return null;
            }
            $headerData = [];

            if (isset($data['osa_code'])) $headerData['osa_code'] = $data['osa_code'];
            if (isset($data['currency'])) $headerData['currency'] = $data['currency'];
            if (isset($data['country_id'])) $headerData['country_id'] = $data['country_id'];
            if (isset($data['order_id'])) $headerData['order_id'] = $data['order_id'];
            if (isset($data['delivery_id'])) $headerData['delivery_id'] = $data['delivery_id'];
            if (isset($data['invoice_id'])) $headerData['invoice_id'] = $data['invoice_id'];
            if (isset($data['warehouse_id'])) $headerData['warehouse_id'] = $data['warehouse_id'];
            if (isset($data['route_id'])) $headerData['route_id'] = $data['route_id'];
            if (isset($data['customer_id'])) $headerData['customer_id'] = $data['customer_id'];
            if (isset($data['salesman_id'])) $headerData['salesman_id'] = $data['salesman_id'];
            if (isset($data['gross_total'])) $headerData['gross_total'] = $data['gross_total'];
            if (isset($data['vat'])) $headerData['vat'] = $data['vat'];
            if (isset($data['net_amount'])) $headerData['net_amount'] = $data['net_amount'];
            if (isset($data['total'])) $headerData['total'] = $data['total'];
            if (isset($data['discount'])) $headerData['discount'] = $data['discount'];
            if (isset($data['status'])) $headerData['status'] = $data['status'];

            if (!empty($headerData)) {
                $header->update($headerData);
            }

            if (isset($data['details']) && is_array($data['details'])) {
                $existingDetailIds = $header->details()->pluck('id')->toArray();
                $updatedDetailIds = [];

                foreach ($data['details'] as $detail) {
                    if (!empty($detail['id'])) {
                        $returnDetail = ReturnDetail::where('id', $detail['id'])
                            ->where('header_id', $header->id)
                            ->first();

                        if ($returnDetail) {
                            $returnDetail->update([
                                'item_id'        => $detail['item_id'],
                                'uom_id'         => $detail['uom_id'] ?? null,
                                'discount_id'    => $detail['discount_id'] ?? null,
                                'promotion_id'   => $detail['promotion_id'] ?? null,
                                'parent_id'      => $detail['parent_id'] ?? null,
                                'item_price'     => $detail['item_price'],
                                'item_quantity'  => $detail['item_quantity'],
                                'vat'            => $detail['vat'] ?? 0,
                                'discount'       => $detail['discount'] ?? 0,
                                'gross_total'    => $detail['gross_total'] ?? 0,
                                'net_total'      => $detail['net_total'] ?? 0,
                                'total'          => $detail['total'] ?? 0,
                                'is_promotional' => $detail['is_promotional'] ?? false,
                                'status'         => $detail['status'] ?? 1,
                            ]);

                            $updatedDetailIds[] = $detail['id'];
                        }
                    } else {
                        $newDetail = ReturnDetail::create([
                            'header_id'      => $header->id,
                            'header_code'    => $header->osa_code,
                            'item_id'        => $detail['item_id'],
                            'uom_id'         => $detail['uom_id'] ?? null,
                            'discount_id'    => $detail['discount_id'] ?? null,
                            'promotion_id'   => $detail['promotion_id'] ?? null,
                            'parent_id'      => $detail['parent_id'] ?? null,
                            'item_price'     => $detail['item_price'],
                            'item_quantity'  => $detail['item_quantity'],
                            'vat'            => $detail['vat'] ?? 0,
                            'discount'       => $detail['discount'] ?? 0,
                            'gross_total'    => $detail['gross_total'] ?? 0,
                            'net_total'      => $detail['net_total'] ?? 0,
                            'total'          => $detail['total'] ?? 0,
                            'is_promotional' => $detail['is_promotional'] ?? false,
                            'status'         => $detail['status'] ?? 1,
                        ]);

                        $updatedDetailIds[] = $newDetail->id;
                    }
                }

                $detailsToDelete = array_diff($existingDetailIds, $updatedDetailIds);
                if (!empty($detailsToDelete)) {
                    ReturnDetail::whereIn('id', $detailsToDelete)->delete();
                }
            }

            DB::commit();

            return $this->getByUuid($header->uuid);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('ReturnService::update Error: ' . $e->getMessage());
            throw $e;
        }
    }


    // public function getInoviceBasedReturn(int $perPage, array $filters = [], bool $dropdown = false)
    // {
    //     $user = auth()->user();

    //     $query = InvoiceHeader::with([
    //         'warehouse:id,warehouse_code,warehouse_name',
    //         'customer:id,osa_code,name',
    //         'salesman:id,name,osa_code',
    //         'createdBy:id,name',
    //         'updatedBy:id,name',
    //         'details.item:id,code,name',
    //     ]);

    //     $query = DataAccessHelper::filterAgentTransaction($query, $user);

    //     if (!empty($filters['warehouse_id'])) {

    //         $warehouseIds = is_array($filters['warehouse_id'])
    //             ? $filters['warehouse_id']
    //             : explode(',', $filters['warehouse_id']);

    //         $query->whereIn('warehouse_id', array_map('intval', $warehouseIds));
    //     }

    //     if (!empty($filters['invoice_date'])) {
    //         $query->whereDate('invoice_date', $filters['invoice_date']);
    //     }

    //     if ($dropdown) {
    //         return $query->latest()->get()->map(function ($invoice) {
    //             return [
    //                 'id'    => $invoice->id,
    //                 'label' => $invoice->invoice_no,
    //                 'value' => $invoice->id,
    //             ];
    //         });
    //     }

    //     $sortBy    = $filters['sort_by'] ?? 'invoice_date';
    //     $sortOrder = $filters['sort_order'] ?? 'desc';

    //     return $query->orderBy($sortBy, $sortOrder)
    //         ->paginate($perPage);
    // }

    public function getInoviceBasedReturn(int $perPage, array $filters = [], bool $dropdown = false)
    {
        $user = auth()->user();

        $query = InvoiceHeader::with([
            'warehouse:id,warehouse_code,warehouse_name',
            'customer:id,osa_code,name',
            'salesman:id,name,osa_code',
            'createdBy:id,name',
            'updatedBy:id,name',

            'details.item:id,code,name,erp_code',
            'details.uoms',
            'returns.details'
        ]);

        $query = DataAccessHelper::filterAgentTransaction($query, $user);

        if (!empty($filters['warehouse_id'])) {
            $warehouseIds = is_array($filters['warehouse_id'])
                ? $filters['warehouse_id']
                : explode(',', $filters['warehouse_id']);

            $query->whereIn('warehouse_id', array_map('intval', $warehouseIds));
        }

        if (!empty($filters['invoice_date'])) {
            $query->whereDate('invoice_date', $filters['invoice_date']);
        }

        if ($dropdown) {
            return $query->latest()->get()->map(function ($invoice) {
                return [
                    'id'    => $invoice->id,
                    'label' => $invoice->invoice_no,
                    'value' => $invoice->id,
                ];
            });
        }

        $invoices = $query->orderBy(
            $filters['sort_by'] ?? 'invoice_date',
            $filters['sort_order'] ?? 'desc'
        )->paginate($perPage);

        foreach ($invoices as $invoice) {

            $returnQtyMap = [];

            foreach ($invoice->returns as $return) {
                foreach ($return->details as $returnDetail) {
                    $returnQtyMap[$returnDetail->item_id][$returnDetail->uom_id] =
                        ($returnQtyMap[$returnDetail->item_id][$returnDetail->uom_id] ?? 0)
                        + $returnDetail->item_quantity;
                }
            }

            foreach ($invoice->details as $detail) {
                $detail->setAttribute(
                    'returned_qty',
                    $returnQtyMap[$detail->item_id][$detail->uom] ?? 0
                );
            }
        }

        return $invoices;
    }
}
