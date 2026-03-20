<?php

namespace App\Services\V1\Agent_transaction\Mob;

use App\Models\Agent_Transaction\ReturnHeader;
use App\Models\Agent_Transaction\ReturnDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Helpers\DataAccessHelper;

class ReturnService
{
    public function create(array $data): ?ReturnHeader
    {
        try {
            DB::beginTransaction();
            $header = ReturnHeader::create([
                'osa_code'     => $data['osa_code'] ?? null,
                'currency'     => $data['currency'] ?? null,
                'country_id'   => $data['country_id'] ?? null,
                'order_id'     => $data['order_id'] ?? null,
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
            if (!empty($data['details']) && is_array($data['details'])) {
                foreach ($data['details'] as $detail) {
                    ReturnDetail::create([
                        'header_id'     => $header->id,
                        'header_code'   => $header->osa_code,
                        'item_id'       => $detail['item_id'],
                        'uom_id'        => $detail['uom_id'],
                        'discount_id'   => $detail['discount_id'] ?? null,
                        'promotion_id'  => $detail['promotion_id'] ?? null,
                        'parent_id'     => $detail['parent_id'] ?? null,
                        'item_price'    => $detail['item_price'] ?? 0,
                        'item_quantity' => $detail['item_quantity'] ?? 0,
                        'return_type'   => $detail['return_type'] ?? 0,
                        'return_reason' => $detail['return_reason'] ?? null,
                        'vat'           => $detail['vat'] ?? 0,
                        'discount'      => $detail['discount'] ?? 0,
                        'gross_total'   => $detail['gross_total'] ?? 0,
                        'net_total'     => $detail['net_total'] ?? 0,
                        'total'         => $detail['total'] ?? 0,
                        'is_promotional' => $detail['is_promotional'] ?? false,
                        'status'        => $detail['status'] ?? 1,
                    ]);
                }
            }

            DB::commit();

            return $header->load('details');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('ReturnService::create Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getAll(int $perPage, array $filters = [], bool $dropdown = false)
    {
        //$user = auth()->user();
        $query = ReturnHeader::with([
            'warehouse:id,warehouse_code,warehouse_name',
            'route:id,route_code,route_name',
            'customer:id,osa_code,name',
            'salesman:id,name,osa_code',
            // 'returntype:id,return_type',
            'createdBy:id,name',
            'updatedBy:id,name',
            'details.item:id,code,name',
        ]);
        
       //$query = DataAccessHelper::filterAgentTransaction($query, $user);
        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (!empty($filters['salesman_id'])) {
            $query->where('salesman_id', $filters['salesman_id']);
        }

        if (!empty($filters['osa_code'])) {
            $query->where('osa_code', 'LIKE', '%' . $filters['osa_code'] . '%');
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

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

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
// dd($query);
        return $query->paginate($perPage);
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
}
