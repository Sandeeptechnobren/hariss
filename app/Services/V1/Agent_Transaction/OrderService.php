<?php

namespace App\Services\V1\Agent_transaction;

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


class OrderService
{


    // public function getAll(int $perPage, array $filters = [], bool $dropdown = false)
    // {
    //     $user = auth()->user();
    //     $query = OrderHeader::with([
    //         'warehouse',
    //         'route',
    //         'warehouse.getCompany',
    //         'country',
    //         'customer',
    //         'salesman',
    //         'createdBy',
    //         'updatedBy',
    //         'details.item.itemUoms',
    //         'details.uom',
    //         'details.discount',
    //         'details.promotion',
    //         'details.parent',
    //         'details.children'
    //     ]);

    //     // $query = DataAccessHelper::filterAgentTransaction($query, $user);

    //     if (isset($filters['order_flag'])) {
    //         $query->where('order_flag', $filters['order_flag']);
    //     }
    //     if (!empty($filters['no_delivery']) && $filters['no_delivery'] == true) {
    //         $query->where('order_flag', 1);
    //     }
    //     if (!empty($filters['search'])) {
    //         $search = $filters['search'];
    //         $query->where(function ($q) use ($search) {
    //             $q->where('order_code', 'LIKE', '%' . $search . '%')
    //                 ->orWhere('comment', 'LIKE', '%' . $search . '%')
    //                 ->orWhere('status', 'LIKE', '%' . $search . '%')
    //                 ->orWhere('delivery_date', 'LIKE', '%' . $search . '%')
    //                 ->orWhereHas('warehouse', function ($q2) use ($search) {
    //                     $q2->where('warehouse_code', 'LIKE', '%' . $search . '%')
    //                         ->orWhere('warehouse_name', 'LIKE', '%' . $search . '%')
    //                         ->orWhere('owner_email', 'LIKE', '%' . $search . '%')
    //                         ->orWhere('owner_number', 'LIKE', '%' . $search . '%')
    //                         ->orWhere('address', 'LIKE', '%' . $search . '%');
    //                 })
    //                 ->orWhereHas('customer', function ($q2) use ($search) {
    //                     $q2->where('osa_code', 'LIKE', '%' . $search . '%')
    //                         ->orWhere('name', 'LIKE', '%' . $search . '%')
    //                         ->orWhere('email', 'LIKE', '%' . $search . '%')
    //                         ->orWhere('street', 'LIKE', '%' . $search . '%')
    //                         ->orWhere('town', 'LIKE', '%' . $search . '%')
    //                         ->orWhere('contact_no', 'LIKE', '%' . $search . '%');
    //                 })
    //                 ->orWhereHas('route', function ($q2) use ($search) {
    //                     $q2->where('route_code', 'LIKE', '%' . $search . '%')
    //                         ->orWhere('route_name', 'LIKE', '%' . $search . '%');
    //                 })
    //                 ->orWhereHas('salesman', function ($q2) use ($search) {
    //                     $q2->where('osa_code', 'LIKE', '%' . $search . '%')
    //                         ->orWhere('name', 'LIKE', '%' . $search . '%');
    //                 })
    //                 ->orWhereHas('details', function ($q2) use ($search) {
    //                     $q2->where('quantity', 'LIKE', '%' . $search . '%')
    //                         ->orWhere('item_price', 'LIKE', '%' . $search . '%')
    //                         ->orWhere('vat', 'LIKE', '%' . $search . '%')
    //                         ->orWhere('discount', 'LIKE', '%' . $search . '%')
    //                         ->orWhere('gross_total', 'LIKE', '%' . $search . '%')
    //                         ->orWhere('net_total', 'LIKE', '%' . $search . '%')
    //                         ->orWhere('total', 'LIKE', '%' . $search . '%')
    //                         ->orWhereHas('item', function ($q3) use ($search) {
    //                             $q3->where('code', 'LIKE', '%' . $search . '%')
    //                                 ->orWhere('name', 'LIKE', '%' . $search . '%')
    //                                 ->orWhereHas('itemUoms', function ($q4) use ($search) {
    //                                     $q4->where('name', 'LIKE', '%' . $search . '%')
    //                                         ->orWhere('price', 'LIKE', '%' . $search . '%')
    //                                         ->orWhere('upc', 'LIKE', '%' . $search . '%')
    //                                         ->orWhere('uom_type', 'LIKE', '%' . $search . '%');
    //                                 });
    //                         })
    //                         ->orWhereHas('uom', function ($q3) use ($search) {
    //                             $q3->where('name', 'LIKE', '%' . $search . '%');
    //                         });
    //                 });
    //         });
    //     }

    //     if (!empty($filters['warehouse_id'])) {
    //         $query->where('warehouse_id', $filters['warehouse_id']);
    //     }
    //     if (!empty($filters['order_code'])) {
    //         $query->where('order_code', 'LIKE', '%' . $filters['order_code'] . '%');
    //     }
    //     if (!empty($filters['customer_id'])) {
    //         $query->where('customer_id', $filters['customer_id']);
    //     }
    //     if (!empty($filters['delivery_date'])) {
    //         $query->where('delivery_date', $filters['delivery_date']);
    //     }
    //     if (!empty($filters['salesman_id'])) {
    //         $query->where('salesman_id', $filters['salesman_id']);
    //     }
    //     if (!empty($filters['comment'])) {
    //         $query->where('comment', 'LIKE', '%' . $filters['comment'] . '%');
    //     }
    //     if (!empty($filters['status'])) {
    //         $query->where('status', $filters['status']);
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

    //     $query->when(!empty($filters['item_id']), function ($q) use ($filters) {
    //         $q->whereHas('details', function ($q2) use ($filters) {
    //             $q2->where('item_id', $filters['item_id']);
    //         });
    //     });

    //     $sortBy = $filters['sort_by'] ?? 'created_at';
    //     $sortOrder = $filters['sort_order'] ?? 'desc';
    //     $query->orderBy($sortBy, $sortOrder);

    //     if ($dropdown) {
    //         return $query->get()->map(function ($order) {
    //             return [
    //                 'id' => $order->id,
    //                 'label' => $order->order_code,
    //                 'value' => $order->id,
    //             ];
    //         });
    //     }

    //     $orders = $query->paginate($perPage);
    //     $orders->getCollection()->transform(function ($order) {
    //         $workflowRequest = \App\Models\HtappWorkflowRequest::where('process_type', 'order')
    //             ->where('process_id', $order->id)
    //             ->orderBy('id', 'DESC')
    //             ->first();
    //         if ($workflowRequest) {
    //             $currentStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
    //                 ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
    //                 ->orderBy('step_order')
    //                 ->first();

    //             $totalSteps = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)->count();

    //             $completedSteps = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
    //                 ->where('status', 'APPROVED')
    //                 ->count();

    //             $order->approval_status = $workflowRequest->status;
    //             // $order->approval_status = $currentStep->message;
    //             $order->current_step    = $currentStep ? $currentStep->title : null;
    //             $order->progress        = $totalSteps > 0 ? ($completedSteps . '/' . $totalSteps) : null;
    //         } else {
    //             $order->approval_status = null;
    //             $order->current_step    = null;
    //             $order->progress        = null;
    //         }

    //         return $order;
    //     });

    //     return $orders;
    // }

    // public function getAll(int $perPage, array $filters = [], bool $dropdown = false)
    // {
    //     $user = auth()->user();

    //     $query = OrderHeader::with([
    //         'warehouse',
    //         'route',
    //         'warehouse.getCompany',
    //         'country',
    //         'customer',
    //         'salesman',
    //         'createdBy',
    //         'updatedBy',
    //         'details.item.itemUoms.uom',
    //         // 'details.item.itemUoms',
    //         'details.uom',
    //         'details.discount',
    //         'details.promotion',
    //         'details.parent',
    //         'details.children'
    //     ]);
    //     $query = DataAccessHelper::filterAgentTransaction($query, $user);
    //     if (!empty($filters['filter']) && is_array($filters['filter'])) {

    //         $warehouseIds = CommonLocationFilter::resolveWarehouseIds([
    //             'company'   => $filters['filter']['company_id']   ?? null,
    //             'region'    => $filters['filter']['region_id']    ?? null,
    //             'area'      => $filters['filter']['area_id']      ?? null,
    //             'warehouse' => $filters['filter']['warehouse_id'] ?? null,
    //             'route'     => $filters['filter']['route_id']     ?? null,
    //         ]);

    //         if (!empty($warehouseIds)) {
    //             $query->whereIn('warehouse_id', $warehouseIds);
    //         }
    //     }


    //     if (isset($filters['order_flag'])) {
    //         $query->where('order_flag', $filters['order_flag']);
    //     }
    //     if (!empty($filters['no_delivery']) && $filters['no_delivery'] == true) {
    //         $query->where('order_flag', 1);
    //     }
    //     if (array_key_exists('is_promotional', $filters)) {

    //         $isPromotional = filter_var(
    //             $filters['is_promotional'],
    //             FILTER_VALIDATE_BOOLEAN,
    //             FILTER_NULL_ON_FAILURE
    //         );

    //         if (!is_null($isPromotional)) {
    //             $query->whereHas('details', function ($q) use ($isPromotional) {
    //                 $q->where('is_promotional', $isPromotional);
    //             });
    //         }
    //     }
    //     if (!empty($filters['search'])) {
    //         $search = $filters['search'];
    //         $query->where(function ($q) use ($search) {
    //             $q->where('order_code', 'LIKE', "%$search%")
    //                 ->orWhere('comment', 'LIKE', "%$search%")
    //                 ->orWhere('status', 'LIKE', "%$search%")
    //                 ->orWhere('delivery_date', 'LIKE', "%$search%")

    //                 ->orWhereHas('warehouse', function ($q2) use ($search) {
    //                     $q2->where('warehouse_code', 'LIKE', "%$search%")
    //                         ->orWhere('warehouse_name', 'LIKE', "%$search%")
    //                         ->orWhere('owner_email', 'LIKE', "%$search%")
    //                         ->orWhere('owner_number', 'LIKE', "%$search%")
    //                         ->orWhere('address', 'LIKE', "%$search%");
    //                 })

    //                 ->orWhereHas('customer', function ($q2) use ($search) {
    //                     $q2->where('osa_code', 'LIKE', "%$search%")
    //                         ->orWhere('name', 'LIKE', "%$search%")
    //                         ->orWhere('email', 'LIKE', "%$search%")
    //                         ->orWhere('street', 'LIKE', "%$search%")
    //                         ->orWhere('town', 'LIKE', "%$search%")
    //                         ->orWhere('contact_no', 'LIKE', "%$search%");
    //                 })

    //                 ->orWhereHas('route', function ($q2) use ($search) {
    //                     $q2->where('route_code', 'LIKE', "%$search%")
    //                         ->orWhere('route_name', 'LIKE', "%$search%");
    //                 })

    //                 ->orWhereHas('salesman', function ($q2) use ($search) {
    //                     $q2->where('osa_code', 'LIKE', "%$search%")
    //                         ->orWhere('name', 'LIKE', "%$search%");
    //                 })

    //                 ->orWhereHas('details', function ($q2) use ($search) {
    //                     $q2->where('quantity', 'LIKE', "%$search%")
    //                         ->orWhere('item_price', 'LIKE', "%$search%")
    //                         ->orWhere('vat', 'LIKE', "%$search%")
    //                         ->orWhere('discount', 'LIKE', "%$search%")
    //                         ->orWhere('gross_total', 'LIKE', "%$search%")
    //                         ->orWhere('net_total', 'LIKE', "%$search%")
    //                         ->orWhere('total', 'LIKE', "%$search%")
    //                         ->orWhereHas('item', function ($q3) use ($search) {
    //                             $q3->where('code', 'LIKE', "%$search%")
    //                                 ->orWhere('name', 'LIKE', "%$search%")
    //                                 ->orWhereHas('itemUoms', function ($q4) use ($search) {
    //                                     $q4->where('name', 'LIKE', "%$search%")
    //                                         ->orWhere('price', 'LIKE', "%$search%")
    //                                         ->orWhere('upc', 'LIKE', "%$search%")
    //                                         ->orWhere('uom_type', 'LIKE', "%$search%");
    //                                 });
    //                         })
    //                         ->orWhereHas('uom', function ($q3) use ($search) {
    //                             $q3->where('name', 'LIKE', "%$search%");
    //                         });
    //                 });
    //         });
    //     }
    //     if (!empty($filters['warehouse_id'])) {

    //         $warehouseIds = is_array($filters['warehouse_id'])
    //             ? $filters['warehouse_id']
    //             : explode(',', $filters['warehouse_id']);

    //         $warehouseIds = array_map('intval', $warehouseIds);

    //         $query->whereIn('warehouse_id', $warehouseIds);
    //     }
    //     // if (!empty($filters['warehouse_id'])) $query->where('warehouse_id', $filters['warehouse_id']);
    //     if (!empty($filters['order_code'])) $query->where('order_code', 'LIKE', '%' . $filters['order_code'] . '%');
    //     if (!empty($filters['customer_id'])) $query->where('customer_id', $filters['customer_id']);
    //     if (!empty($filters['delivery_date'])) $query->where('delivery_date', $filters['delivery_date']);
    //     if (!empty($filters['salesman_id'])) {
    //         $salesmanIds = is_array($filters['salesman_id'])
    //             ? $filters['salesman_id']
    //             : explode(',', $filters['salesman_id']);
    //         $salesmanIds = array_map('intval', $salesmanIds);
    //         $query->whereIn('salesman_id', $salesmanIds);
    //     }
    //     if (!empty($filters['comment'])) $query->where('comment', 'LIKE', "%{$filters['comment']}%");
    //     if (!empty($filters['status'])) $query->where('status', $filters['status']);
    //     if (!empty($filters['from_date']) || !empty($filters['to_date'])) {

    //         if (!empty($filters['from_date'])) {
    //             $query->whereDate('created_at', '>=', $filters['from_date']);
    //         }

    //         if (!empty($filters['to_date'])) {
    //             $query->whereDate('created_at', '<=', $filters['to_date']);
    //         }
    //     } else {

    //         if (empty($filters['filter'])) {
    //             $query->whereDate('created_at', Carbon::today());
    //         }
    //     }
    //     if (!empty($filters['country_id'])) $query->where('country_id', $filters['country_id']);

    //     $sortBy = $filters['sort_by'] ?? 'created_at';
    //     $sortOrder = $filters['sort_order'] ?? 'desc';
    //     $query->orderBy($sortBy, $sortOrder);
    //     if ($dropdown) {

    //         $query->where('status', 1);

    //         $orders = $query->get()->filter(function ($order) {

    //             $workflowRequest = \App\Models\HtappWorkflowRequest::where('process_type', 'order')
    //                 ->where('process_id', $order->id)
    //                 ->latest('id')
    //                 ->first();

    //             if (!$workflowRequest) {
    //                 return false;
    //             }

    //             $lastStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
    //                 ->orderByDesc('step_order')
    //                 ->first();

    //             return $lastStep && $lastStep->status === 'APPROVED';

    //         })->values();
    //     }
    //             $orders->setCollection(
    //                 $orders->getCollection()
    //                     ->transform(function ($order) {
    //                         $workflowRequest = \App\Models\HtappWorkflowRequest::where('process_type', 'order')
    //                             ->where('process_id', $order->id)
    //                             ->orderBy('id', 'DESC')
    //                             ->first();
    //                         if (!$workflowRequest) {
    //                             return null;
    //                         }
    //                         $currentStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
    //                             ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
    //                             ->orderBy('step_order')
    //                             ->first();
    //                         $totalSteps = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)->count();
    //                         $completedSteps = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
    //                             ->where('status', 'APPROVED')
    //                             ->count();
    //                         $lastStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
    //                             ->orderBy('step_order', 'desc')
    //                             ->first();
    //                         if (!$lastStep || $lastStep->status !== 'APPROVED') {
    //                             return null;
    //                         }
    //                         $lastApprovedStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
    //                             ->where('status', 'APPROVED')
    //                             ->orderBy('step_order', 'desc')
    //                             ->first();
    //                         $order->approval_status = $lastApprovedStep ? $lastApprovedStep->message : 'Initiated';
    //                         $order->current_step = $currentStep ? $currentStep->title : null;
    //                         $order->progress = $totalSteps > 0 ? ($completedSteps . '/' . $totalSteps) : null;
    //                         return $order;
    //                     })
    //                     ->filter()
    //                     ->values()
    //             );
    //             return $orders;
    // }
public function getAll(int $perPage, array $filters = [], bool $dropdown = false)
{
    $user = auth()->user();

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
    ]);

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
    if (isset($filters['order_flag'])) {
        $query->where('order_flag', $filters['order_flag']);
    }
    if (!empty($filters['no_delivery']) && $filters['no_delivery'] == true) {
        $query->where('order_flag', 1);
    }
    if (array_key_exists('is_promotional', $filters)) {
        $isPromotional = filter_var(
            $filters['is_promotional'],
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );
        if (!is_null($isPromotional)) {
            $query->whereHas('details', function ($q) use ($isPromotional) {
                $q->where('is_promotional', $isPromotional);
            });
        }
    }
    if (!empty($filters['search'])) {

        $search = $filters['search'];

        $query->where(function ($q) use ($search) {

            $q->where('order_code', 'LIKE', "%$search%")
                ->orWhere('comment', 'LIKE', "%$search%")
                ->orWhere('status', 'LIKE', "%$search%")
                ->orWhere('delivery_date', 'LIKE', "%$search%")

                ->orWhereHas('warehouse', function ($q2) use ($search) {
                    $q2->where('warehouse_code', 'LIKE', "%$search%")
                        ->orWhere('warehouse_name', 'LIKE', "%$search%");
                })

                ->orWhereHas('customer', function ($q2) use ($search) {
                    $q2->where('osa_code', 'LIKE', "%$search%")
                        ->orWhere('name', 'LIKE', "%$search%");
                })

                ->orWhereHas('route', function ($q2) use ($search) {
                    $q2->where('route_code', 'LIKE', "%$search%")
                        ->orWhere('route_name', 'LIKE', "%$search%");
                })

                ->orWhereHas('salesman', function ($q2) use ($search) {
                    $q2->where('osa_code', 'LIKE', "%$search%")
                        ->orWhere('name', 'LIKE', "%$search%");
                });
        });
    }

    if (!empty($filters['warehouse_id'])) {

        $warehouseIds = is_array($filters['warehouse_id'])
            ? $filters['warehouse_id']
            : explode(',', $filters['warehouse_id']);

        $warehouseIds = array_map('intval', $warehouseIds);

        $query->whereIn('warehouse_id', $warehouseIds);
    }

    if (!empty($filters['order_code'])) {
        $query->where('order_code', 'LIKE', '%' . $filters['order_code'] . '%');
    }

    if (!empty($filters['customer_id'])) {
        $query->where('customer_id', $filters['customer_id']);
    }

    if (!empty($filters['delivery_date'])) {
        $query->where('delivery_date', $filters['delivery_date']);
    }

    if (!empty($filters['salesman_id'])) {

        $salesmanIds = is_array($filters['salesman_id'])
            ? $filters['salesman_id']
            : explode(',', $filters['salesman_id']);

        $salesmanIds = array_map('intval', $salesmanIds);

        $query->whereIn('salesman_id', $salesmanIds);
    }

    if (!empty($filters['comment'])) {
        $query->where('comment', 'LIKE', "%{$filters['comment']}%");
    }

    if (!empty($filters['status'])) {
        $query->where('status', $filters['status']);
    }

    if (!empty($filters['from_date']) || !empty($filters['to_date'])) {

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

    } else {

        if (empty($filters['filter'])) {
            $query->whereDate('created_at', Carbon::today());
        }
    }

    if (!empty($filters['country_id'])) {
        $query->where('country_id', $filters['country_id']);
    }

    $sortBy = $filters['sort_by'] ?? 'created_at';
    $sortOrder = $filters['sort_order'] ?? 'desc';

    $query->orderBy($sortBy, $sortOrder);
    // if ($dropdown) {

    //     $query->where('status', 1);

    //     return $query->get()->filter(function ($order) {

    //         $workflowRequest = \App\Models\HtappWorkflowRequest::where('process_type', 'order')
    //             ->where('process_id', $order->id)
    //             ->latest('id')
    //             ->first();

    //         if (!$workflowRequest) {
    //             return false;
    //         }

    //         $lastStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
    //             ->orderByDesc('step_order')
    //             ->first();

    //         return $lastStep && $lastStep->status === 'APPROVED';

    //     })->values();
    // }
    if ($dropdown) {

    $query->where('status', 1);

    return $query->get()->filter(function ($order) {

        $workflowRequest = \App\Models\HtappWorkflowRequest::where('process_type', 'order')
            ->where('process_id', $order->id)
            ->latest('id')
            ->first();

        // If no approval request exists → include the order
        if (!$workflowRequest) {
            return true;
        }

        $lastStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
            ->orderByDesc('step_order')
            ->first();

        // If request exists → include only if approved
        return $lastStep && $lastStep->status === 'APPROVED';

    })->values();
}
    $orders = $query->paginate($perPage);

    $orders->setCollection(

        $orders->getCollection()->transform(function ($order) {

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

            $totalSteps = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)->count();

            $completedSteps = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                ->where('status', 'APPROVED')
                ->count();

            $lastApprovedStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                ->where('status', 'APPROVED')
                ->orderByDesc('step_order')
                ->first();

            $order->approval_status = $lastApprovedStep ? $lastApprovedStep->message : 'Initiated';

            $order->current_step = $currentStep ? $currentStep->title : null;

            $order->progress = $totalSteps > 0
                ? ($completedSteps . '/' . $totalSteps)
                : null;

            return $order;
        })
    );

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

    // public function create(array $data): ?OrderHeader
    //     {
    //         try {
    //             DB::beginTransaction();
    //             $warehouse = Warehouse::with('getCompany')->find($data['warehouse_id']);

    //             $company = $warehouse->getCompany;

    //             $header = OrderHeader::create([
    //                 'order_code'    => $data['order_code'] ?? null,
    //                 'warehouse_id'  => $data['warehouse_id'],
    //                 'delivery_date' => $data['delivery_date'],
    //                 'customer_id'   => $data['customer_id'],
    //                 'comment'       => $data['comment'],
    //                 'status'        => $data['status'] ?? 1,
    //                 'currency'      => $company->selling_currency,
    //                 // 'country_id'    => $data['country_id'],
    //                 // 'route_id'      => $data['route_id'],
    //                 // 'salesman_id'   => $data['salesman_id'] ?? null,
    //                 // 'gross_total'   => $data['gross_total'] ?? 0,
    //                 'vat'           => $data['vat'] ?? 0,
    //                 'net_amount'    => $data['net_amount'] ?? 0,
    //                 'total'         => $data['total'] ?? 0,
    //                 // 'discount'      => $data['discount'] ?? 0,

    //             ]);

    //             if (!empty($data['details']) && is_array($data['details'])) {
    //                 foreach ($data['details'] as $detail) {
    //                     OrderDetail::create([
    //                         'header_id'     => $header->id,
    //                         'item_id'       => $detail['item_id'],
    //                         'uom_id'        => $detail['uom_id'],
    //                         'status'        => $detail['status'] ?? 1,
    //                         'gross_total'   => $detail['gross_total'] ?? 0,
    //                         'net_total'     => $detail['net_total'] ?? 0,
    //                         'total'         => $detail['total'] ?? 0,
    //                         'item_price'    => $detail['item_price'] ?? 0,
    //                         'quantity'      => $detail['quantity'] ?? 0,
    //                         'vat'           => $detail['vat'] ?? 0,
    //                         // 'discount_id'   => $detail['discount_id'] ?? null,
    //                         // 'promotion_id'  => $detail['promotion_id'] ?? null,
    //                         // 'parent_id'     => $detail['parent_id'] ?? null,
    //                         // 'discount'      => $detail['discount'] ?? 0,
    //                         // 'is_promotional'=> $detail['is_promotional'] ?? false,
    //                     ]);
    //                 }
    //             }

    //             DB::commit();
    //             return $header->load('details');
    //         } catch (Exception $e) {
    //             DB::rollBack();
    //             Log::error('OrderService::create Error: ' . $e->getMessage());
    //             throw $e;
    //         }
    //     }


    // public function create(array $data): ?OrderHeader
    // {
    //     try {
    //         DB::beginTransaction();

    //         if (empty($data['warehouse_id'])) {
    //             throw new \InvalidArgumentException('Warehouse is required to create an order.');
    //         }

    //         if (empty($data['customer_id'])) {
    //             throw new \InvalidArgumentException('Customer is required to create an order.');
    //         }

    //         if (empty($data['delivery_date'])) {
    //             throw new \InvalidArgumentException('Delivery date is required.');
    //         }

    //         if (empty($data['details']) || !is_array($data['details'])) {
    //             throw new \InvalidArgumentException('At least one order item is required.');
    //         }

    //         $warehouse = Warehouse::with('getCompany')->find($data['warehouse_id']);

    //         if (!$warehouse) {
    //             throw new \InvalidArgumentException('Invalid warehouse selected.');
    //         }

    //         if (!$warehouse->getCompany) {
    //             throw new \InvalidArgumentException('Company configuration not found for this warehouse.');
    //         }

    //         $company = $warehouse->getCompany;

    //         $header = OrderHeader::create([
    //             'order_code'    => $data['order_code'] ?? null,
    //             'warehouse_id'  => $data['warehouse_id'],
    //             'delivery_date' => $data['delivery_date'],
    //             'customer_id'   => $data['customer_id'],
    //             'comment'       => $data['comment'] ?? null,
    //             'status'        => $data['status'] ?? 1,
    //             'currency'      => $company->selling_currency,
    //             'vat'           => $data['vat'] ?? 0,
    //             'net_amount'    => $data['net_amount'] ?? 0,
    //             'total'         => $data['total'] ?? 0,
    //         ]);

    //         foreach ($data['details'] as $index => $detail) {

    //             $isPromotion = (bool) ($detail['isPrmotion'] ?? false);

    //             OrderDetail::create([
    //                 'header_id'      => $header->id,
    //                 'item_id'        => $detail['item_id'],
    //                 'uom_id'         => $detail['uom_id'],
    //                 'status'         => $detail['status'] ?? 1,
    //                 'is_promotional' => $isPromotion,
    //                 'gross_total' => $isPromotion
    //                     ? $this->valueOrZero($detail, 'gross_total')
    //                     : $detail['gross_total'] ?? null,

    //                 'net_total' => $isPromotion
    //                     ? $this->valueOrZero($detail, 'net_total')
    //                     : $detail['net_total'],

    //                 'total' => $isPromotion
    //                     ? $this->valueOrZero($detail, 'total')
    //                     : $detail['total'],

    //                 'item_price' => $isPromotion
    //                     ? $this->valueOrZero($detail, 'item_price')
    //                     : $detail['item_price'],

    //                 'vat' => $isPromotion
    //                     ? $this->valueOrZero($detail, 'vat')
    //                     : $detail['vat'],

    //                 'quantity' => $isPromotion
    //                     ? $this->valueOrZero($detail, 'quantity')
    //                     : $detail['quantity'],
    //             ]);
    //         }

    //         DB::commit();

    //         $workflow = DB::table('htapp_workflow_assignments')
    //             ->where('process_type', 'order')
    //             ->where('is_active', true)
    //             ->first();

    //         if ($workflow) {
    //             app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)
    //                 ->startApproval([
    //                     'workflow_id'  => $workflow->workflow_id,
    //                     'process_type' => 'order',
    //                     'process_id'   => $header->id,
    //                 ]);
    //         }

    //         return $header->load('details');
    //     } catch (\InvalidArgumentException $e) {
    //         DB::rollBack();
    //         throw new \Exception($e->getMessage());
    //     } catch (\Throwable $e) {
    //         dd($e);

    //         DB::rollBack();

    //         Log::error('OrderService::create failed', [
    //             'error' => $e->getMessage(),
    //             'data'  => $data,
    //         ]);

    //         throw new \Exception(
    //             'Unable to create order at the moment. Please verify the details and try again.'
    //         );
    //     }
    // }
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
                //  dd($e),
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
    // public function create(array $data): ?OrderHeader
    // {
    //     try {
    //         DB::beginTransaction();
    //         $warehouse = Warehouse::with('getCompany')->find($data['warehouse_id']);
    //         $company = $warehouse->getCompany;
    //         $header = OrderHeader::create([
    //             'order_code'    => $data['order_code'] ?? null,
    //             'warehouse_id'  => $data['warehouse_id'] ?? null,
    //             'delivery_date' => $data['delivery_date'],
    //             'customer_id'   => $data['customer_id'],
    //             'comment'       => $data['comment'],
    //             'status'        => $data['status'] ?? 1,
    //             'currency'      => $company->selling_currency,
    //             'vat'           => $data['vat'] ?? 0,
    //             'net_amount'    => $data['net_amount'] ?? 0,
    //             'total'         => $data['total'] ?? 0,
    //         ]);
    //         if (!empty($data['details']) && is_array($data['details'])) {
    //             foreach ($data['details'] as $detail) {
    //                 OrderDetail::create([
    //                     'header_id'     => $header->id,
    //                     'item_id'       => $detail['item_id'],
    //                     'uom_id'        => $detail['uom_id'],
    //                     'status'        => $detail['status'] ?? 1,
    //                     'gross_total'   => $detail['gross_total'] ?? 0,
    //                     'net_total'     => $detail['net_total'] ?? 0,
    //                     'total'         => $detail['total'] ?? 0,
    //                     'item_price'    => $detail['item_price'] ?? 0,
    //                     'quantity'      => $detail['quantity'] ?? 0,
    //                     'vat'           => $detail['vat'] ?? 0,
    //                 ]);
    //             }
    //         }

    //         DB::commit();
    //         $workflow = DB::table('htapp_workflow_assignments')
    //             ->where('process_type', 'order')
    //             ->where('is_active', true)
    //             ->first();

    //         if ($workflow) {
    //             app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)
    //                 ->startApproval([
    //                     "workflow_id"  => $workflow->workflow_id,
    //                     "process_type" => "order",
    //                     "process_id"   => $header->id
    //                 ]);
    //         }
    //         return $header->load('details');
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         Log::error('OrderService::create Error: ' . $e->getMessage());
    //         throw $e;
    //     }
    // }


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
