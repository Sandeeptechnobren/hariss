<?php

namespace App\Http\Controllers\V1\EfrisAPI;

use App\Http\Controllers\Controller;
use App\Services\V1\EfrisAPI\BaseEfrisService;
use App\Services\V1\EfrisAPI\UraStockAdjustmentService;
use Illuminate\Http\Request;
use App\Models\Warehouse;

class UraStockAdjustmentController extends Controller
{
    protected $service;

    public function __construct(UraStockAdjustmentService $service)
    {
        $this->service = $service;
    }

    // public function stockAdjustment(Request $request)
    // {
    //     return $this->service->stockAdjustment(
    //         $request->operation_type,
    //         $request->warehouse_id
    //     );
    // }


    public function fetch(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required',
            'operation_type' => 'required|in:101,102'
        ]);

        $service = app(UraStockAdjustmentService::class);

        $response = $service->stockAdjustment(
            $request->operation_type,
            $request->warehouse_id
        );

        return $response;
    }


    public function upload(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required',
            'operation_type' => 'required',
            'items' => 'required|array'
        ]);

        $warehouse = Warehouse::find($request->warehouse_id);

        $goodsStockInItem = [];

        foreach ($request->items as $item) {

            if ($item['variance'] <= 0) continue;

            $goodsStockInItem[] = [
                "commodityGoodsId" => "",
                "goodsCode" => $item['code'],
                "measureUnit" => $item['uom'],
                "quantity" => $item['variance'], // 🔥 important
                "unitPrice" => $item['unitprice'],
                "remarks" => "",
                "fuelTankId" => "",
                "lossQuantity" => "0",
                "originalQuantity" => "0"
            ];
        }

        if (empty($goodsStockInItem)) {
            return response()->json([
                'status' => false,
                'message' => 'No valid items to upload'
            ]);
        }

        $payload = [
            "goodsStockIn" => [
                "operationType" => $request->operation_type,
                "supplierTin" => $request->operation_type == 101 ? $warehouse->tin_no : "",
                "supplierName" => $request->operation_type == 101 ? $warehouse->warehouse_name : "",
                "adjustType" => $request->operation_type == 102 ? "101" : "",
                "remarks" => "",
                "stockInDate" => now()->format('Y-m-d'),
                "stockInType" => $request->operation_type == 101 ? 102 : "",
                "branchId" => $warehouse->branch_id,
                "invoiceNo" => "",
                "isCheckBatchNo" => "0",
                "rollBackIfError" => "0",
                "goodsTypeCode" => "101"
            ],
            "goodsStockInItem" => $goodsStockInItem
        ];

        $efris = app(BaseEfrisService::class);
        $result = $efris->callApi("T131", (object)$payload, $warehouse);

        return response()->json([
            'status' => true,
            'payload' => $payload,
            'efris_response' => $result
        ]);
    }


    public function listEfrisStock(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required',
            'page_no' => 'nullable'
        ]);

        $warehouse = Warehouse::find($request->warehouse_id);

        if (!$warehouse) {
            return response()->json([
                'status' => false,
                'message' => 'Warehouse not found'
            ]);
        }
        $branchIds = $warehouse->branch_id ?? '';

        $payload = (object)[
            "pageNo" => $request->page_no ?? 1,
            "pageSize" => "10",
            "branchId" => $branchIds
        ];
        // dd($payload);
        $efris = app(BaseEfrisService::class);
        $result = $efris->callApi("T127", $payload, $warehouse);

        $records = $result['inner_response']['records'] ?? [];

        if (empty($records)) {
            return response()->json([
                'status' => true,
                'data' => []
            ]);
        }
        $items = \DB::table('items')
            ->pluck('id', 'sap_id');

        $stocks = \DB::table('tbl_warehouse_stocks')
            ->where('warehouse_id', $request->warehouse_id)
            ->pluck('qty', 'item_id');

        $uoms = \DB::table('item_uoms')
            ->where('status', 1)
            ->where('uom_type', 'secondary')
            ->pluck('upc', 'item_id');

        $final = [];

        foreach ($records as $value) {

            $itemId = $items[$value['goodsCode']] ?? null;

            $buomQty = ($itemId && isset($stocks[$itemId]))
                ? $stocks[$itemId]
                : 0;

            $upc = ($itemId && isset($uoms[$itemId]))
                ? $uoms[$itemId]
                : 1;

            $qty = ($upc > 0) ? $buomQty / $upc : $buomQty;

            $altuom = ($value['measureUnit'] == 110)
                ? 'BOX'
                : $value['measureUnit'];

            $final[] = [
                'sync_date' => $value['updateDateStr'] ?? null,
                'goodsCode' => $value['goodsCode'] ?? null,
                'goodsName' => $value['goodsName'] ?? null,
                'base_uom' => $value['pieceMeasureUnit'] ?? null,
                'piece_unit_price' => isset($value['pieceUnitPrice']) ? (float)$value['pieceUnitPrice'] : 0,
                'upc' => $value['pieceScaledValue'] ?? null,
                'alt_uom' => $altuom,
                'unit_price' => isset($value['unitPrice']) ? (float)$value['unitPrice'] : 0,
                'category' => $value['commodityCategoryName'] ?? null,
                'efris_stock' => isset($value['stock']) ? round($value['stock'], 3) : 0,
                'qty' => round($qty, 3)
            ];
        }

        return response()->json([
            'status' => true,
            'total' => count($final),
            'data' => $final
        ]);
    }
}
