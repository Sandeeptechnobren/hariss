<?php

namespace App\Services\V1\Hariss_transaction\Web;

use App\Models\Hariss_Transaction\Web\HtCapsHeader;
use App\Models\Hariss_Transaction\Web\HtCapsDetail;
use App\Models\CapsCollectionQty;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helpers\DataAccessHelper;
use App\Helpers\CommonLocationFilter;

class CapsHService
{
    //     public function createCaps(array $data)
    // {
    //     return DB::transaction(function () use ($data) {

    //         $header = HtCapsHeader::create([
    //             'osa_code'      => $data['osa_code'] ?? null,
    //             'warehouse_id'  => $data['warehouse_id'] ?? null,
    //             'driver_id'     => $data['driver_id'] ?? null,
    //             'truck_no'      => $data['truck_no'],
    //             'contact_no'    => $data['contact_no'],
    //             'claim_no'      => $data['claim_no'] ?? null,
    //             'claim_date'    => $data['claim_date'] ?? null,
    //             'claim_amount'  => $data['claim_amount'] ?? null,
    //             'status'        => $data['status'] ?? "0",
    //         ]);

    //         foreach ($data['details'] as $detail) {

    //             HtCapsDetail::create([
    //                 'header_id'     => $header->id,
    //                 'osa_code'      => $detail['osa_code'] ?? null,
    //                 'item_id'       => $detail['item_id'],
    //                 'uom_id'        => $detail['uom_id'],
    //                 'quantity'      => $detail['quantity'] ?? 0,
    //                 'receive_qty'   => $detail['receive_qty'] ?? 0,
    //                 'receive_amount'=> $detail['receive_amount'] ?? 0,
    //                 'receive_date'  => $detail['receive_date'],
    //                 'remarks'       => $detail['remarks'],
    //                 'remarks2'      => $detail['remarks2'],
    //                 'status'        => $detail['status'] ?? 0,
    //             ]);
    //             $warehouseId = $header->warehouse_id;
    //             $itemId = $detail['item_id'];
    //             $decreaseQty = $detail['quantity'];

    //             $qtyRow = CapsCollectionQty::where('warehouse_id', $warehouseId)
    //                 ->where('item_id', $itemId)
    //                 ->lockForUpdate()
    //                 ->first();
    //             if (!$qtyRow) {
    //                 DB::rollBack();
    //                 return [
    //                     'status' => true,
    //                     'stock_available' => false,
    //                     'message' => "No stock available for item ID {$itemId} in warehouse {$warehouseId}"
    //                 ];
    //             }
    //             if ($qtyRow->quantity < $decreaseQty) {
    //                 DB::rollBack();
    //                 return [
    //                     'status' => true,
    //                     'stock_available' => false,
    //                     'message' => "Insufficient stock for item ID {$itemId} in warehouse {$warehouseId}"
    //                 ];
    //             }
    //             $qtyRow->update([
    //                 'quantity' => $qtyRow->quantity - $decreaseQty
    //             ]);
    //         }

    //         return $header;
    //     });
    // }
    public function createCaps(array $data)
    {
        return DB::transaction(function () use ($data) {

            $header = HtCapsHeader::create([
                'osa_code'      => $data['osa_code'] ?? null,
                'warehouse_id'  => $data['warehouse_id'] ?? null,
                'driver_id'     => $data['driver_id'] ?? null,
                'truck_no'      => $data['truck_no'],
                'contact_no'    => $data['contact_no'],
                'claim_no'      => $data['claim_no'] ?? null,
                'claim_date'    => $data['claim_date'] ?? null,
                'claim_amount'  => $data['claim_amount'] ?? null,
                'status'        => $data['status'] ?? "0",
            ]);

            foreach ($data['details'] as $detail) {

                HtCapsDetail::create([
                    'header_id'      => $header->id,
                    'osa_code'       => $detail['osa_code'] ?? null,
                    'item_id'        => $detail['item_id'],
                    'uom_id'         => $detail['uom_id'],
                    'quantity'       => $detail['quantity'] ?? 0,
                    'receive_qty'    => $detail['receive_qty'] ?? 0,
                    'receive_amount' => $detail['receive_amount'] ?? 0,
                    'receive_date'   => $detail['receive_date'],
                    'remarks'        => $detail['remarks'] ?? null,
                    'remarks2'       => $detail['remarks2'] ?? null,
                    'status'         => $detail['status'] ?? 0,
                ]);

                $warehouseId = $header->warehouse_id;
                $itemId      = $detail['item_id'];
                $decreaseQty = $detail['receive_qty'];

                $qtyRow = CapsCollectionQty::where('warehouse_id', $warehouseId)
                    ->where('item_id', $itemId)
                    ->lockForUpdate()
                    ->first();

                if (!$qtyRow) {
                    throw new \Exception(
                        "No stock available for item ID {$itemId} in distributor {$warehouseId}"
                    );
                }

                if ($qtyRow->quantity < $decreaseQty) {
                    throw new \Exception(
                        "Insufficient stock for item ID {$itemId} in distributor {$warehouseId}"
                    );
                }

                $qtyRow->update([
                    'quantity' => $qtyRow->quantity - $decreaseQty
                ]);
            }

            $workflow = DB::table('htapp_workflow_assignments')
                ->where('process_type', 'Ht_Caps_Header')
                ->where('is_active', true)
                ->first();

            if ($workflow) {
                app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)
                    ->startApproval([
                        'workflow_id'  => $workflow->workflow_id,
                        'process_type' => 'Ht_Caps_Header',
                        'process_id'   => $header->id,
                    ]);
            }

            return $header->load('details');
        });
    }

    //     public function getAll(int $perPage, array $filters = [], bool $dropdown = false)
    // {
    //     $query = HtCapsHeader::with([
    //         'warehouse',
    //         'driverinfo:id,osa_code,driver_name,contactno',
    //         'details.item',
    //         'details.itemuom'
    //     ])->latest();

    //     if (!empty($filters['search'])) {
    //         $search = $filters['search'];

    //         $query->where(function ($q) use ($search) {
    //             $q->where('osa_code', 'LIKE', "%$search%")
    //                 ->orWhere('truck_no', 'LIKE', "%$search%")
    //                 ->orWhere('contact_no', 'LIKE', "%$search%")
    //                 ->orWhere('claim_no', 'LIKE', "%$search%")
    //                 ->orWhere('status', 'LIKE', "%$search%")

    //                 ->orWhereHas('warehouse', function ($w) use ($search) {
    //                     $w->where('warehouse_code', 'LIKE', "%$search%")
    //                       ->orWhere('warehouse_name', 'LIKE', "%$search%")
    //                       ->orWhere('warehouse_email', 'LIKE', "%$search%")
    //                       ->orWhere('town_village', 'LIKE', "%$search%")
    //                       ->orWhere('street', 'LIKE', "%$search%");
    //                 })

    //                 ->orWhereHas('driverinfo', function ($d) use ($search) {
    //                     $d->where('osa_code', 'LIKE', "%$search%")
    //                       ->orWhere('driver_name', 'LIKE', "%$search%")
    //                       ->orWhere('contactno', 'LIKE', "%$search%");
    //                 })

    //                 ->orWhereHas('details', function ($det) use ($search) {
    //                     $det->where('osa_code', 'LIKE', "%$search%")
    //                         ->orWhere('quantity', 'LIKE', "%$search%")
    //                         ->orWhere('receive_qty', 'LIKE', "%$search%")
    //                         ->orWhere('receive_amount', 'LIKE', "%$search%")
    //                         ->orWhere('remarks', 'LIKE', "%$search%")
    //                         ->orWhere('remarks2', 'LIKE', "%$search%")
    //                         ->orWhere('status', 'LIKE', "%$search%")

    //                         ->orWhereHas('item', function ($i) use ($search) {
    //                             $i->where('code', 'LIKE', "%$search%")
    //                               ->orWhere('name', 'LIKE', "%$search%");
    //                         })

    //                         ->orWhereHas('itemuom', function ($u) use ($search) {
    //                             $u->where('name', 'LIKE', "%$search%")
    //                               ->orWhere('uom_type', 'LIKE', "%$search%");
    //                         });
    //                 });
    //         });
    //     }

    //     $headerFilters = [
    //         'osa_code',
    //         'warehouse_id',
    //         'driver_id',
    //         'truck_no',
    //         'contact_no',
    //         'claim_no',
    //         'claim_date',
    //         'status',
    //     ];

    //     foreach ($headerFilters as $field) {
    //         if (!empty($filters[$field])) {
    //             $query->where($field, $filters[$field]);
    //         }
    //     }

    //     if (!empty($filters['item_id'])) {
    //         $query->whereHas('details', function ($q) use ($filters) {
    //             $q->where('item_id', $filters['item_id']);
    //         });
    //     }

    //     if (!empty($filters['uom_id'])) {
    //         $query->whereHas('details', function ($q) use ($filters) {
    //             $q->where('uom_id', $filters['uom_id']);
    //         });
    //     }

    //     if (!empty($filters['remarks'])) {
    //         $query->whereHas('details', function ($q) use ($filters) {
    //             $q->where('remarks', 'LIKE', "%{$filters['remarks']}%");
    //         });
    //     }

    //     if (!empty($filters['from_date'])) {
    //         $query->whereDate('created_at', '>=', $filters['from_date']);
    //     }
    //     if (!empty($filters['to_date'])) {
    //         $query->whereDate('created_at', '<=', $filters['to_date']);
    //     }

    //     $sortBy = $filters['sort_by'] ?? 'created_at';
    //     $sortOrder = $filters['sort_order'] ?? 'desc';
    //     $query->orderBy($sortBy, $sortOrder);

    //     if ($dropdown) {
    //         return $query->get()->map(function ($item) {
    //             return [
    //                 'id'    => $item->id,
    //                 'label' => $item->osa_code,
    //                 'value' => $item->id,
    //             ];
    //         });
    //     }
    //     return $query->paginate($perPage);
    // }
    public function getAll(int $perPage, array $filters = [], bool $dropdown = false)
    {
        $query = HtCapsHeader::with([
            'warehouse',
            'driverinfo:id,osa_code,driver_name,contactno',
            'details.item',
            'details.itemuom'
        ])->latest();

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('osa_code', 'LIKE', "%$search%")
                    ->orWhere('truck_no', 'LIKE', "%$search%")
                    ->orWhere('contact_no', 'LIKE', "%$search%")
                    ->orWhere('claim_no', 'LIKE', "%$search%")
                    ->orWhere('status', 'LIKE', "%$search%")

                    ->orWhereHas('warehouse', function ($w) use ($search) {
                        $w->where('warehouse_code', 'LIKE', "%$search%")
                            ->orWhere('warehouse_name', 'LIKE', "%$search%")
                            ->orWhere('warehouse_email', 'LIKE', "%$search%")
                            ->orWhere('town_village', 'LIKE', "%$search%")
                            ->orWhere('street', 'LIKE', "%$search%");
                    })

                    ->orWhereHas('driverinfo', function ($d) use ($search) {
                        $d->where('osa_code', 'LIKE', "%$search%")
                            ->orWhere('driver_name', 'LIKE', "%$search%")
                            ->orWhere('contactno', 'LIKE', "%$search%");
                    })

                    ->orWhereHas('details', function ($det) use ($search) {
                        $det->where('osa_code', 'LIKE', "%$search%")
                            ->orWhere('quantity', 'LIKE', "%$search%")
                            ->orWhere('receive_qty', 'LIKE', "%$search%")
                            ->orWhere('receive_amount', 'LIKE', "%$search%")
                            ->orWhere('remarks', 'LIKE', "%$search%")
                            ->orWhere('remarks2', 'LIKE', "%$search%")
                            ->orWhere('status', 'LIKE', "%$search%")

                            ->orWhereHas('item', function ($i) use ($search) {
                                $i->where('code', 'LIKE', "%$search%")
                                    ->orWhere('name', 'LIKE', "%$search%");
                            })

                            ->orWhereHas('itemuom', function ($u) use ($search) {
                                $u->where('name', 'LIKE', "%$search%")
                                    ->orWhere('uom_type', 'LIKE', "%$search%");
                            });
                    });
            });
        }

        $headerFilters = [
            'osa_code',
            'warehouse_id',
            'driver_id',
            'truck_no',
            'contact_no',
            'claim_no',
            'claim_date',
            'status',
        ];

        foreach ($headerFilters as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (!empty($filters['item_id'])) {
            $query->whereHas('details', function ($q) use ($filters) {
                $q->where('item_id', $filters['item_id']);
            });
        }

        if (!empty($filters['uom_id'])) {
            $query->whereHas('details', function ($q) use ($filters) {
                $q->where('uom_id', $filters['uom_id']);
            });
        }

        if (!empty($filters['remarks'])) {
            $query->whereHas('details', function ($q) use ($filters) {
                $q->where('remarks', 'LIKE', "%{$filters['remarks']}%");
            });
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
                    'label' => $item->osa_code,
                    'value' => $item->id,
                ];
            });
        }

        $caps = $query->paginate($perPage);
        $caps->getCollection()->transform(function ($cap) {

            $workflowRequest = \App\Models\HtappWorkflowRequest::where('process_type', 'Ht_Caps_Header')
                ->where('process_id', $cap->id)
                ->latest()
                ->first();

            if ($workflowRequest) {

                $currentStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                    ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
                    ->orderBy('step_order')
                    ->first();

                $totalSteps = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)->count();

                $approvedSteps = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                    ->where('status', 'APPROVED')
                    ->count();

                $lastApprovedStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                    ->where('status', 'APPROVED')
                    ->orderBy('step_order', 'desc')
                    ->first();

                $cap->approval_status = $lastApprovedStep
                    ? $lastApprovedStep->message
                    : 'Initiated';

                $cap->current_step = $currentStep?->title;
                $cap->progress     = $totalSteps > 0 ? "{$approvedSteps}/{$totalSteps}" : null;
            } else {
                $cap->approval_status = null;
                $cap->current_step    = null;
                $cap->progress        = null;
            }

            return $cap;
        });

        return $caps;
    }

    //  public function getByUuid(string $uuid)
    //     {
    //         try {
    //             $current = HtCapsHeader::with([
    //                 'details' => function ($q) {
    //                     $q->with(['item', 'itemuom']);
    //                 },
    //             ])->where('uuid', $uuid)->first();

    //             if (!$current) {
    //                 return null;
    //             }
    //             $previousUuid = HtCapsHeader::where('id', '<', $current->id)
    //                 ->orderBy('id', 'desc')
    //                 ->value('uuid');

    //             $nextUuid = HtCapsHeader::where('id', '>', $current->id)
    //                 ->orderBy('id', 'asc')
    //                 ->value('uuid');

    //             $current->previous_uuid = $previousUuid;
    //             $current->next_uuid = $nextUuid;

    //             return $current;
    //         } catch (\Exception $e) {
    //             Log::error("CapsHService::getByUuid Error: " . $e->getMessage());
    //             return null;
    //         }
    //     }


    public function getByUuid(string $uuid)
    {
        try {
            $current = HtCapsHeader::with([
                'details' => function ($q) {
                    $q->with(['item', 'itemuom']);
                },
            ])->where('uuid', $uuid)->first();

            if (!$current) {
                return null;
            }

            // 🔹 Previous / Next
            $current->previous_uuid = HtCapsHeader::where('id', '<', $current->id)
                ->orderBy('id', 'desc')
                ->value('uuid');

            $current->next_uuid = HtCapsHeader::where('id', '>', $current->id)
                ->orderBy('id', 'asc')
                ->value('uuid');

            /**
             * =====================================================
             * 🔥 APPROVAL (OLD FORMAT – SAME AS LIST)
             * =====================================================
             */
            $workflowRequest = DB::table('htapp_workflow_requests')
                ->where('process_type', 'Ht_Caps_Header')
                ->where('process_id', $current->id)
                ->latest()
                ->first();

            $current->approval_status = null;
            $current->current_step    = null;
            $current->request_step_id = null;
            $current->progress        = null;

            if ($workflowRequest) {

                $currentStep = DB::table('htapp_workflow_request_steps')
                    ->where('workflow_request_id', $workflowRequest->id)
                    ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
                    ->orderBy('step_order')
                    ->first();

                $totalSteps = DB::table('htapp_workflow_request_steps')
                    ->where('workflow_request_id', $workflowRequest->id)
                    ->count();

                $approvedSteps = DB::table('htapp_workflow_request_steps')
                    ->where('workflow_request_id', $workflowRequest->id)
                    ->where('status', 'APPROVED')
                    ->count();

                $lastApprovedStep = DB::table('htapp_workflow_request_steps')
                    ->where('workflow_request_id', $workflowRequest->id)
                    ->where('status', 'APPROVED')
                    ->orderBy('step_order', 'desc')
                    ->first();

                $current->approval_status = $lastApprovedStep
                    ? $lastApprovedStep->message
                    : 'Initiated';

                $current->current_step    = $currentStep->title ?? null;
                $current->request_step_id = $currentStep->id ?? null;
                $current->progress        = $totalSteps > 0
                    ? "{$approvedSteps}/{$totalSteps}"
                    : null;
            }

            return $current;
        } catch (\Exception $e) {
            Log::error("CapsHService::getByUuid Error: " . $e->getMessage());
            return null;
        }
    }

    public function updateCapsByUuid(string $uuid, array $data)
    {
        return DB::transaction(function () use ($uuid, $data) {
            $header = HtCapsHeader::with('details')
                ->where('uuid', $uuid)
                ->lockForUpdate()
                ->firstOrFail();

            $header->update([
                'osa_code'     => $data['osa_code'] ?? $header->osa_code,
                'warehouse_id' => $data['warehouse_id'] ?? $header->warehouse_id,
                'driver_id'    => $data['driver_id'] ?? $header->driver_id,
                'truck_no'     => $data['truck_no'] ?? $header->truck_no,
                'contact_no'   => $data['contact_no'] ?? $header->contact_no,
                'claim_no'     => $data['claim_no'] ?? $header->claim_no,
                'claim_date'   => $data['claim_date'] ?? $header->claim_date,
                'claim_amount' => $data['claim_amount'] ?? $header->claim_amount,
                'status'       => $data['status'] ?? $header->status,
            ]);

            $warehouseId = $header->warehouse_id;
            foreach ($header->details as $oldDetail) {

                $qtyRow = CapsCollectionQty::where('warehouse_id', $warehouseId)
                    ->where('item_id', $oldDetail->item_id)
                    ->lockForUpdate()
                    ->first();

                if ($qtyRow) {
                    $qtyRow->increment('quantity', $oldDetail->quantity);
                }
            }
            HtCapsDetail::where('header_id', $header->id)->delete();
            foreach ($data['details'] as $detail) {

                HtCapsDetail::create([
                    'header_id'      => $header->id,
                    'osa_code'       => $detail['osa_code'] ?? null,
                    'item_id'        => $detail['item_id'],
                    'uom_id'         => $detail['uom_id'],
                    'quantity'       => $detail['quantity'] ?? 0,
                    'receive_qty'    => $detail['receive_qty'] ?? 0,
                    'receive_amount' => $detail['receive_amount'] ?? 0,
                    'receive_date'   => $detail['receive_date'],
                    'remarks'        => $detail['remarks'] ?? null,
                    'remarks2'       => $detail['remarks2'] ?? null,
                    'status'         => $detail['status'] ?? 0,
                ]);

                $qtyRow = CapsCollectionQty::where('warehouse_id', $warehouseId)
                    ->where('item_id', $detail['item_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$qtyRow) {
                    throw new \Exception(
                        "No stock available for item ID {$detail['item_id']} in warehouse {$warehouseId}"
                    );
                }

                if ($qtyRow->quantity < $detail['quantity']) {
                    throw new \Exception(
                        "Insufficient stock for item ID {$detail['item_id']} in warehouse {$warehouseId}"
                    );
                }

                $qtyRow->decrement('quantity', $detail['quantity']);
            }

            return $header->load('details');
        });
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

        $query = HtCapsHeader::latest('id');

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

        // Status filter
        if (isset($filter['status'])) {
            $query->where('status', $filter['status']);
        }

        // Delivery date range
        if (!empty($filter['from_date'])) {
            $query->whereDate('created_at', '>=', $filter['from_date']);
        }

        if (!empty($filter['to_date'])) {
            $query->whereDate('created_at', '<=', $filter['to_date']);
        }

        return $query->paginate($perPage);
    }
}
