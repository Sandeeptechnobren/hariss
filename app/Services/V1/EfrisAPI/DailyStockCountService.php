<?php

// namespace App\Services\V1\EfrisAPI;

// use Carbon\Carbon;
// use Illuminate\Support\Facades\DB;
// use App\Models\EfrisAPI\DailyStockCountHeader;
// use App\Models\EfrisAPI\DailyStockCountDetail;
// use App\Models\Warehouse;
// use App\Models\CompanyCustomer;
// use App\Models\WarehouseStock;
// use App\Models\ItemUOM;

// class DailyStockCountService
// {
//     public function insertData()
//     {
//         $targetDate = Carbon::today()->toDateString();

//         // ✅ Check exists
//         $exists = DailyStockCountHeader::whereDate('date', $targetDate)->exists();
//         // dd($exists);
//         if ($exists) {
//             return "Data for {$targetDate} already exists.";
//         }

//         return DB::transaction(function () use ($targetDate) {

//             $warehouses = Warehouse::where('status', 1)
//                 ->get();
//             // $warehouse = Warehouse::where('id', 86)
//             //     ->first();
//             foreach ($warehouses as $warehouse) {

//                 $header = DailyStockCountHeader::create([
//                     'warehouse_id' => $warehouse->id,
//                     'customer_id' => $warehouse->agent_customer ?? null,
//                     'date' => $targetDate,
//                     'total_good_stock_amount' => 0,
//                     'total_bad_stock_amount' => 0,
//                 ]);
//                 // dd($header);
//                 $totalGoodAmount = 0;

//                 // ✅ Get stock data
//                 $stocks = WarehouseStock::where('warehouse_id', $warehouse->id)
//                     ->where('status', 1)
//                     ->get()
//                     ->unique('item_id');

//                 foreach ($stocks as $stock) {

//                     $qty = $stock->qty ?? 0;
//                     $uom = ItemUOM::where('item_id', $stock->item_id)
//                         ->where('is_stock_keeping', true)
//                         ->where('status', 1)
//                         ->first();

//                     if (!$uom) {
//                         continue;
//                     }

//                     $upc = $uom->upc_num ?? 1;
//                     $price = $uom->price ?? 0;

//                     $altQty = $upc > 0 ? ($qty / $upc) : 0;

//                     DailyStockCountDetail::create([
//                         'header_id' => $header->id,
//                         'item_id' => $stock->item_id,
//                         'base_uom_qty' => $qty,
//                         'alt_uom_qty' => $altQty,
//                     ]);

//                     $totalGoodAmount += ($altQty * $price);
//                 }

//                 $header->update([
//                     'total_good_stock_amount' => round($totalGoodAmount, 2),
//                 ]);
//             }

//             return "Stock count created successfully.";
//         });
//     }
// }
namespace App\Services\V1\EfrisAPI;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\EfrisAPI\DailyStockCountHeader;
use App\Models\EfrisAPI\DailyStockCountDetail;
use App\Models\Warehouse;
use App\Models\WarehouseStock;

class DailyStockCountService
{
    public function insertData()
    {
        $targetDate = Carbon::today()->toDateString();

        $exists = DailyStockCountHeader::whereDate('date', $targetDate)->exists();
        if ($exists) {
            throw new \Exception("Data for {$targetDate} already exists.");
        }

        return DB::transaction(function () use ($targetDate) {

            $warehouses = Warehouse::where('status', 1)->where('warehouse_type', 1)->get();

            if ($warehouses->isEmpty()) {
                throw new \Exception("No warehouses found.");
            }

            $successIds = [];
            $failedIds = [];

            foreach ($warehouses as $warehouse) {

                try {

                    if (!$warehouse->id) {
                        throw new \Exception("Invalid warehouse data.");
                    }

                    $header = DailyStockCountHeader::create([
                        'warehouse_id' => $warehouse->id,
                        'customer_id' => $warehouse->agent_customer ?? null,
                        'date' => $targetDate,
                    ]);

                    if (!$header) {
                        throw new \Exception("Header creation failed.");
                    }

                    $stocks = WarehouseStock::where('warehouse_id', $warehouse->id)
                        ->where('status', 1)
                        ->get()
                        ->unique('item_id');

                    foreach ($stocks as $stock) {

                        if (!$stock->item_id) {
                            throw new \Exception("Invalid stock item.");
                        }

                        $qty = $stock->qty ?? 0;

                        $detail = DailyStockCountDetail::create([
                            'header_id' => $header->id,
                            'item_id' => $stock->item_id,
                            'qty' => $qty
                        ]);

                        if (!$detail) {
                            throw new \Exception("Detail insert failed for item_id: {$stock->item_id}");
                        }
                    }

                    $successIds[] = $warehouse->id;
                } catch (\Exception $e) {

                    $failedIds[] = [
                        'warehouse_id' => $warehouse->id,
                        'error' => $e->getMessage()
                    ];

                    throw $e;
                }
            }

            Log::info('Stock Count Cron Summary', [
                'date' => $targetDate,
                'total_warehouses' => count($warehouses),
                'success_count' => count($successIds),
                'failed_count' => count($failedIds),
                'success_ids' => $successIds,
                'failed_ids' => $failedIds,
            ]);

            return "Stock count created successfully.";
        });
    }
}
