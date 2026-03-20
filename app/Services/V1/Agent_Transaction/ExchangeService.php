<?php

namespace App\Services\V1\Agent_transaction;

use App\Models\Agent_Transaction\ExchangeHeader;
use App\Models\Agent_Transaction\ExchangeInInvoice;
use App\Models\Agent_Transaction\ExchangeInReturn;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Helpers\DataAccessHelper;
use App\Helpers\CommonLocationFilter;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;


class ExchangeService
{
    // public function create(array $data): ?ExchangeHeader
    // {
    //     try {
    //         DB::beginTransaction();
    //         $header = ExchangeHeader::create([
    //             'exchange_code' => $data['exchange_code'] ?? null,
    //             'status'        => $data['status'] ?? 1,
    //             'warehouse_id'  => $data['warehouse_id'],
    //             'customer_id'   => $data['customer_id'],
    //             'comment'       => $data['comment'],
    //             // 'currency'      => $data['currency'],
    //             // 'country_id'    => $data['country_id'],
    //             // 'order_id'      => $data['order_id'] ?? null,
    //             // 'delivery_id'   => $data['delivery_id'] ?? null,
    //             // 'route_id'      => $data['route_id'] ?? null,
    //             // 'salesman_id'   => $data['salesman_id'] ?? null,
    //             // 'gross_total'   => $data['gross_total'] ?? 0,
    //             // 'vat'           => $data['vat'] ?? 0,
    //             // 'net_amount'    => $data['net_amount'] ?? 0,
    //             // 'total'         => $data['total'] ?? 0,
    //             // 'discount'      => $data['discount'] ?? 0,
    //         ]);
    //         if (!empty($data['invoices']) && is_array($data['invoices'])) {
    //             foreach ($data['invoices'] as $invoice) {
    //                 ExchangeInInvoice::create([
    //                     'header_id'      => $header->id,
    //                     'exchange_code'  => $header->exchange_code,
    //                     'item_id'        => $invoice['item_id'],
    //                     'uom_id'         => $invoice['uom_id'],
    //                     'item_price'     => $invoice['item_price'] ?? 0,
    //                     'item_quantity'  => $invoice['item_quantity'] ?? 0,
    //                     'total'          => $invoice['total'] ?? 0,
    //                     'status'         => $invoice['status'] ?? 1,
    //                 ]);
    //             }
    //         }
    //         if (!empty($data['returns']) && is_array($data['returns'])) {
    //             foreach ($data['returns'] as $return) {
    //                 ExchangeInReturn::create([
    //                     'header_id'      => $header->id,
    //                     'exchange_code'  => $header->exchange_code,
    //                     'item_id'        => $return['item_id'],
    //                     'uom_id'         => $return['uom_id'],
    //                     'item_price'     => $return['item_price'] ?? 0,
    //                     'item_quantity'  => $return['item_quantity'] ?? 0,
    //                     'total'          => $return['total'] ?? 0,
    //                     'status'         => $return['status'] ?? 1,
    //                     'return_type'    => $invoice['return_type'],
    //                     'region'         => $invoice['region'],
    //                 ]);
    //             }
    //         }
    //         DB::commit();
    //         return $header->load(['invoices', 'returns']);
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         Log::error('ExchangeService::create Error: ' . $e->getMessage());
    //         throw $e;
    //     }
    // }
    public function create(array $data): ?ExchangeHeader
    {
        try {
            DB::beginTransaction();

            $header = ExchangeHeader::create([
                'exchange_code' => $data['exchange_code'] ?? null,
                'status'        => $data['status'] ?? 1,
                'warehouse_id'  => $data['warehouse_id'],
                'customer_id'   => $data['customer_id'],
                'comment'       => $data['comment'] ?? NULL,
            ]);

            if (!empty($data['invoices']) && is_array($data['invoices'])) {
                foreach ($data['invoices'] as $invoice) {
                    ExchangeInInvoice::create([
                        'header_id'     => $header->id,
                        'exchange_code' => $header->exchange_code,
                        'item_id'       => $invoice['item_id'],
                        'uom_id'        => $invoice['uom_id'],
                        'item_price'    => $invoice['item_price'] ?? 0,
                        'item_quantity' => $invoice['item_quantity'] ?? 0,
                        'total'         => $invoice['total'] ?? 0,
                        'status'        => $invoice['status'] ?? 1,
                    ]);
                }
            }

            if (!empty($data['returns']) && is_array($data['returns'])) {
                foreach ($data['returns'] as $return) {
                    ExchangeInReturn::create([
                        'header_id'     => $header->id,
                        'exchange_code' => $header->exchange_code,
                        'item_id'       => $return['item_id'],
                        'uom_id'        => $return['uom_id'],
                        'item_price'    => $return['item_price'] ?? 0,
                        'item_quantity' => $return['item_quantity'] ?? 0,
                        'total'         => $return['total'] ?? 0,
                        'status'        => $return['status'] ?? 1,
                        'return_type'   => $return['return_type'] ?? null,
                        'region'        => $return['region'] ?? null,
                    ]);
                }
            }

            DB::commit();

            /**
             * ================================================
             * 🚀 APPLY WORKFLOW AUTOMATICALLY (SAVED PATTERN)
             * ================================================
             */
            $workflow = DB::table('htapp_workflow_assignments')
                ->where('process_type', 'Exchange_Header')
                ->where('is_active', true)
                ->first();

            if ($workflow) {
                app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)
                    ->startApproval([
                        'workflow_id'  => $workflow->workflow_id,
                        'process_type' => 'Exchange_Header',
                        'process_id'   => $header->id,
                    ]);
            }

            return $header->load(['invoices', 'returns']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('ExchangeService::create Error: ' . $e->getMessage());
            throw $e;
        }
    }

    // public function getAll(int $perPage, array $filters = [], bool $dropdown = false)
    // {
    //     $user = auth()->user();
    //     $query = ExchangeHeader::with([
    //         'warehouse:id,warehouse_name,warehouse_code',
    //         'route:id,route_name,route_code ',
    //         'customer:id,name,osa_code',
    //         'salesman:id,name,osa_code',
    //         'createdBy:id,name',
    //         'updatedBy:id,name',
    //         'invoices.item:id,code,name',
    //         'returns.item:id,code,name',
    //         // 'invoices.item:id,code,name',
    //         // 'returns.item:id,code,name',

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

    //     if (!empty($filters['exchange_code'])) {
    //         $query->where('exchange_code', 'LIKE', '%' . $filters['exchange_code'] . '%');
    //     }

    //     if (!empty($filters['from_date'])) {
    //         $query->whereDate('created_at', '>=', $filters['from_date']);
    //     }

    //     if (!empty($filters['to_date'])) {
    //         $query->whereDate('created_at', '<=', $filters['to_date']);
    //     }

    //     if (!empty($filters['country_id'])) {
    //         $query->where('country_id', $filters['country_id']);
    //     }

    //     if (!empty($filters['status'])) {
    //         $query->where('status', $filters['status']);
    //     }

    //     $sortBy = $filters['sort_by'] ?? 'created_at';
    //     $sortOrder = $filters['sort_order'] ?? 'desc';
    //     $query->orderBy($sortBy, $sortOrder);

    //     if ($dropdown) {
    //         return $query->get()->map(function ($exchange) {
    //             return [
    //                 'id'    => $exchange->id,
    //                 'label' => $exchange->exchange_code,
    //                 'value' => $exchange->id,
    //             ];
    //         });
    //     }

    //     return $query->paginate($perPage);
    // }
    public function getAll(int $perPage, array $filters = [], bool $dropdown = false)
    {
        $user = auth()->user();

        $query = ExchangeHeader::with([
            'warehouse:id,warehouse_name,warehouse_code',
            'route:id,route_name,route_code',
            'customer:id,name,osa_code',
            'salesman:id,name,osa_code',
            'createdBy:id,name',
            'updatedBy:id,name',
            'invoices.item:id,code,name',
            'returns.item:id,code,name',
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

        if (!empty($filters['exchange_code'])) {
            $query->where('exchange_code', 'LIKE', '%' . $filters['exchange_code'] . '%');
        }

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

        if (!empty($filters['country_id'])) {
            $query->where('country_id', $filters['country_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        if ($dropdown) {
            return $query->get()->map(function ($exchange) {
                return [
                    'id'    => $exchange->id,
                    'label' => $exchange->exchange_code,
                    'value' => $exchange->id,
                ];
            });
        }

        $exchanges = $query->paginate($perPage);

        $exchanges->getCollection()->transform(function ($exchange) {

            $workflowRequest = \App\Models\HtappWorkflowRequest::where('process_type', 'Exchange_Header')
                ->where('process_id', $exchange->id)
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

                $exchange->approval_status = $lastApprovedStep
                    ? $lastApprovedStep->message
                    : 'Initiated';

                $exchange->current_step = $currentStep ? $currentStep->title : null;
                $exchange->progress     = $totalSteps > 0
                    ? ($completedSteps . '/' . $totalSteps)
                    : null;
            } else {
                $exchange->approval_status = null;
                $exchange->current_step    = null;
                $exchange->progress        = null;
            }

            return $exchange;
        });

        return $exchanges;
    }

    public function getByUuid(string $uuid)
    {
        try {
            return ExchangeHeader::with([
                'warehouse',
                'route',
                'customer',
                'salesman',
                'createdBy',
                'updatedBy',
                'invoices.item',
                'invoices.discount',
                'invoices.promotion',
                'invoices.parent',
                'returns.item',
                'returns.discount',
                'returns.promotion',
                'returns.parent',
            ])->where('uuid', $uuid)->first();
        } catch (\Exception $e) {
            Log::error('ExchangeService::getByUuid Error: ' . $e->getMessage());
            return null;
        }
    }

    //       public function getAll(int $perPage = 50, array $filters = [], bool $dropdown = false)
    // {
    //     $query = ExchangeHeader::with([
    //         'warehouse:id,warehouse_name,warehouse_code',
    //         'route:id,route_name,route_code',
    //         'customer:id,name,osa_code',
    //         'salesman:id,name,osa_code',
    //         'createdBy:id,name',
    //         'updatedBy:id,name',
    //         'exchange_in_invoices.item:id,code,name',
    //         'exchange_in_invoices.uom:id,name,uom_type',
    //         'exchange_in_returns.item:id,code,name',
    //         'exchange_in_returns.uom:id,name,uom_type',
    //     ]);

    //     $allowedFilters = [
    //         'warehouse_id',
    //         'customer_id',
    //         'salesman_id',
    //         'country_id',4
    //         'status',
    //     ];

    //     foreach ($allowedFilters as $field) {
    //         if (!empty($filters[$field])) {
    //             $query->where($field, $filters[$field]);
    //         }
    //     }
    //     if (!empty($filters['from_date'])) {
    //         $query->whereDate('created_at', '>=', $filters['from_date']);
    //     }
    //     if (!empty($filters['to_date'])) {
    //         $query->whereDate('created_at', '<=', $filters['to_date']);
    //     }
    //     if (!empty($filters['exchange_code'])) {
    //         $query->where('exchange_code', 'LIKE', '%' . $filters['exchange_code'] . '%');
    //     }

    //     if (!empty($filters['search'])) { 
    //         $search = $filters['search'];

    //         $query->where(function ($q) use ($search) {
    //             $q->where('exchange_code', 'LIKE', "%$search%")
    //               ->orWhere('remarks', 'LIKE', "%$search%")
    //               ->orWhere('status', 'LIKE', "%$search%");

    //             $q->orWhereHas('warehouse', function ($w) use ($search) {
    //                 $w->where('warehouse_name', 'LIKE', "%$search%")
    //                   ->orWhere('warehouse_code', 'LIKE', "%$search%");
    //             });

    //             $q->orWhereHas('route', function ($r) use ($search) {
    //                 $r->where('route_name', 'LIKE', "%$search%")
    //                   ->orWhere('route_code', 'LIKE', "%$search%");
    //             });

    //             $q->orWhereHas('customer', function ($c) use ($search) {
    //                 $c->where('name', 'LIKE', "%$search%")
    //                   ->orWhere('osa_code', 'LIKE', "%$search%");
    //             });

    //             $q->orWhereHas('salesman', function ($s) use ($search) {
    //                 $s->where('name', 'LIKE', "%$search%")
    //                   ->orWhere('osa_code', 'LIKE', "%$search%");
    //             });

    //             $q->orWhereHas('exchange_in_invoices.item', function ($i) use ($search) {
    //                 $i->where('name', 'LIKE', "%$search%")
    //                   ->orWhere('code', 'LIKE', "%$search%");
    //             });

    //             $q->orWhereHas('exchange_in_returns.item', function ($i) use ($search) {
    //                 $i->where('name', 'LIKE', "%$search%")
    //                   ->orWhere('code', 'LIKE', "%$search%");
    //             });
    //               $q->orWhereHas('exchange_in_invoices.uom', function ($i) use ($search) {
    //                 $i->where('name', 'LIKE', "%$search%")
    //                   ->orWhere('uom_type', 'LIKE', "%$search%");
    //             });

    //             $q->orWhereHas('exchange_in_returns.uom', function ($i) use ($search) {
    //                 $i->where('name', 'LIKE', "%$search%")
    //                   ->orWhere('uom_type', 'LIKE', "%$search%");
    //             });
    //         });
    //     }

    //     $sortBy    = $filters['sort_by'] ?? 'created_at';
    //     $sortOrder = $filters['sort_order'] ?? 'desc';
    //     $query->orderBy($sortBy, $sortOrder);

    //     if ($dropdown) {
    //         return $query->get()->map(function ($exchange) {
    //             return [
    //                 'id'    => $exchange->id,
    //                 'label' => $exchange->exchange_code,
    //                 'value' => $exchange->id,
    //             ];
    //         });
    //     }

    //     return $query->paginate($perPage);
    // }

    public function delete(string $uuid): bool
    {
        try {
            DB::beginTransaction();

            $header = ExchangeHeader::where('uuid', $uuid)->first();

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
            \Log::error('ExchangeService::delete Error: ' . $e->getMessage());
            return false;
        }
    }

    public function updateOrdersStatus(array $exchangeUuids, int $status): bool
    {
        return ExchangeHeader::whereIn('uuid', $exchangeUuids)
            ->update(['status' => $status]) > 0;
    }

    public function update(string $uuid, array $data): ?ExchangeHeader
    {
        try {
            DB::beginTransaction();

            $header = ExchangeHeader::where('uuid', $uuid)->first();

            if (!$header) {
                DB::rollBack();
                return null;
            }

            // === Prepare header data ===
            $headerData = [];

            if (isset($data['exchange_code'])) $headerData['exchange_code'] = $data['exchange_code'];
            if (isset($data['currency'])) $headerData['currency'] = $data['currency'];
            if (isset($data['country_id'])) $headerData['country_id'] = $data['country_id'];
            if (isset($data['order_id'])) $headerData['order_id'] = $data['order_id'];
            if (isset($data['delivery_id'])) $headerData['delivery_id'] = $data['delivery_id'];
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

            // === Update or Create Invoices (no delete) ===
            if (isset($data['invoices']) && is_array($data['invoices'])) {
                foreach ($data['invoices'] as $invoice) {
                    if (!empty($invoice['id'])) {
                        $exchangeInvoice = ExchangeInInvoice::where('id', $invoice['id'])
                            ->first();

                        if ($exchangeInvoice) {
                            // update only, do not create
                            $exchangeInvoice->update([
                                'header_id'      => $header->id,
                                'exchange_code'  => $header->exchange_code,
                                'item_id'        => $invoice['item_id'],
                                'uom_id'         => $invoice['uom_id'] ?? null,
                                'discount_id'    => $invoice['discount_id'] ?? null,
                                'promotion_id'   => $invoice['promotion_id'] ?? null,
                                'parent_id'      => $invoice['parent_id'] ?? null,
                                'item_price'     => $invoice['item_price'],
                                'item_quantity'  => $invoice['item_quantity'],
                                'vat'            => $invoice['vat'] ?? 0,
                                'discount'       => $invoice['discount'] ?? 0,
                                'gross_total'    => $invoice['gross_total'] ?? 0,
                                'net_total'      => $invoice['net_total'] ?? 0,
                                'total'          => $invoice['total'] ?? 0,
                                'is_promotional' => $invoice['is_promotional'] ?? false,
                                'status'         => $invoice['status'] ?? 1,
                            ]);
                            continue; // ✅ stop here, don’t create again
                        }
                    }

                    // if no ID or record not found, create new
                    ExchangeInInvoice::create([
                        'header_id'      => $header->id,
                        'exchange_code'  => $header->exchange_code,
                        'item_id'        => $invoice['item_id'],
                        'uom_id'         => $invoice['uom_id'] ?? null,
                        'discount_id'    => $invoice['discount_id'] ?? null,
                        'promotion_id'   => $invoice['promotion_id'] ?? null,
                        'parent_id'      => $invoice['parent_id'] ?? null,
                        'item_price'     => $invoice['item_price'],
                        'item_quantity'  => $invoice['item_quantity'],
                        'vat'            => $invoice['vat'] ?? 0,
                        'discount'       => $invoice['discount'] ?? 0,
                        'gross_total'    => $invoice['gross_total'] ?? 0,
                        'net_total'      => $invoice['net_total'] ?? 0,
                        'total'          => $invoice['total'] ?? 0,
                        'is_promotional' => $invoice['is_promotional'] ?? false,
                        'status'         => $invoice['status'] ?? 1,
                    ]);
                }
            }

            // === Update or Create Returns (no duplicate creation) ===
            if (isset($data['returns']) && is_array($data['returns'])) {
                foreach ($data['returns'] as $return) {
                    if (!empty($return['id'])) {
                        $exchangeReturn = ExchangeInReturn::where('id', $return['id'])
                            ->first();

                        if ($exchangeReturn) {
                            $exchangeReturn->update([
                                'header_id'      => $header->id,
                                'exchange_code'  => $header->exchange_code,
                                'item_id'        => $return['item_id'],
                                'uom_id'         => $return['uom_id'] ?? null,
                                'discount_id'    => $return['discount_id'] ?? null,
                                'promotion_id'   => $return['promotion_id'] ?? null,
                                'parent_id'      => $return['parent_id'] ?? null,
                                'item_price'     => $return['item_price'],
                                'item_quantity'  => $return['item_quantity'],
                                'vat'            => $return['vat'] ?? 0,
                                'discount'       => $return['discount'] ?? 0,
                                'gross_total'    => $return['gross_total'] ?? 0,
                                'net_total'      => $return['net_total'] ?? 0,
                                'total'          => $return['total'] ?? 0,
                                'is_promotional' => $return['is_promotional'] ?? false,
                                'status'         => $return['status'] ?? 1,
                            ]);
                            continue; // ✅ stop here, don’t create duplicate
                        }
                    }

                    // Create new record if no id or not found
                    ExchangeInReturn::create([
                        'header_id'      => $header->id,
                        'exchange_code'  => $header->exchange_code,
                        'item_id'        => $return['item_id'],
                        'uom_id'         => $return['uom_id'] ?? null,
                        'discount_id'    => $return['discount_id'] ?? null,
                        'promotion_id'   => $return['promotion_id'] ?? null,
                        'parent_id'      => $return['parent_id'] ?? null,
                        'item_price'     => $return['item_price'],
                        'item_quantity'  => $return['item_quantity'],
                        'vat'            => $return['vat'] ?? 0,
                        'discount'       => $return['discount'] ?? 0,
                        'gross_total'    => $return['gross_total'] ?? 0,
                        'net_total'      => $return['net_total'] ?? 0,
                        'total'          => $return['total'] ?? 0,
                        'is_promotional' => $return['is_promotional'] ?? false,
                        'status'         => $return['status'] ?? 1,
                    ]);
                }
            }

            DB::commit();

            // Return full refreshed header with related data
            return $this->getByUuid($header->uuid);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('ExchangeService::update Error: ' . $e->getMessage());
            throw $e;
        }
    }



    public function globalFilter(int $perPage, array $filters = [])
    {
        $user   = auth()->user();
        $filter = $filters['filter'] ?? [];

        Paginator::currentPageResolver(fn() => (int) ($filters['current_page'] ?? 1));

        // 🔥 NORMALIZE ALL COMMA STRINGS TO ARRAYS
        foreach (['region_id', 'area_id', 'warehouse_id', 'route_id', 'salesman_id', 'customer_id'] as $key) {
            if (!empty($filter[$key]) && !is_array($filter[$key])) {
                $filter[$key] = array_map('intval', explode(',', $filter[$key]));
            }
        }

        $query = ExchangeHeader::with([
            'warehouse:id,warehouse_name,warehouse_code',
            'route:id,route_name,route_code',
            'customer:id,name,osa_code',
            'salesman:id,name,osa_code',
        ])->latest();

        // $query = DataAccessHelper::filterAgentTransaction($query, $user);

        // 🌍 LOCATION HIERARCHY (NOW WORKS)
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
        // dd($warehouseIds);
        // Other filters
        if (!empty($filter['customer_id'])) {
            $query->whereIn('customer_id', $filter['customer_id']);
        }

        if (!empty($filter['salesman_id'])) {
            $query->whereIn('salesman_id', $filter['salesman_id']);
        }

        if (!empty($filter['country_id'])) {
            $query->where('country_id', $filter['country_id']);
        }

        if (!empty($filter['status'])) {
            $query->where('status', (int) $filter['status']);
        }

        if (!empty($filter['exchange_code'])) {
            $query->where('exchange_code', 'ILIKE', '%' . $filter['exchange_code'] . '%');
        }

        if (!empty($filter['from_date']) || !empty($filter['to_date'])) {

            $from = !empty($filter['from_date'])
                ? Carbon::parse($filter['from_date'])->startOfDay()
                : Carbon::minValue();

            $to = !empty($filter['to_date'])
                ? Carbon::parse($filter['to_date'])->endOfDay()
                : Carbon::now()->endOfDay();

            $query->whereBetween('created_at', [$from, $to]);
        }

        return $query->paginate($perPage);
    }
}
