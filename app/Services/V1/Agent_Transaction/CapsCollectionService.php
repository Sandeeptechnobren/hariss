<?php

namespace App\Services\V1\Agent_transaction;

use App\Models\Agent_Transaction\CapsCollectionHeader;
use App\Models\Agent_Transaction\CapsCollectionDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;
use App\Helpers\DataAccessHelper;
use App\Helpers\CommonLocationFilter;
use App\Models\CapsCollectionQty;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;

class CapsCollectionService
{
    public function create(array $data): ?CapsCollectionHeader
    {
        try {
            DB::beginTransaction();

            $code = $data['code'] ?? null;
            $header = CapsCollectionHeader::create([
                'warehouse_id' => $data['warehouse_id'],
                'route_id' => $data['route_id'] ?? null,
                'salesman_id' => $data['salesman_id'] ?? null,
                'customer' => $data['customer'],
                'contact_no' => $data['Contact_no'] ?? null,
                'status' => $data['status'] ?? 1,
                'code' => $code
            ]);

            if (empty($code)) {

                $prefix = 'CAPHD';
                $year = now()->year;

                $counter = DB::table('code_counters')
                    ->where('prefix', $prefix)
                    ->where('year', $year)
                    ->lockForUpdate()
                    ->first();

                if (!$counter) {
                    DB::table('code_counters')->insert([
                        'prefix' => $prefix,
                        'current_value' => 1,
                        'year' => $year,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $nextNumber = 1;
                } else {
                    $nextNumber = $counter->current_value + 1;
                    DB::table('code_counters')
                        ->where('id', $counter->id)
                        ->update([
                            'current_value' => $nextNumber,
                            'updated_at' => now(),
                        ]);
                }

                $generatedCode = $prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

                $header->update([
                    'code' => $generatedCode
                ]);
            }

            if (!empty($data['details']) && is_array($data['details'])) {

                foreach ($data['details'] as $detail) {

                    CapsCollectionDetail::create([
                        'header_id' => $header->id,
                        'item_id' => $detail['item_id'],
                        'uom_id' => $detail['uom_id'],
                        'price' => $detail['price'] ?? null,
                        'total' => $detail['total'] ?? null,
                        'collected_quantity' => $detail['collected_quantity'] ?? null,
                    ]);


                    $warehouseId = $header->warehouse_id;
                    $itemId = $detail['item_id'];
                    $qty = $detail['collected_quantity'];

                    $existingQty = CapsCollectionQty::where('warehouse_id', $warehouseId)
                        ->where('item_id', $itemId)
                        ->lockForUpdate()
                        ->first();

                    if ($existingQty) {
                        $existingQty->update([
                            'quantity' => $existingQty->quantity + $qty
                        ]);
                    } else {
                        CapsCollectionQty::create([
                            'warehouse_id' => $warehouseId,
                            'item_id' => $itemId,
                            'quantity' => $qty
                        ]);
                    }
                }
            }

            DB::commit();
            return $header->load('details.item', 'details.uom');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('CapsCollectionService::create Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getAll(int $perPage, array $filters = [], bool $dropdown = false)
    {
        $user = auth()->user();
        $query = CapsCollectionHeader::with([
            'customerdata:id,osa_code,name',
            'warehouse:id,warehouse_name,warehouse_code',
            'route:id,route_name,route_code',
            'salesman:id,name,osa_code',
            'details.item:id,code,name',
            'details.uom:id,name',
        ])
        ->withSum('details as itemtotal', 'collected_quantity');
        $query = DataAccessHelper::filterAgentTransaction($query, $user);
        if (!empty($filters['warehouse_id'])) {

            $warehouseIds = is_array($filters['warehouse_id'])
                ? $filters['warehouse_id']
                : explode(',', $filters['warehouse_id']);

            $warehouseIds = array_map('intval', $warehouseIds);

            $query->whereIn('warehouse_id', $warehouseIds);
        }
        if (!empty($filters['route_id'])) {

            $routeIds = is_array($filters['route_id'])
                ? $filters['route_id']
                : explode(',', $filters['route_id']);

            $routeIds = array_map('intval', $routeIds);

            $query->whereIn('route_id', $routeIds);
        }
        if (!empty($filters['salesman_id'])) {

            $salesmanIds = is_array($filters['salesman_id'])
                ? $filters['salesman_id']
                : explode(',', $filters['salesman_id']);

            $salesmanIds = array_map('intval', $salesmanIds);

            $query->whereIn('salesman_id', $salesmanIds);
        }
        if (!empty($filters['customer'])) {
            $query->where('customer', 'ILIKE', '%' . $filters['customer'] . '%');
        }
        if (!empty($filters['code'])) {
            $query->where('code', 'ILIKE', '%' . $filters['code'] . '%');
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
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);
        if ($dropdown) {
            return $query->get()->map(function ($collection) {
                return [
                    'id'    => $collection->id,
                    'label' => $collection->code,
                    'value' => $collection->id,
                ];
            });
        }
        return $query->paginate($perPage);
    }

    public function getByUuid(string $uuid)
    {
        try {
            return CapsCollectionHeader::with([
                'warehouse',
                'customerdata',
                'route',
                'salesman',
                'details.item',
                'details.uom',
            ])->where('uuid', $uuid)->first();
        } catch (\Exception $e) {
            \Log::error('CapsCollectionService::getByUuid Error: ' . $e->getMessage());
            return null;
        }
    }

    public function delete(string $uuid): bool
    {
        try {
            DB::beginTransaction();

            $header = CapsCollectionHeader::where('uuid', $uuid)->first();

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
            \Log::error('CapsCollectionService::delete Error: ' . $e->getMessage());
            return false;
        }
    }

    public function updateOrdersStatus(array $capsUuids, int $status): bool
    {
        return CapsCollectionHeader::whereIn('uuid', $capsUuids)
            ->update(['status' => $status]) > 0;
    }

    // public function update(string $uuid, array $data): ?CapsCollectionHeader
    // {
    //     try {
    //         DB::beginTransaction();

    //         $header = CapsCollectionHeader::where('uuid', $uuid)->first();

    //         if (!$header) {
    //             DB::rollBack();
    //             return null;
    //         }

    //         $headerData = [];

    //         if (isset($data['code'])) $headerData['code'] = $data['code'];
    //         if (isset($data['warehouse_id'])) $headerData['warehouse_id'] = $data['warehouse_id'];
    //         if (isset($data['route_id'])) $headerData['route_id'] = $data['route_id'];
    //         if (isset($data['salesman_id'])) $headerData['salesman_id'] = $data['salesman_id'];
    //         if (isset($data['customer'])) $headerData['customer'] = $data['customer'];
    //         if (isset($data['status'])) $headerData['status'] = $data['status'];

    //         if (!empty($headerData)) {
    //             $header->update($headerData);
    //         }

    //         if (isset($data['details']) && is_array($data['details'])) {
    //             $existingDetailIds = $header->details()->pluck('id')->toArray();
    //             $updatedDetailIds = [];

    //             foreach ($data['details'] as $detail) {
    //                 if (!empty($detail['id'])) {
    //                     $capsDetail = CapsCollectionDetail::where('id', $detail['id'])
    //                         ->where('header_id', $header->id)
    //                         ->first();

    //                     if ($capsDetail) {
    //                         $capsDetail->update([
    //                             'item_id'            => $detail['item_id'],
    //                             'uom_id'             => $detail['uom_id'] ?? null,
    //                             'collected_quantity' => $detail['collected_quantity'] ?? 0,
    //                             'status'             => $detail['status'] ?? 1,
    //                         ]);

    //                         $updatedDetailIds[] = $detail['id'];
    //                     }
    //                 } else {
    //                     $newDetail = CapsCollectionDetail::create([
    //                         'uuid'               => Str::uuid()->toString(),
    //                         'header_id'          => $header->id,
    //                         'item_id'            => $detail['item_id'],
    //                         'uom_id'             => $detail['uom_id'] ?? null,
    //                         'collected_quantity' => $detail['collected_quantity'] ?? 0,
    //                         'status'             => $detail['status'] ?? 1,
    //                     ]);

    //                     $updatedDetailIds[] = $newDetail->id;
    //                 }
    //             }

    //             $detailsToDelete = array_diff($existingDetailIds, $updatedDetailIds);
    //             if (!empty($detailsToDelete)) {
    //                 CapsCollectionDetail::whereIn('id', $detailsToDelete)->delete();
    //             }
    //         }

    //         DB::commit();

    //         return $this->getByUuid($header->uuid);
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         Log::error('CapsCollectionService::update Error: ' . $e->getMessage());
    //         throw $e;
    //     }
    // }
    public function update(string $uuid, array $data): ?CapsCollectionHeader
    {
        try {
            DB::beginTransaction();

            $header = CapsCollectionHeader::where('uuid', $uuid)->first();

            if (!$header) {
                DB::rollBack();
                return null;
            }

            $headerData = [];
            foreach (['code', 'warehouse_id', 'route_id', 'salesman_id', 'customer', 'status'] as $field) {
                if (isset($data[$field])) {
                    $headerData[$field] = $data[$field];
                }
            }
            if (!empty($headerData)) {
                $header->update($headerData);
            }

            $existingDetails = $header->details()->get();
            $updatedDetailIds = [];

            if (isset($data['details']) && is_array($data['details'])) {
                foreach ($data['details'] as $detail) {
                    $capsDetail = CapsCollectionDetail::where('header_id', $header->id)
                        ->where('item_id', $detail['item_id'])
                        ->first();

                    if ($capsDetail) {
                        $capsDetail->update([
                            'uom_id'             => $detail['uom_id'] ?? null,
                            'collected_quantity' => $detail['collected_quantity'] ?? 0,
                            'status'             => $detail['status'] ?? 1,
                        ]);
                        $updatedDetailIds[] = $capsDetail->id;
                    } else {
                        $newDetail = CapsCollectionDetail::create([
                            'uuid'               => \Str::uuid()->toString(),
                            'header_id'          => $header->id,
                            'item_id'            => $detail['item_id'],
                            'uom_id'             => $detail['uom_id'] ?? null,
                            'collected_quantity' => $detail['collected_quantity'] ?? 0,
                            'status'             => $detail['status'] ?? 1,
                        ]);
                        $updatedDetailIds[] = $newDetail->id;
                    }
                }

                $detailsToDelete = $existingDetails->filter(function ($existing) use ($data) {
                    foreach ($data['details'] as $updated) {
                        if ($existing->item_id == $updated['item_id']) {
                            return false;
                        }
                    }
                    return true;
                })->pluck('id')->toArray();

                if (!empty($detailsToDelete)) {
                    CapsCollectionDetail::whereIn('id', $detailsToDelete)->delete();
                }
            }

            DB::commit();

            return $this->getByUuid($header->uuid);
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('CapsCollectionService::update Error: ' . $e->getMessage());
            throw $e;
        }
    }



    public function getQtyByWarehouseAndItem(int $warehouseId, int $itemId): ?array
    {
        $record = CapsCollectionQty::where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->select('warehouse_id', 'item_id', 'quantity')
            ->first();

        if (! $record) {
            return null;
        }

        return [
            'warehouse_id' => $record->warehouse_id,
            'item_id'      => $record->item_id,
            'quantity'     => $record->quantity,
        ];
    }



    public function globalFilter(array $filters = [])
    {
        $user   = auth()->user();
        $filter = $filters['filter'] ?? [];

        $perPage = max((int) ($filters['per_page'] ?? 50), 1);
        $page    = max((int) ($filters['current_page'] ?? 1), 1);

        // 📄 Force current page
        Paginator::currentPageResolver(fn() => $page);

        $query = CapsCollectionHeader::with([
            'customerdata:id,osa_code,name',
            'warehouse:id,warehouse_name,warehouse_code',
            'route:id,route_name,route_code',
            'salesman:id,name,osa_code',
            'details.item:id,code,name',
            'details.uom:id,name',
        ])
        ->latest()
        ->withSum('details as itemtotal', 'total');

        $query = DataAccessHelper::filterAgentTransaction($query, $user);

        // 🌍 Location hierarchy
        $warehouseIds = CommonLocationFilter::resolveWarehouseIds([
            'company_id'   => $filter['company_id']   ?? null,
            'region_id'    => $filter['region_id']    ?? null,
            'area_id'      => $filter['area_id']      ?? null,
            'warehouse_id' => $filter['warehouse_id'] ?? null,
            'route_id'     => $filter['route_id']     ?? null,
        ]);

        if ($warehouseIds) {
            $query->whereIn('warehouse_id', $warehouseIds);
        }

        // 👨‍💼 Salesman
        if (!empty($filter['salesman_id'])) {
            $ids = is_array($filter['salesman_id'])
                ? $filter['salesman_id']
                : explode(',', $filter['salesman_id']);

            $query->whereIn('salesman_id', array_map('intval', $ids));
        }

        // 👤 Customer
        if (!empty($filter['customer_id'])) {
            $query->where('customer', (int) $filter['customer_id']);
        }

        // 📅 Date range
        if (!empty($filter['from_date'])) {
            $query->whereDate('created_at', '>=', $filter['from_date']);
        }
        if (!empty($filter['to_date'])) {
            $query->whereDate('created_at', '<=', $filter['to_date']);
        }

        // 📌 Status
        if (isset($filter['status'])) {
            $query->where('status', (int) $filter['status']);
        }

        return $query->paginate($perPage);
    }
}
