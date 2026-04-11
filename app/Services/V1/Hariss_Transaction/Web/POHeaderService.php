<?php

namespace App\Services\V1\Hariss_transaction\Web;

use App\Models\Hariss_Transaction\Web\PoOrderHeader;
use App\Models\Hariss_Transaction\Web\PoOrderDetail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\CompanyCustomer;
use App\Models\Warehouse;
use App\Models\Region;
use Carbon\Carbon;
use App\Helpers\CommonLocationFilter;
use App\Helpers\DataAccessHelper;
use Illuminate\Pagination\Paginator;

class POHeaderService
{
   
// public function getAll(int $perPage, array $filters = [], bool $dropdown = false)
//     {
//         $query = PoOrderHeader::latest();
//         if (!empty($filters['search'])) {
//             $search = $filters['search'];
//             $query->where(function ($q) use ($search) {
//                 $q->where('order_code', 'LIKE', "%$search%")
//                     ->orWhere('comment', 'LIKE', "%$search%")
//                     ->orWhere('status', 'LIKE', "%$search%");
//             });
//         }
//         foreach (['warehouse_id', 'company_id', 'country_id', 'status'] as $field) {
//             if (!empty($filters[$field])) {
//                 $query->where($field, $filters[$field]);
//             }
//         }
//         $fromDate = !empty($filters['from_date'])
//             ? Carbon::parse($filters['from_date'])->toDateString()
//             : null;

//         $toDate = !empty($filters['to_date'])
//             ? Carbon::parse($filters['to_date'])->toDateString()
//             : null;

//         if ($fromDate || $toDate) {

//             if ($fromDate && $toDate) {
//                 $query->whereDate('created_at', '>=', $fromDate)
//                     ->whereDate('created_at', '<=', $toDate);
//             } elseif ($fromDate) {
//                 $query->whereDate('created_at', '>=', $fromDate);
//             } elseif ($toDate) {
//                 $query->whereDate('created_at', '<=', $toDate);
//             }
//         } else {
//             $query->whereDate('created_at', Carbon::today());
//         }
//         $sortBy    = $filters['sort_by'] ?? 'created_at';
//         $sortOrder = $filters['sort_order'] ?? 'desc';
//         $query->orderBy($sortBy, $sortOrder);
//         if ($dropdown) {
//             return $query->get()->map(function ($item) {
//                 return [
//                     'id'    => $item->id,
//                     'label' => $item->order_code,
//                     'value' => $item->id,
//                 ];
//             });
//         }
//         $orders = $query->paginate($perPage);
//         $orders->getCollection()->transform(function ($order) {
//             $workflowRequest = \App\Models\HtappWorkflowRequest::where('process_type', 'Po_Order_Header')
//                 ->where('process_id', $order->id)
//                 ->orderByDesc('id')
//                 ->first();
//             if ($workflowRequest) {
//                 $currentStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
//                     ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
//                     ->orderBy('step_order')
//                     ->first();
//                 $lastApprovedStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
//                     ->where('status', 'APPROVED')
//                     ->orderByDesc('step_order')
//                     ->first();
//                 $totalSteps = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)->count();
//                 $completedSteps = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
//                     ->where('status', 'APPROVED')
//                     ->count();
//                 $order->approval_status = $lastApprovedStep?->message ?? 'Initiated';
//                 $order->current_step   = $currentStep?->title;
//                 $order->progress       = $totalSteps > 0
//                     ? ($completedSteps . '/' . $totalSteps)
//                     : null;
//             } else {
//                 $order->approval_status = null;
//                 $order->current_step   = null;
//                 $order->progress       = null;
//             }
//             return $order;
//         });
//         return $orders;
//     }
public function getAll(int $perPage, array $filters = [], bool $dropdown = false)
{
    $query = PoOrderHeader::latest();

    if (!empty($filters['search'])) {
        $search = $filters['search'];
        $query->where(function ($q) use ($search) {
            $q->where('order_code', 'LIKE', "%$search%")
                ->orWhere('comment', 'LIKE', "%$search%")
                ->orWhere('status', 'LIKE', "%$search%");
        });
    }
    foreach (['warehouse_id', 'company_id', 'country_id', 'status'] as $field) {
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
            $query->whereDate('order_date', '>=', $fromDate)
                  ->whereDate('order_date', '<=', $toDate);
        } elseif ($fromDate) {
            $query->whereDate('order_date', '>=', $fromDate);
        } elseif ($toDate) {
            $query->whereDate('order_date', '<=', $toDate);
        }

    } else {
        $query->whereDate('order_date', Carbon::today());
    }

    $sortBy    = $filters['sort_by'] ?? 'created_at';
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

    $orders = $query->paginate($perPage);

    $orders->getCollection()->transform(function ($order) {

        $workflowRequest = \App\Models\HtappWorkflowRequest::where('process_type', 'Po_Order_Header')
            ->where('process_id', $order->id)
            ->orderByDesc('id')
            ->first();

        if ($workflowRequest) {

            $currentStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
                ->orderBy('step_order')
                ->first();

            $lastApprovedStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                ->where('status', 'APPROVED')
                ->orderByDesc('step_order')
                ->first();

            $totalSteps = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)->count();

            $completedSteps = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                ->where('status', 'APPROVED')
                ->count();

            $order->approval_status = $lastApprovedStep?->message ?? 'Initiated';
            $order->current_step   = $currentStep?->title;
            $order->progress       = $totalSteps > 0
                ? ($completedSteps . '/' . $totalSteps)
                : null;

        } else {
            $order->approval_status = null;
            $order->current_step   = null;
            $order->progress       = null;
        }

        return $order;
    });

    return $orders;
} 

    public function getByUuid(string $uuid)
    {
        try {
            $current = PoOrderHeader::with([
                'details' => function ($q) {
                    $q->with(['item', 'uom']);
                },
            ])->where('uuid', $uuid)->first();

            if (!$current) {
                return null;
            }
            $previousUuid = PoOrderHeader::where('id', '<', $current->id)
                ->orderBy('id', 'desc')
                ->value('uuid');

            $nextUuid = PoOrderHeader::where('id', '>', $current->id)
                ->orderBy('id', 'asc')
                ->value('uuid');

            $current->previous_uuid = $previousUuid;
            $current->next_uuid = $nextUuid;

            return $current;
        } catch (\Exception $e) {
            Log::error("POOrderHeaderService::getByUuid Error: " . $e->getMessage());
            return null;
        }
    }

    // public function createOrder(array $data)
    //     {
    //         return DB::transaction(function () use ($data) {
    //             $header = PoOrderHeader::create([
    //                 'sap_id'        => $data['sap_id'] ?? null,
    //                 'sap_msg'       => $data['sap_msg'] ?? null,
    //                 'customer_id'   => $data['customer_id'],
    //                 'warehouse_id'  => $data['warehouse_id'],
    //                 'company_id'    => $data['company_id'],
    //                 'delivery_date' => $data['delivery_date'] ?? null,
    //                 'comment'       => $data['comment'] ?? null,
    //                 'order_code'    => $data['order_code'] ?? null,
    //                 'status'        => $data['status'] ?? "0",
    //                 'currency'      => $data['currency'] ?? null,
    //                 'country_id'    => $data['country_id'] ?? null,
    //                 'salesman_id'   => $data['salesman_id'] ?? null,
    //                 'gross_total'   => $data['gross_total'] ?? 0,
    //                 'discount'      => $data['discount'] ?? 0,
    //                 'pre_vat'       => $data['pre_vat'] ?? 0,
    //                 'vat'           => $data['vat'] ?? 0,
    //                 'excise'        => $data['excise'] ?? 0,
    //                 'net'           => $data['net'] ?? 0,
    //                 'total'         => $data['total'] ?? 0,
    //                 'order_flag'    => $data['order_flag'] ?? 1,
    //                 'log_file'      => $data['log_file'] ?? null,
    //                 'doc_type'      => $data['doc_type'] ?? null,
    //                 'order_date'    => $data['order_date'] ?? null,
    //             ]);
    //             foreach ($data['details'] as $detail) {
    //                 PoOrderDetail::create([
    //                     'header_id'     => $header->id,
    //                     'item_id'       => $detail['item_id'],
    //                     'uom_id'        => $detail['uom_id'],
    //                     'discount_id'   => $detail['discount_id'] ?? null,
    //                     'promotion_id'  => $detail['promotion_id'] ?? null,
    //                     'parent_id'     => $detail['parent_id'] ?? null,
    //                     'item_price'    => $detail['item_price'] ?? 0,
    //                     'quantity'      => $detail['quantity'],
    //                     'discount'      => $detail['discount'] ?? 0,
    //                     'gross_total'   => $detail['gross_total'] ?? 0,
    //                     'promotion'     => $detail['promotion'] ?? false,
    //                     'net'           => $detail['net'] ?? 0,
    //                     'excise'        => $detail['excise'] ?? 0,
    //                     'pre_vat'       => $detail['pre_vat'] ?? 0,
    //                     'vat'           => $detail['vat'] ?? 0,
    //                     'total'         => $detail['total'] ?? 0,
    //                 ]);
    //             }
    //             return $header;
    //         });
    //     }
    public function createOrder(array $data)
    {
        try {
            DB::beginTransaction();
            $customer = CompanyCustomer::findOrFail($data['customer_id']);
            $warehouseId = null;
            $companyId   = null;
            if ($customer->customer_type == 2) {
                $warehouse = Warehouse::where(
                    'company_customer_id',
                    $customer->id
                )->first();
                if (!$warehouse) {
                    throw new \Exception('Distributor not found for this customer');
                }
                $warehouseId = $warehouse->id;
            }
            if ($customer->customer_type == 4) {

                $region = Region::find($customer->region_id);

                if (!$region) {
                    throw new \Exception('Region not found for this customer');
                }

                $companyId = $region->company_id;
            }
            $header = PoOrderHeader::create([
                'sap_id'        => $data['sap_id'] ?? null,
                'sap_msg'       => $data['sap_msg'] ?? null,
                'customer_id'   => $customer->id,
                'warehouse_id'  => $warehouseId,
                'company_id'    => $companyId,
                'delivery_date' => $data['delivery_date'] ?? null,
                'comment'       => $data['comment'] ?? null,
                'order_code'    => $data['order_code'] ?? null,
                'status'        => $data['status'] ?? 1,
                'currency'      => $data['currency'] ?? null,
                'country_id'    => $data['country_id'] ?? null,
                'salesman_id'   => $data['salesman_id'] ?? null,
                'gross_total'   => $data['gross_total'] ?? 0,
                'discount'      => $data['discount'] ?? 0,
                'pre_vat'       => $data['pre_vat'] ?? 0,
                'vat'           => $data['vat'] ?? 0,
                'excise'        => $data['excise'] ?? 0,
                'net'           => $data['net'] ?? 0,
                'total'         => $data['total'] ?? 0,
                'order_flag'    => $data['order_flag'] ?? 1,
                'order_date'    => $data['order_date'] ?? now(),
            ]);
            foreach ($data['details'] as $detail) {
                PoOrderDetail::create([
                    'header_id'  => $header->id,
                    'item_id'    => $detail['item_id'],
                    'uom_id'     => $detail['uom_id'],
                    'quantity'   => $detail['quantity'],
                    'item_price' => $detail['item_price'] ?? 0,
                    'excise'     => $detail['excise'] ?? 0,
                    'discount'   => $detail['discount'] ?? 0,
                    'net'        => $detail['net'] ?? 0,
                    'vat'        => $detail['vat'] ?? 0,
                    'total'      => $detail['total'] ?? 0,
                ]);
            }

            DB::commit();

            return $header->load('details');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('PO Order create failed', ['error' => $e->getMessage()]);
            throw $e;
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
        $query = PoOrderHeader::latest('id');
        $query = DataAccessHelper::filterAgentTransaction($query, $user);
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
        if (!empty($filter['salesman_id'])) {
            $salesmanIds = is_array($filter['salesman_id'])
                ? $filter['salesman_id']
                : explode(',', $filter['salesman_id']);
            $query->whereIn('salesman_id', $salesmanIds);
        }
        if (isset($filter['status'])) {
            $query->where('status', $filter['status']);
        }
        if (!empty($filter['from_date'])) {
            $query->whereDate('order_date', '>=', $filter['from_date']);
        }
        if (!empty($filter['to_date'])) {
            $query->whereDate('order_date', '<=', $filter['to_date']);
        }
        return $query->paginate($perPage);
    }
}
