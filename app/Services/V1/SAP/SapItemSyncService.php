<?php

namespace App\Services\V1\SAP;

use App\Models\Item;
use App\Models\Uom;
use App\Models\ItemUOM;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SapItemSyncService
{
    protected string $url;

    // public function __construct()
    // {
    //     $this->url = config('services.sap.material_header_url');
    // }

    public function sync(): array
    {
        DB::beginTransaction();

        try {

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => config('services.sap.token'),
            ])
                ->withoutVerifying()
                ->timeout(60)
                ->get($this->url);

            if (!$response->successful()) {
                throw new \Exception('SAP API Failed');
            }

            $results = $response->json()['d']['results'] ?? [];

            $insertItem = 0;
            $updateItem = 0;

            foreach ($results as $data) {

                $baseUomName = trim($data['BaseUOM'] ?? '');
                $altUomName  = trim($data['AlternateUOM'] ?? '');

                if (!$baseUomName) continue;

                // 1️⃣ UOM Sync (Base)
                $baseUom = Uom::firstOrCreate(
                    ['sap_name' => $baseUomName],
                    [
                        'uuid' => Str::uuid(),
                        'name' => $baseUomName,
                        'osa_code' => $data['UOM_EFRISCode'] ?? null,
                        'created_user' => auth()->id()
                    ]
                );

                // 2️⃣ UOM Sync (Alternate)
                $altUom = null;
                if ($altUomName) {
                    $altUom = Uom::firstOrCreate(
                        ['sap_name' => $altUomName],
                        [
                            'uuid' => Str::uuid(),
                            'name' => $altUomName,
                            'osa_code' => $data['ALTUOM_EFRISCode'] ?? null,
                            'created_user' => auth()->id()
                        ]
                    );
                }

                // 3️⃣ Item Upsert
                $item = Item::updateOrCreate(
                    ['erp_code' => $data['ItemCode']],
                    [
                        'uuid' => Str::uuid(),
                        'code' => $data['ItemCode'],
                        'name' => $data['ItemName'] ?? null,
                        'description' => $data['Description'] ?? null,
                        'barcode' => $data['Barcode'] ?? null,
                        'shelf_life' => $data['ShelfLife'] ?? null,
                        'item_weight' => $data['NetWeight'] ?? null,
                        'is_taxable' => ($data['Vat'] ?? '') == '1',
                        'has_excies' => ($data['Excise'] ?? '') == '1',
                        'status' => 1,
                        'created_user' => auth()->id(),
                        'updated_user' => auth()->id(),
                    ]
                );

                if ($item->wasRecentlyCreated) {
                    $insertItem++;
                } else {
                    $updateItem++;
                }

                // 4️⃣ Item UOM (Base)
                ItemUOM::updateOrCreate(
                    [
                        'item_id' => $item->id,
                        'uom_id' => $baseUom->id
                    ],
                    [
                        'name' => $baseUomName,
                        'uom_type' => 'base',
                        'upc_num' => $data['UPC'] ?? 1,
                        'price' => $data['BaseUOMPrice'] ?? 0,
                        'is_stock_keeping' => true,
                        'enable_for' => 'sales,return',
                        'status' => 1
                    ]
                );

                // 5️⃣ Item UOM (Alternate)
                if ($altUom) {
                    $basePrice = (float)($data['BaseUOMPrice'] ?? 0);
                    $upc = (float)($data['UPC'] ?? 1);

                    $altPrice = $upc > 0 ? round($basePrice / $upc, 2) : 0;

                    ItemUOM::updateOrCreate(
                        [
                            'item_id' => $item->id,
                            'uom_id' => $altUom->id
                        ],
                        [
                            'name' => $altUomName,
                            'uom_type' => 'alternate',
                            'upc_num' => $upc,
                            'price' => $altPrice,
                            'is_stock_keeping' => false,
                            'enable_for' => 'sales,return',
                            'status' => 1
                        ]
                    );
                }
            }

            DB::commit();

            return [
                'status' => true,
                'inserted_items' => $insertItem,
                'updated_items' => $updateItem
            ];
        } catch (\Exception $e) {

            DB::rollBack();

            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function syncFromStdClass($payload)
    {
        DB::beginTransaction();

        try {

            $insertItem = 0;
            $updateItem = 0;

            // Direct indexing loop
            for ($i = 0; $i < count($payload->results); $i++) {

                $data = $payload->results[$i];

                /* =========================
                   1️⃣ UOM (Base)
                ========================== */

                $baseUom = Uom::firstOrCreate(
                    ['sap_name' => trim($data->BaseUOM)],
                    [
                        'uuid' => Str::uuid(),
                        'name' => trim($data->BaseUOM),
                        'UOM_EFRISCode' => $data->UOM_EFRISCode ?? null,
                        'created_user' => 1
                    ]
                );

                /* =========================
                   2️⃣ UOM (Alternate)
                ========================== */

                $altUom = null;

                if (!empty(trim($data->AlternateUOM))) {

                    $altUom = Uom::firstOrCreate(
                        ['sap_name' => trim($data->AlternateUOM)],
                        [
                            'uuid' => Str::uuid(),
                            'name' => trim($data->AlternateUOM),
                            'ALTUOM_EFRISCode' => $data->ALTUOM_EFRISCode ?? null,
                            'created_user' => 1
                        ]
                    );
                }

                /* =========================
                   3️⃣ Item Insert / Update
                ========================== */

                $item = Item::updateOrCreate(
                    ['erp_code' => $data->ItemCode],
                    [
                        'uuid' => Str::uuid(),
                        'code' => $data->ItemOsaCode,
                        'name' => $data->ItemName,
                        'description' => $data->Description,
                        'customer_code' => $data->CustomerCode,
                        'barcode' => $data->Barcode,
                        'image' => $data->Image,
                        'shelf_life' => $data->ShelfLife,
                        'item_weight' => (float)$data->NetWeight,
                        'volume' => (float)$data->BaseUOMVol,
                        'base_uom_vol' => (float)$data->BaseUOMVol,
                        'alter_base_uom_vol' => (float)$data->AlterBaseUOMVol,
                        'item_category' => $data->ItemCategory,
                        'distribution_code' => $data->DistributionCode,
                        'commodity_goods_code' => $data->CommodityGoodsCode,
                        'excise_duty_code' => $data->ExciseDutyCode,
                        'item_group' => $data->ItemGroup,
                        'item_group_desc' => $data->ItemGroupDesc,
                        'vat' => $data->Vat,
                        'excise' => $data->Excise,
                        'is_taxable' => ($data->Vat == 1),
                        'has_excies' => ($data->Excise == 1),
                        'status' => empty($data->Status) ? 1 : 0,
                        'created_user' => 1,
                        'updated_user' => 1
                    ]
                );

                if ($item->wasRecentlyCreated) {
                    $insertItem++;
                } else {
                    $updateItem++;
                }

                /* =========================
                   4️⃣ Item UOM (Base)
                ========================== */

                $basePrice = (float)$data->BaseUOMPrice;
                $upc = (float)trim($data->UPC);

                ItemUOM::updateOrCreate(
                    [
                        'item_id' => $item->id,
                        'uom_id' => $baseUom->id
                    ],
                    [
                        'name' => $data->BaseUOM,
                        'uom_type' => 'base',
                        'upc' => $data->UPC,
                        // 'upc_num' => $upc,
                        'price' => $basePrice,
                        'is_stock_keeping' => true,
                        'status' => 1
                    ]
                );

                /* =========================
                   5️⃣ Item UOM (Alternate)
                ========================== */

                if ($altUom) {

                    $altPrice = ($upc > 0)
                        ? round($basePrice / $upc, 2)
                        : 0;

                    ItemUOM::updateOrCreate(
                        [
                            'item_id' => $item->id,
                            'uom_id' => $altUom->id
                        ],
                        [
                            'name' => $data->AlternateUOM,
                            'uom_type' => 'alternate',
                            'upc' => $data->UPC,
                            'upc_num' => $upc,
                            'price' => $altPrice,
                            'is_stock_keeping' => false,
                            'status' => 1
                        ]
                    );
                }
            }

            DB::commit();

            return [
                'status' => true,
                'inserted_items' => $insertItem,
                'updated_items' => $updateItem
            ];
        } catch (\Exception $e) {

            DB::rollBack();

            return [
                'status' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
