<?php

// namespace App\Services\V1\EfrisAPI;

// use App\Models\EfrisAPI\EfrisItemSyncFlag;
// use App\Models\Item;
// use App\Models\Warehouse;
// use App\Models\EfrisAPI\EfrisSyncLogs;
// use Illuminate\Support\Facades\DB;

// class EfrisStockSyncService
// {
//     protected $api;

//     public function __construct(EfrisApiService $api)
//     {
//         $this->api = $api;
//     }

//     public function syncItems($itemId)
//     {

        
//         $items = $itemId === 'all'
//             ? Item::with(['uoms' => function ($q) {
//                 $q->whereNotNull('price')
//                     ->where('price', '!=', 0)
//                     ->where('price', '!=', '');
//             }])
//             ->where('status', 1)
//             ->whereHas('uoms', function ($q) {
//                 $q->whereNotNull('price')
//                     ->where('price', '!=', 0)
//                     ->where('price', '!=', '');
//             })
//             ->get()
//             : Item::with('uoms')->where('id', $itemId)->get();
        
//         $warehouse = Warehouse::where('id', 2054)->first();
//         // dd($warehouse);
//         // foreach ($warehouses as $warehouse) {
//         foreach ($items as $item) {
//             if ($item->uoms->isEmpty()) continue;

//             $operationType = $this->getOperationType($item, $warehouse);

//             $payload = $this->buildPayload($item, $operationType);
            
//             // $jsonPayload = json_encode($payload);

//             // dd($payload);
//             $response = $this->api->post("T130", $payload, $warehouse);

//             $results[] = [
//                 'item_id' => $item->id,
//                 'warehouse_id' => $warehouse->id,
//                 'response' => $response
//             ];
//             $this->handleResponse($item, $warehouse, $response, $operationType, $payload);
//         }
//         // }

//         return $results;
//     }

//     private function getOperationType($item, $warehouse)
//     {
//         $exists = EfrisItemSyncFlag::where('item_id', $item->id)
//             ->where('warehouse_id', $warehouse->id)
//             ->where('is_synced', 1)
//             ->exists();

//         return $exists ? 102 : 101;
//     }

//     private function getuomsCode($uomId)
//     {
//         $map = [
//             1 => 'PP',
//             2 => 'CS',
//             3 => 'PP',
//             4 => '110',
//         ];

//         return $map[$uomId] ?? '';
//     }

//     private function buildPayload($item, $operationType)
//     {
//         $uoms = $item->uoms;
//         if ($uoms->isEmpty()) return [];

//         $uom_id = $uoms->sortByDesc('upc')->values();

//         $measureUnit = '';
//         $unitPrice = 0;

//         $pieceMeasureUnit = '';
//         $pieceUnitPrice = 0;

//         $pieceScaledValue = 1;

//         $otherUnits = [];

//         foreach ($uom_id as $index => $uom) {

//             if (in_array($uom->uom_id, [2, 4]) && $measureUnit == '') {

//                 $measureUnit = $uom->uom_id == 2 ? 'CS' : '110';
//                 $unitPrice = $uom->price ?? 0;
//             }

//             if (in_array($uom->uom_id, [1, 3]) && $pieceMeasureUnit == '') {

//                 $pieceMeasureUnit = 'PP';
//                 $pieceScaledValue = $uom->upc ?? 1;
//                 $pieceUnitPrice = $uom->price ?? 0;
//             }
//         }

//         foreach ($uom_id->skip(1) as $uom) {
//             $otherUnits[] = [
//                 "otherUnit" => $this->getuomsCode($uom->uom_id),
//                 "otherPrice" => (string)($uom->price ?? 0),
//                 "otherScaled" => (string)($uom->upc ?? 1),
//                 "packageScaled" => "1"
//             ];
//         }

//         return [
//             "operationType" => (string)$operationType,
//             "goodsName" => $item->name ?? '',
//             "goodsCode" => $item->sap_id ?? '',
//             "measureUnit" => $measureUnit,
//             "unitPrice" => (string)$unitPrice,
//             "currency" => "101",
//             "commodityCategoryId" => $item->commodity_goods_code ?? '',
//             "haveExciseTax" => "101",
//             "stockPrewarning" => "0",
//             "havePieceUnit" => "101",
//             "pieceMeasureUnit" => $pieceMeasureUnit,
//             "pieceUnitPrice" => (string)$pieceUnitPrice,
//             "packageScaledValue" => "1",
//             "pieceScaledValue" => (string)$pieceScaledValue,
//             // "goodsOtherUnits" => array()
//             "goodsOtherUnits" => $otherUnits
//         ];
//     }

//     private function handleResponse($item, $warehouse, $response, $operationType, $payload)
//     {
//         $isSuccess = $response['success'] ?? false;
//         $message   = $response['message'] ?? null;

//         EfrisSyncLogs::updateOrCreate(
//             [
//                 'item_id' => $item->id,
//                 'warehouse_id' => $warehouse->id,
//                 'interface_code' => 'T130',
//             ],
//             [
//                 'operation_type' => $operationType,
//                 'request_payload' => $payload,
//                 'response_payload' => $response,
//                 'is_success' => $isSuccess,
//                 'error_message' => $isSuccess ? null : $message,
//                 'synced_at' => now(),
//             ]
//         );

//         if ($isSuccess) {
//             EfrisItemSyncFlag::updateOrCreate(
//                 [
//                     'item_id' => $item->id,
//                     'warehouse_id' => $warehouse->id
//                 ],
//                 [
//                     'is_synced' => true
//                 ]
//             );
//         }
//     }
// }
namespace App\Services\V1\EfrisAPI;

use App\Models\EfrisAPI\EfrisItemSyncFlag;
use App\Models\Item;
use App\Models\Warehouse;
use App\Models\EfrisAPI\EfrisSyncLogs;

class EfrisStockSyncService
{
    protected $api;

    public function __construct(EfrisApiService $api)
    {
        $this->api = $api;
    }

    public function syncItems($itemId)
    {
        $results = [];

        $items = $itemId === 'all'
            ? Item::with('uoms')->where('status', 1)->get()
            : Item::with('uoms')->where('id', $itemId)->get();

        $warehouse = Warehouse::find(2054);

        if (!$warehouse) {
            return ['error' => 'Warehouse not found'];
        }

        foreach ($items as $item) {

            if ($item->uoms->isEmpty()) continue;

            $operationType = $this->getOperationType($item, $warehouse);

            $payload = $this->buildPayload($item, $operationType);

            if (empty($payload)) continue;

            \Log::info("EFRIS T130 PAYLOAD", $payload);

            $response = $this->api->post("T130", $payload, $warehouse);

            $results[] = [
                'item_id' => $item->id,
                'response' => $response
            ];

            $this->handleResponse($item, $warehouse, $response, $operationType, $payload);
        }

        return $results;
    }

    private function getOperationType($item, $warehouse)
    {
        return EfrisItemSyncFlag::where('item_id', $item->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('is_synced', 1)
            ->exists() ? 102 : 101;
    }

    private function buildPayload($item, $operationType)
    {
        $uom = $item->uoms->first();

        return [
            "operationType" => (string)$operationType,
            "goodsName" => $item->name,
            "goodsCode" => $item->sap_id,
            "measureUnit" => "PP",
            "unitPrice" => (string)($uom->price ?? 0),
            "currency" => "101",
            "commodityCategoryId" => $item->commodity_goods_code ?? "101",
            "haveExciseTax" => "102",
            "stockPrewarning" => "0",
            "havePieceUnit" => "102",
            "goodsOtherUnits" => []
        ];
    }

    private function handleResponse($item, $warehouse, $response, $operationType, $payload)
    {
        $isSuccess = $response['success'] ?? false;

        EfrisSyncLogs::updateOrCreate(
            [
                'item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
                'interface_code' => 'T130',
            ],
            [
                'operation_type' => $operationType,
                'request_payload' => json_encode($payload),
                'response_payload' => json_encode($response),
                'is_success' => $isSuccess,
                'error_message' => $response['message'] ?? null,
                'synced_at' => now(),
            ]
        );
    }
}