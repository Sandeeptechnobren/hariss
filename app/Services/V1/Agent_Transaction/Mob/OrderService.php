<?php

namespace App\Services\V1\Agent_transaction\Mob;

use App\Models\Agent_Transaction\OrderHeader;
use App\Models\Agent_Transaction\OrderDetail;
use App\Models\Agent_Transaction\AgentDeliveryHeaders;
use App\Models\Agent_Transaction\AgentDeliveryDetails;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;
// use App\Helpers\DataAccessHelper;


class OrderService
{
public function getAll(int $perPage, array $filters = [], bool $dropdown = false)
{
    // $user = auth()->user();

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
    ]);

    // $query = DataAccessHelper::filterAgentTransaction($query, $user);

    if (isset($filters['order_flag'])) {
        $query->where('order_flag', $filters['order_flag']);
    }
    if (!empty($filters['no_delivery']) && $filters['no_delivery'] == true) {
        $query->where('order_flag', 1);
    }
    if (!empty($filters['search'])) {
        $search = $filters['search'];
        $query->where(function ($q) use ($search) {
            $q->where('order_code', 'LIKE', '%' . $search . '%')
                ->orWhere('comment', 'LIKE', '%' . $search . '%')
                ->orWhere('status', 'LIKE', '%' . $search . '%')
                ->orWhere('delivery_date', 'LIKE', '%' . $search . '%')
                ->orWhereHas('warehouse', function ($q2) use ($search) {
                    $q2->where('warehouse_code', 'LIKE', '%' . $search . '%')
                        ->orWhere('warehouse_name', 'LIKE', '%' . $search . '%')
                        ->orWhere('owner_email', 'LIKE', '%' . $search . '%')
                        ->orWhere('owner_number', 'LIKE', '%' . $search . '%')
                        ->orWhere('address', 'LIKE', '%' . $search . '%');
                })
                ->orWhereHas('customer', function ($q2) use ($search) {
                    $q2->where('osa_code', 'LIKE', '%' . $search . '%')
                        ->orWhere('name', 'LIKE', '%' . $search . '%')
                        ->orWhere('email', 'LIKE', '%' . $search . '%')
                        ->orWhere('street', 'LIKE', '%' . $search . '%')
                        ->orWhere('town', 'LIKE', '%' . $search . '%')
                        ->orWhere('contact_no', 'LIKE', '%' . $search . '%');
                })
                ->orWhereHas('route', function ($q2) use ($search) {
                    $q2->where('route_code', 'LIKE', '%' . $search . '%')
                        ->orWhere('route_name', 'LIKE', '%' . $search . '%');
                })
                ->orWhereHas('salesman', function ($q2) use ($search) {
                    $q2->where('osa_code', 'LIKE', '%' . $search . '%')
                        ->orWhere('name', 'LIKE', '%' . $search . '%');
                })
                ->orWhereHas('details', function ($q2) use ($search) {
                    $q2->where('quantity', 'LIKE', '%' . $search . '%')
                        ->orWhere('item_price', 'LIKE', '%' . $search . '%')
                        ->orWhere('vat', 'LIKE', '%' . $search . '%')
                        ->orWhere('discount', 'LIKE', '%' . $search . '%')
                        ->orWhere('gross_total', 'LIKE', '%' . $search . '%')
                        ->orWhere('net_total', 'LIKE', '%' . $search . '%')
                        ->orWhere('total', 'LIKE', '%' . $search . '%')
                        ->orWhereHas('item', function ($q3) use ($search) {
                            $q3->where('code', 'LIKE', '%' . $search . '%')
                                ->orWhere('name', 'LIKE', '%' . $search . '%')
                                ->orWhereHas('itemUoms', function ($q4) use ($search) {
                                    $q4->where('name', 'LIKE', '%' . $search . '%')
                                        ->orWhere('price', 'LIKE', '%' . $search . '%')
                                        ->orWhere('upc', 'LIKE', '%' . $search . '%')
                                        ->orWhere('uom_type', 'LIKE', '%' . $search . '%');
                                });
                        })
                        ->orWhereHas('uom', function ($q3) use ($search) {
                            $q3->where('name', 'LIKE', '%' . $search . '%');
                        });
                });
        });
    }

    if (!empty($filters['warehouse_id'])) {
        $query->where('warehouse_id', $filters['warehouse_id']);
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
        $query->where('salesman_id', $filters['salesman_id']);
    }

    if (!empty($filters['comment'])) {
        $query->where('comment', 'LIKE', '%' . $filters['comment'] . '%');
    }

    if (!empty($filters['status'])) {
        $query->where('status', $filters['status']);
    }

    if (!empty($filters['from_date'])) {
        $query->whereDate('created_at', '>=', $filters['from_date']);
    }

    if (!empty($filters['to_date'])) {
        $query->whereDate('created_at', '<=', $filters['to_date']);
    }

    if (!empty($filters['country_id'])) {
        $query->where('country_id', $filters['country_id']);
    }

    $query->when(!empty($filters['item_id']), function ($q) use ($filters) {
        $q->whereHas('details', function ($q2) use ($filters) {
            $q2->where('item_id', $filters['item_id']);
        });
    });

    $query->when(!empty($filters['item_name']), function ($q) use ($filters) {
        $q->whereHas('details', function ($q2) use ($filters) {
            $q2->whereHas('item', function ($q3) use ($filters) {
                $q3->where('name', 'LIKE', '%' . $filters['item_name'] . '%');
            });
        });
    });

    $query->when(!empty($filters['uom_id']), function ($q) use ($filters) {
        $q->whereHas('details', function ($q2) use ($filters) {
            $q2->where('uom_id', $filters['uom_id']);
        });
    });

    $query->when(!empty($filters['item_price']), function ($q) use ($filters) {
        $q->whereHas('details', function ($q2) use ($filters) {
            $q2->where('item_price', $filters['item_price']);
        });
    });

    $query->when(!empty($filters['quantity']), function ($q) use ($filters) {
        $q->whereHas('details', function ($q2) use ($filters) {
            $q2->where('quantity', $filters['quantity']);
        });
    });

    $query->when(!empty($filters['net_total']), function ($q) use ($filters) {
        $q->whereHas('details', function ($q2) use ($filters) {
            $q2->where('net_total', $filters['net_total']);
        });
    });

    $query->when(!empty($filters['gross_total']), function ($q) use ($filters) {
        $q->whereHas('details', function ($q2) use ($filters) {
            $q2->where('gross_total', $filters['gross_total']);
        });
    });

    $sortBy = $filters['sort_by'] ?? 'created_at';
    $sortOrder = $filters['sort_order'] ?? 'desc';
    $query->orderBy($sortBy, $sortOrder);

    if ($dropdown) {
        return $query->get()->map(function ($order) {
            return [
                'id' => $order->id,
                'label' => $order->order_code,
                'value' => $order->id,
            ];
        });
    }

    return $query->paginate($perPage);
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
            $warehouse = Warehouse::with('getCompany')->find($data['warehouse_id']);
 
            $company = $warehouse->getCompany;

            $header = OrderHeader::create([
                'order_code'    => $data['order_code'] ?? null,
                'warehouse_id'  => $data['warehouse_id'],
                'delivery_date' => $data['delivery_date'],
                'customer_id'   => $data['customer_id'],
                'comment'       => $data['comment'],
                'status'        => $data['status'] ?? 1,
                'currency'      => $company->selling_currency,
                // 'country_id'    => $data['country_id'],
                'route_id'      => $data['route_id'],
                'salesman_id'   => $data['salesman_id'] ?? null,
                // 'gross_total'   => $data['gross_total'] ?? 0,
                'vat'           => $data['vat'] ?? 0,
                'net_amount'    => $data['net_amount'] ?? 0,
                'total'         => $data['total'] ?? 0,
                // 'discount'      => $data['discount'] ?? 0,
                'latitude'         => $data['latitude'] ?? 0,
                'longitude'         => $data['longitude'] ?? 0,

            ]);

            if (!empty($data['details']) && is_array($data['details'])) {
                foreach ($data['details'] as $detail) {
                    OrderDetail::create([
                        'header_id'     => $header->id,
                        'item_id'       => $detail['item_id'],
                        'uom_id'        => $detail['uom_id'],
                        'status'        => $detail['status'] ?? 1,
                        'gross_total'   => $detail['gross_total'] ?? 0,
                        'net_total'     => $detail['net_total'] ?? 0,
                        'total'         => $detail['total'] ?? 0,
                        'item_price'    => $detail['item_price'] ?? 0,
                        'quantity'      => $detail['quantity'] ?? 0,
                        'vat'           => $detail['vat'] ?? 0,
                        // 'discount_id'   => $detail['discount_id'] ?? null,
                        'promotion_id'  => $detail['promotion_id'] ?? null,
                        // 'parent_id'     => $detail['parent_id'] ?? null,
                        // 'discount'      => $detail['discount'] ?? 0,
                        'is_promotional'=> $detail['is_promotional'] ?? false,
                    ]);
                }
            }
                $details = $data['details'] ?? [];
                $this->createDelivery($header, $details);
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
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('OrderService::create Error: ' . $e->getMessage());
            throw $e;
        }
    }
private function createDelivery(OrderHeader $order, array $details)
{
    $deliveryCode = $this->generateDeliveryCode();
    $delivery = AgentDeliveryHeaders::create([
        'uuid'          => Str::uuid(),
        'delivery_code' => $deliveryCode,
        'warehouse_id'  => $order->warehouse_id,
        'customer_id'   => $order->customer_id,
        'currency'      => $order->currency,
        'route_id'      => $order->route_id,
        'salesman_id'   => $order->salesman_id,
        'gross_total'   => $order->gross_total ?? 0,
        'vat'           => $order->vat,
        'net_amount'    => $order->net_amount,
        'total'         => $order->total,
        'order_code'    => $order->order_code,
        'comment'       => $order->comment,
        'latitude'      => $order->latitude,
        'longitude'     => $order->longitude,
        'delivery_date' => $order->delivery_date,
        'status'        => 1,
    ]);
    foreach ($details as $detail) {
        AgentDeliveryDetails::create([
            'uuid'          => Str::uuid(),
            'header_id'     => $delivery->id,
            'item_id'       => $detail['item_id'],
            'uom_id'        => $detail['uom_id'],
            'item_price'    => $detail['item_price'] ?? 0,
            'quantity'      => $detail['quantity'] ?? 0,
            'vat'           => $detail['vat'] ?? 0,
            'gross_total'   => $detail['gross_total'] ?? 0,
            'net_total'     => $detail['net_total'] ?? 0,
            'total'         => $detail['total'] ?? 0,
            'promotion_id'  => $detail['promotion_id'] ?? null,
            'is_promotional'=> $detail['is_promotional'] ?? false,
        ]);
    }
    $order->update([
        'order_flag' => 2, 
    ]);
    return $delivery;
}
private function generateDeliveryCode(?string $manualCode = null): string
{
    if ($manualCode) {
        return $manualCode;
    }

    $prefix = 'ADL';
    $date = now()->format('Ymd');

    do {
        $random = random_int(1000, 9999);
        $code = $prefix . $date . $random;
    } while (AgentDeliveryHeaders::where('delivery_code', $code)->exists());

    return $code;
}
}