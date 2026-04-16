<?php

// namespace App\Services\V1\EfrisAPI;

// use App\Models\Warehouse;
// use App\Models\Item;
// use App\Models\EfrisAPI\DailyStockCountDetail;
// use App\Models\EfrisAPI\EfrisStockAdjustmentLog;
// use App\Models\EfrisAPI\EfrisStockAdjustmentSnapshot;

// class UraStockAdjustmentService extends BaseEfrisService
// {
//     public function stockAdjustment($operationType, $warehouseId)
//     {
//         ini_set('max_execution_time', 300);

//         if (!$operationType || !$warehouseId) {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'Invalid input'
//             ]);
//         }

//         $warehouse = Warehouse::where('id', $warehouseId)
//             ->where('is_efris', 1)
//             ->where('status', 1)
//             ->first();

//         if (!$warehouse) {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'EFRIS depot not found'
//             ]);
//         }

//         $itemIds = Item::whereNotNull('sap_id')
//             ->pluck('id')
//             ->toArray();
//         $items = DailyStockCountDetail::with(['item', 'header'])
//             ->whereIn('item_id', $itemIds) // 🔥 cross DB safe
//             ->whereNotNull('qty')
//             ->where('qty', '>', 0)
//             ->whereHas('header', function ($q) use ($warehouseId) {
//                 $q->where('warehouse_id', $warehouseId)
//                     ->whereDate('date', today());
//             })
//             ->get();
//         $resp = [];

//         foreach ($items as $value) {


//             // ❌ Missing relation
//             if (empty($value->item)) {
//                 \Log::warning('Skipped: item relation missing');
//                 continue;
//             }

//             $payload = (object)[
//                 "pageNo" => "1",
//                 "pageSize" => "10",
//                 "goodsCode" => $value->item->sap_id,
//                 "branchId" => $warehouse->branch_id
//             ];

//             $api = $this->makePost("T127", $payload, $warehouse);

//             \Log::info('API response summary', [
//                 'returnCode' => $api['returnCode'] ?? null,
//                 'message' => $api['message'] ?? null,
//             ]);

//             $result = $api['inner_response'] ?? null;

//             if (!isset($result['records'][0])) {
//                 \Log::warning('Skipped: no records from API', [
//                     'item_id' => $value->item->id
//                 ]);
//                 continue;
//             }

//             $record = $result['records'][0];
//             $qty = $value->qty ?? 0;
//             $stock = $record['stock'];

//             $adjustment = $qty - $stock;

//             $type = 'NO_CHANGE';
//             if ($adjustment > 0) $type = 'INCREASE';
//             elseif ($adjustment < 0) $type = 'DECREASE';

//             if ($operationType == 101) {
//                 if (($stock - $qty) <= -0.5) {
//                     \Log::info('Condition PASSED (INCREASE)');

//                     $resp[] = [
//                         'code' => $record['goodsCode'],
//                         'name' => $record['goodsName'],
//                         'uom' => $record['measureUnit'],
//                         'unitprice' => $record['unitPrice'],
//                         'w_stock' => $qty,
//                         'efris_stock' => $stock,
//                         'variance' => round($qty - $stock, 0),
//                     ];
//                 } else {
//                     \Log::warning('Condition FAILED (INCREASE)');
//                 }
//             } else {
//                 if (($stock - $qty) >= 1) {
//                     \Log::info('Condition PASSED (DECREASE)');

//                     $resp[] = [
//                         'code' => $record['goodsCode'],
//                         'name' => $record['goodsName'],
//                         'uom' => $record['measureUnit'],
//                         'unitprice' => $record['unitPrice'],
//                         'w_stock' => $qty,
//                         'efris_stock' => $stock,
//                         'variance' => intval($stock - $qty),
//                     ];
//                 } else {
//                     \Log::warning('Condition FAILED (DECREASE)');
//                 }
//             }
//             \Log::info('--- ITEM END ---');
//         }

//         return response()->json([
//             'status' => true,
//             'operation_type' => $operationType,
//             'data' => $resp
//         ]);
//     }
// }


namespace App\Services\V1\EfrisAPI;

use App\Models\Warehouse;
use App\Models\Item;
use App\Models\Uom;
use App\Models\EfrisAPI\DailyStockCountDetail;

class UraStockAdjustmentService extends BaseEfrisService
{
    public function stockAdjustment($operationType, $warehouseId)
    {
        if (!$operationType || !$warehouseId) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid input'
            ]);
        }

        $warehouse = Warehouse::where([
            ['id', $warehouseId],
            ['is_efris', 1],
            ['status', 1]
        ])->first();

        if (!$warehouse) {
            return response()->json([
                'status' => false,
                'message' => 'EFRIS depot not found'
            ]);
        }

        $itemIds = Item::whereNotNull('sap_id')->pluck('id');

        $items = DailyStockCountDetail::with(['item', 'header'])
            ->whereIn('item_id', $itemIds)
            ->where('qty', '>', 0)
            ->whereHas(
                'header',
                fn($q) =>
                $q->where('warehouse_id', $warehouseId)
                    ->whereDate('date', today())
            )
            ->get();

        $resp = [];

        foreach ($items as $value) {

            if (!$value->item) continue;

            $api = $this->makePost("T127", (object)[
                "pageNo" => "1",
                "pageSize" => "10",
                "goodsCode" => $value->item->sap_id,
                "branchId" => $warehouse->branch_id
            ], $warehouse);

            $record = $api['inner_response']['records'][0] ?? null;
            if (!$record) continue;

            $qty = $value->qty;
            $stock = $record['stock'];
            $uomCode = $record['measureUnit'] ?? null;

            $uomName = Uom::where('uom_efriscode', $uomCode)
                ->value('name') ?? $uomCode;
            if ($operationType == 101 && ($stock - $qty) <= -0.5) {
                $resp[] = [
                    'code' => $record['goodsCode'],
                    'name' => $record['goodsName'],
                    'uom' => $uomName,
                    'unitprice' => $record['unitPrice'],
                    'w_stock' => $qty,
                    'efris_stock' => $stock,
                    'variance' => round($qty - $stock, 0),
                ];
            }

            if ($operationType != 101 && ($stock - $qty) >= 1) {
                $resp[] = [
                    'code' => $record['goodsCode'],
                    'name' => $record['goodsName'],
                    'uom' => $uomName,
                    'unitprice' => $record['unitPrice'],
                    'w_stock' => $qty,
                    'efris_stock' => $stock,
                    'variance' => intval($stock - $qty),
                ];
            }
        }

        return response()->json([
            'status' => true,
            'operation_type' => $operationType,
            'data' => $resp
        ]);
    }
}
