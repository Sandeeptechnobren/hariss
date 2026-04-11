<?php

namespace App\Services\V1\EfrisAPI;

use App\Models\Warehouse;
use App\Models\Hariss_Transaction\Web\HTDeliveryHeader;
use App\Models\Hariss_Transaction\Web\HTDeliveryDetail;
use App\Models\Uom;
use App\Models\Item;
use App\Models\ItemUOM;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UraDeliverySyncService extends BaseEfrisService
{

    public function getUnsyncDelivery(array $filters)
    {
        try {
            $warehouseIds = $filters['warehouse_id'] ?? [];
            $fromDate     = $filters['from_date'] ?? null;
            $toDate       = $filters['to_date'] ?? null;

            if (empty($warehouseIds) || !$fromDate || !$toDate) {
                return [];
            }

            if (!is_array($warehouseIds)) {
                $warehouseIds = [$warehouseIds];
            }

            // ✅ Step 1: Get live_date
            $warehouse = Warehouse::whereIn('id', $warehouseIds)
                ->where('status', 1)
                ->select('live_date')
                ->first();

            if (!$warehouse || empty($warehouse->live_date)) {
                return [];
            }

            // ✅ Step 2: Pure Eloquent Query (NO TABLE NAME)
            $data = HTDeliveryHeader::with(['customer', 'warehouse'])

                ->whereHas('customer.warehouses', function ($q) use ($warehouseIds) {
                    $q->whereIn('id', $warehouseIds);
                })

                ->whereBetween('delivery_date', [$fromDate, $toDate])
                ->whereDate('updated_at', '>=', $warehouse->live_date)
                ->where('status', 1)
                ->where('sync_efriss', '0')
                ->orderBy('id', 'ASC')
                ->get()

                ->map(function ($item) {
                    return [
                        'id'              => $item->id,
                        'delivery_uuid'   => $item->uuid,
                        'delivery_code'   => $item->delivery_code,
                        'delivery_date'   => $item->delivery_date,

                        'warehouse_id'    => $item->warehouse->id ?? null,
                        'warehouse_name'  => $item->warehouse->warehouse_name ?? null,
                        'warehouse_code'  => $item->warehouse->warehouse_code ?? null,

                        'customer_uuid'   => $item->customer->uuid ?? null,
                        'customer_code'   => $item->customer->osa_code ?? null,
                        'customer_name'   => $item->customer->business_name ?? null,
                        'sap_code'   => $item->sap_id ?? null,
                        'sync_date'   => $item->updated_at ?? null,
                    ];
                });

            return $data;
        } catch (\Throwable $e) {

            Log::error('HT Delivery Filter Error', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile()
            ]);

            return [];
        }
    }


    public function syncDelivery($deliveryId)
    {
        if (!$deliveryId) {
            return ['status' => false, 'message' => 'Invalid delivery id'];
        }

        $header = HTDeliveryHeader::find($deliveryId);
        if (!$header) {
            return ['status' => false, 'message' => 'Delivery not found'];
        }

        $warehouse = Warehouse::where('id', $header->warehouse_id)
            ->where('is_efris', 1)
            ->where('status', 1)
            ->first();

        if (!$warehouse) {
            return ['status' => false, 'message' => 'EFRIS warehouse not found'];
        }

        $details = HTDeliveryDetail::where('header_id', $deliveryId)->get();
        if ($details->isEmpty()) {
            return ['status' => false, 'message' => 'No delivery items found'];
        }

        // ✅ preload
        $items = Item::whereIn('id', $details->pluck('item_id'))
            ->select('id', 'sap_id')
            ->get()
            ->keyBy('id');

        $uoms = Uom::whereIn('id', $details->pluck('uom_id'))
            ->get()
            ->keyBy('id');

        $itemUoms = ItemUOM::whereIn('item_id', $details->pluck('item_id'))
            ->whereIn('uom_id', $details->pluck('uom_id'))
            ->where('status', 1)
            ->get()
            ->groupBy(fn($q) => $q->item_id . '_' . $q->uom_id);

        $goodsStockInItem = [];

        foreach ($details as $item) {

            $itemData = $items[$item->item_id] ?? null;
            if (!$itemData) {
                return ['status' => false, 'message' => "Item not found {$item->item_id}"];
            }

            $goodsCode = $itemData->sap_id;
            if (empty($goodsCode)) {
                return ['status' => false, 'message' => "GoodsCode missing {$item->item_id}"];
            }

            $uom = $uoms[$item->uom_id] ?? null;
            if (!$uom || empty($uom->sap_name)) {
                return ['status' => false, 'message' => "Invalid UOM {$item->uom_id}"];
            }

            $item_uom = trim(explode(',', $uom->sap_name)[0]);

            $key = $item->item_id . '_' . $item->uom_id;
            $itemUom = $itemUoms[$key][0] ?? null;

            if (!$itemUom) {
                return ['status' => false, 'message' => "Price missing {$item->item_id}"];
            }

            $price = $itemUom->price;
            if ($price <= 0) {
                return ['status' => false, 'message' => "Invalid price {$item->item_id}"];
            }

            // 🔥 ITEM DEBUG
            $this->logEfrisDebug('ITEM_DATA', [
                'item_id' => $item->item_id,
                'uom_id' => $item->uom_id,
                'goodsCode' => $goodsCode,
                'measureUnit' => $item_uom,
                'price' => $price,
                'qty' => $item->quantity
            ]);

            $goodsStockInItem[] = [
                "commodityGoodsId" => "",
                "goodsCode" => $goodsCode,
                "measureUnit" => $item_uom,
                "quantity" => (string) $item->quantity,
                "unitPrice" => (string) $price,
                "remarks" => "remarks",
                "fuelTankId" => "",
                "lossQuantity" => "",
                "originalQuantity" => ""
            ];
        }

        $payload = [
            "goodsStockIn" => [
                "operationType" => 101,
                "supplierTin" => "1000032087",
                "supplierName" => "HARISS INTERNATIONAL LIMITED",
                "remarks" => $header->sap_id,
                "stockInDate" => now()->format('Y-m-d'),
                "stockInType" => 102,
                "branchId" => (string) $warehouse->branch_id,
                "isCheckBatchNo" => "0",
                "rollBackIfError" => "0",
                "goodsTypeCode" => "101"
            ],
            "goodsStockInItem" => $goodsStockInItem
        ];

        // 🔥 DEBUG BEFORE API
        $this->logEfrisDebug('REQUEST_PAYLOAD', $payload);

        $response = $this->makePost("T131", $payload, $warehouse);

        // 🔥 DEBUG AFTER API
        $this->logEfrisDebug('API_RESPONSE', $response);
        $this->logEfrisDebug('INNER_RESPONSE', $response['inner_response'] ?? []);

        if (($response['returnCode'] ?? null) == "00") {

            DB::beginTransaction();
            try {
                DB::table('ht_delivery_header')
                    ->where('id', $deliveryId)
                    ->update([
                        'sync_efriss' => 1,
                        'updated_at' => now()
                    ]);

                $this->logEfrisDebug('UPDATE_SUCCESS', [
                    'delivery_id' => $deliveryId
                ]);

                DB::commit();

                return [
                    'status' => true,
                    'message' => 'Delivery synced successfully',
                    'response' => $response
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                return [
                    'status' => false,
                    'message' => 'DB update failed',
                    'error' => $e->getMessage()
                ];
            }
        }

        DB::table('tbl_efriss_delivery_errorlog')->insert([
            'interface_code' => 'T131',
            'massage' => json_encode($response),
            'tin_no' => $warehouse->tin ?? null,
            'created_at' => now()
        ]);

        return [
            'status' => false,
            'message' => $response['returnMessage'] ?? 'EFRIS Error',
            'response' => $response
        ];
    }

    // ✅ DEBUG FUNCTION
    protected function logEfrisDebug($type, $data)
    {
        $path = storage_path('logs/efris_debug');

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $file = $path . '/debug_' . date('Y-m-d') . '.log';

        $log = "\n\n========== {$type} ==========\n";
        $log .= date('Y-m-d H:i:s') . "\n";
        $log .= json_encode($data, JSON_PRETTY_PRINT);

        file_put_contents($file, $log, FILE_APPEND);
    }
}
