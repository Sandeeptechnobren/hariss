<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\Item;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SapItemService
{
public function importFromSAP()
{
    // ----------------------------
    // 1️⃣ Fetch data from SAP using Laravel HTTP
    // ----------------------------
$response = Http::withOptions([
        'verify' => false,
    ])
    ->withHeaders([
        'X-REQUESTED-WITH' => 'X',
        'Accept'           => 'application/json',
        'Content-Type'     => 'application/json',
        'Authorization'    => 'Basic T1NBX0FkbWluOlM0aGFuYVJpc2VAMTIzNDU=',
    ])
    ->timeout(30)
    ->get('https://vhhzrphici.sap.harissint.com:44300/sap/opu/odata/sap/ZOSA_DOWNLOAD_SRV/MaterialHeaderSet?%24format=json&sap-client=900');

$data = $response->object();
$all_request_data = $data->d ?? [];
dd($data);
/**
 * Same as:
 * $all_request_data = isset($data->d) ? $data->d : array();
 */
$all_request_data = isset($data->d) ? $data->d : [];
dd($all_request_data);

    try {
        $response = Http::withHeaders([
            'X-REQUESTED-WITH' => 'X',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => env('SAP_TOKEN'), // Your SAP token from .env
        ])
        ->timeout(60) // 60 seconds timeout for large data
        ->get('https://vhhzrphici.sap.harissint.com:44300/sap/opu/odata/sap/ZOSA_DOWNLOAD_SRV/MaterialHeaderSet?%24format=json&sap-client=900');

        // Check if the request failed
        if ($response->failed()) {
            return [
                'status' => false,
                'message' => 'SAP HTTP error: ' . $response->status()
            ];
        }

        // Decode JSON response
        $data = $response->json();

        if (!isset($data['d']['results'])) {
            return [
                'status' => false,
                'message' => 'Invalid SAP response or no results found'
            ];
        }

        $records = $data['d']['results'];

    } catch (\Exception $e) {
        return [
            'status' => false,
            'message' => 'SAP HTTP exception: ' . $e->getMessage()
        ];
    }

    $batchSize = 500;
    $now = now();
    
    // ----------------------------
    // 2️⃣ Fetch reference data from DB
    // ----------------------------
    $categories = DB::table('item_categories')
        ->select('category_code', 'id', 'category_name')
        ->get()
        ->keyBy(fn($c) => trim($c->category_code));

    $subcategories = DB::table('item_sub_categories')
        ->select('sub_category_code', 'id', 'sub_category_name', 'category_id')
        ->get()
        ->keyBy(fn($s) => trim($s->sub_category_code));

    $uoms = DB::table('uom')
        ->select('id', 'sap_name', 'uom')
        ->get()
        ->keyBy(fn($u) => trim($u->sap_name));

    $uomMapping = [
        'PC'  => 'PS',
        'PCS' => 'PS',
        'PS'  => 'PS',
        'CS'  => 'CS',
        'BOX' => 'BOX',
        'OUT' => 'OUT',
        'CAR' => 'CAR',
        'PAK' => 'PAK',
        'PAC' => 'PAC',
    ];

    // ----------------------------
    // 3️⃣ Process records in batches
    // ----------------------------
    $chunks = array_chunk($records, $batchSize);

    foreach ($chunks as $chunk) {
        $payload = [];
        $uomPayload = [];

        foreach ($chunk as $item) {
            if (empty($item['ItemCode'])) continue;

            // Alternate UOM mapping
            $alternateUOM = match (trim($item['AlternateUOM'] ?? '')) {
                "PS" => 1,
                "CS" => 2,
                "OUT" => 3,
                "CAR", "PAK", "PAC" => 4,
                default => null,
            };

            // Category/subcategory
            $category = $categories[trim($item['ItemCategory'] ?? '')] ?? null;
            $subcategory = $subcategories[trim($item['ItemGroup'] ?? '')] ?? null;

            // Base UOM mapping
            $baseUOM = trim($item['BaseUOM'] ?? '');
            $mappedUOM = $uomMapping[$baseUOM] ?? $baseUOM;
            $uom = $uoms[$mappedUOM] ?? null;

            if (!$uom) {
                Log::warning("UOM '{$baseUOM}' (mapped: '{$mappedUOM}') not found in DB");
            }

            $vat = ($item['Vat'] == '1' || $item['Vat'] == '') ? 1 : 0;
            $excise = ($item['Excise'] == '1') ? 1 : 0;
            $status = ($item['Status'] == '') ? 1 : 0;

            $baseUOMPrice = (float) trim($item['BaseUOMPrice'] ?? 0);
            $upc = (float) trim($item['UPC'] ?? 1);
            $baseAlternateUOMPrice = $upc > 0 ? round($baseUOMPrice / $upc, 0) : 0;

            $payload[] = [
                'uuid' => Str::uuid(),
                'erp_code' => trim($item['ItemCode']),
                'code' => trim($item['ItemOsaCode'] ?? ''),
                'name' => trim($item['ItemName'] ?? ''),
                'description' => trim($item['Description'] ?? ''),
                'image' => trim($item['Image'] ?? ''),
                'brand' => trim($item['Brand'] ?? null),
                'category_id' => $category->id ?? null,
                'sub_category_id' => $subcategory->id ?? null,
                'item_weight' => (float) trim($item['ItemWeight'] ?? 0),
                'shelf_life' => (int) trim($item['ShelfLife'] ?? 0),
                'volume' => (float) trim($item['Volume'] ?? 0),
                'is_promotional' => $item['IsPromotional'] ?? 0,
                'is_taxable' => $item['IsTaxable'] ?? 1,
                'has_excies' => $item['HasExcise'] ?? 0,
                'commodity_goods_code' => trim($item['CommodityGoodsCode'] ?? ''),
                'excise_duty_code' => trim($item['ExciseDutyCode'] ?? ''),
                'status' => $status,
                'created_user' => 1,
                'updated_user' => 1,
                'base_uom' => $baseUOM,
                'alternate_uom' => $alternateUOM,
                'customer_code' => trim($item['CustomerCode'] ?? ''),
                'upc' => $upc,
                'base_uom_vol' => (float) trim($item['BaseUOMVol'] ?? 0),
                'alter_base_uom_vol' => (float) trim($item['AlterBaseUOMVol'] ?? 0),
                'distribution_code' => trim($item['DistributionCode'] ?? ''),
                'barcode' => trim($item['Barcode'] ?? ''),
                'net_weight' => (float) trim($item['NetWeight'] ?? 0),
                'base_uom_price' => $baseUOMPrice,
                'base_alternate_uom_price' => $baseAlternateUOMPrice,
                'tax' => trim($item['Tax'] ?? ''),
                'vat' => $vat,
                'excise' => $excise,
                'uom_efris_code' => trim($item['UOM_EFRISCode'] ?? ''),
                'altuom_efris_code' => trim($item['ALTUOM_EFRISCode'] ?? ''),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $uomPayload[] = [
                'erp_code' => trim($item['ItemCode']),
                'name' => $baseUOM,
                'uom_id' => $uom->id ?? null,
                'price' => $baseUOMPrice,
                'upc' => $upc,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // ----------------------------
        // 4️⃣ Bulk upsert items and item_uoms
        // ----------------------------
        $beforeCount = DB::table('items')->count();

        DB::transaction(function () use ($payload, $uomPayload, &$insertedUOM, &$updatedUOM) {
            DB::table('items')->upsert(
                $payload,
                ['erp_code'],
                [
                    'code', 'name', 'description', 'image', 'brand', 'category_id', 'sub_category_id',
                    'item_weight', 'shelf_life', 'volume', 'is_promotional', 'is_taxable', 'has_excies',
                    'commodity_goods_code', 'excise_duty_code', 'status', 'updated_user', 'base_uom',
                    'alternate_uom', 'customer_code', 'upc', 'base_uom_vol', 'alter_base_uom_vol',
                    'distribution_code', 'barcode', 'net_weight', 'base_uom_price',
                    'base_alternate_uom_price', 'tax', 'vat', 'excise', 'uom_efris_code', 'altuom_efris_code',
                    'updated_at'
                ]
            );

            $finalUOMPayload = [];
            $itemIds = DB::table('items')
                ->whereIn('erp_code', array_column($uomPayload, 'erp_code'))
                ->pluck('id', 'erp_code');

            foreach ($uomPayload as $row) {
                $itemId = $itemIds[$row['erp_code']] ?? null;
                if (!$itemId || !$row['uom_id']) continue;
                $row['item_id'] = $itemId;
                unset($row['erp_code']);
                $finalUOMPayload[] = $row;
            }

            $insertedUOM = 0;
            $updatedUOM = 0;

            $existing = DB::table('item_uoms')
                ->whereIn('item_id', array_column($finalUOMPayload, 'item_id'))
                ->get(['id', 'item_id', 'uom_id']);

            $toUpdate = [];
            $toInsert = [];

            foreach ($finalUOMPayload as $row) {
                $exists = $existing->first(fn($e) => $e->item_id == $row['item_id'] && $e->uom_id == $row['uom_id']);
                if ($exists) {
                    $row['id'] = $exists->id;
                    $toUpdate[] = $row;
                    $updatedUOM++;
                } else {
                    $toInsert[] = $row;
                    $insertedUOM++;
                }
            }

            DB::table('item_uoms')->insert($toInsert);
        });
    }

    $afterCount = DB::table('items')->count();
    $inserted = $afterCount - $beforeCount;
    $updated = count($payload) - $inserted;

    return [
        'status' => true,
        'message' => 'SAP Material Import completed successfully using Laravel HTTP client.',
        'inserted' => $inserted,
        'updated' => $updated,
        'item_uoms_inserted' => $insertedUOM,
        'item_uoms_updated' => $updatedUOM,
    ];
}

}