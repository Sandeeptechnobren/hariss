<?php

namespace App\Services\V1\EfrisAPI;

use App\Models\Warehouse;
use App\Models\Agent_Transaction\InvoiceHeader;
use App\Models\Agent_Transaction\ReturnHeader;
use App\Models\Agent_Transaction\ReturnDetail;
use App\Models\Item;

class UraReturnService extends BaseEfrisService
{
    public function getReturnsList($depotId, $date, $toDate)
    {
        $warehouse = Warehouse::find($depotId);

        if (!$warehouse) {
            return [
                'status' => false,
                'message' => 'Depot not found'
            ];
        }

        // 🔥 T111 (Return list)
        $payload = [
            "queryType" => "1",
            "startDate" => $date,
            "endDate" => $toDate,
            "pageNo" => "1",
            "pageSize" => "10"
        ];

        $resp = $this->makePost("T111", $payload, $warehouse);

        $records = $resp['inner_response']['records'] ?? [];

        $remain_data = [
            'vfc_code' => [],
            'qr_code' => []
        ];

        if (!empty($records)) {

            foreach ($records as $key => $value) {

                $invoiceNo = $value['invoiceNo'] ?? null;

                if (!$invoiceNo) {
                    $remain_data['vfc_code'][$key] = '';
                    $remain_data['qr_code'][$key] = '';
                    continue;
                }

                // 🔥 T108 (verification)
                $verifyPayload = [
                    "invoiceNo" => $invoiceNo
                ];

                $verifyResp = $this->makePost("T108", $verifyPayload, $warehouse);

                $verifyData = $verifyResp['inner_response'] ?? [];

                $remain_data['vfc_code'][$key] =
                    $verifyData['basicInformation']['antifakeCode'] ?? '';

                $remain_data['qr_code'][$key] =
                    $verifyData['summary']['qrCode'] ?? '';
            }
        }

        return [
            'status' => true,
            'returns' => $records,   // 🔥 renamed
            'extra' => $remain_data
        ];
    }

    // public function getReturnDetails($invoiceNo, $warehouseId)
    // {
    //     $warehouse = Warehouse::find($warehouseId);

    //     if (!$warehouse) {
    //         return [
    //             'status' => false,
    //             'message' => 'Depot not found'
    //         ];
    //     }

    //     $payload = [
    //         "invoiceNo" => $invoiceNo
    //     ];

    //     $resp = $this->makePost("T108", $payload, $warehouse);

    //     $data = $resp['inner_response'] ?? [];

    //     $itemupc = [];

    //     if (!empty($data['goodsDetails'])) {

    //         // 🔥 collect all itemCodes
    //         $itemCodes = collect($data['goodsDetails'])->pluck('itemCode')->filter();

    //         // 🔥 preload items with itemUoms (NO N+1)
    //         $items = Item::with(['itemUoms' => function ($q) {
    //             $q->where('status', 1)
    //                 ->where('uom_type', 'primary'); // optional but recommended
    //         }])
    //             ->whereIn('sap_id', $itemCodes)
    //             ->get()
    //             ->keyBy('sap_id');

    //         foreach ($data['goodsDetails'] as $value) {

    //             $itemCode = $value['itemCode'] ?? null;

    //             if (!$itemCode) {
    //                 continue;
    //             }

    //             $item = $items[$itemCode] ?? null;

    //             if ($item && $item->itemUoms->isNotEmpty()) {

    //                 $uom = $item->itemUoms->first();

    //                 $itemupc[$itemCode] = $uom->upc_num ?? $uom->upc;
    //             } else {
    //                 $itemupc[$itemCode] = null;
    //             }
    //         }
    //     }

    //     return [
    //         'status' => true,
    //         'data' => $data,
    //         'item_upc' => $itemupc
    //     ];
    // }

    public function getReturnDetails($invoiceNo, $warehouseId)
    {
        $warehouse = Warehouse::find($warehouseId);

        if (!$warehouse) {
            return [
                'status' => false,
                'message' => 'Depot not found'
            ];
        }

        $payload = [
            "invoiceNo" => $invoiceNo
        ];

        $resp = $this->makePost("T108", $payload, $warehouse);

        $data = $resp['inner_response'] ?? [];

        $itemsData = [];

        if (!empty($data['goodsDetails'])) {

            $itemCodes = collect($data['goodsDetails'])
                ->pluck('itemCode')
                ->filter();

            // 🔥 preload items with itemUoms + uom relation
            $items = Item::with([
                'itemUoms' => function ($q) {
                    $q->where('status', 1);
                },
                'itemUoms.uom' // 🔥 relation to uom table
            ])
                ->whereIn('sap_id', $itemCodes)
                ->get()
                ->keyBy('sap_id');

            foreach ($data['goodsDetails'] as $value) {

                $itemCode = $value['itemCode'] ?? null;

                if (!$itemCode) {
                    continue;
                }

                $item = $items[$itemCode] ?? null;

                if ($item && $item->itemUoms->isNotEmpty()) {

                    $uoms = [];

                    foreach ($item->itemUoms as $uomRow) {

                        $uoms[] = [
                            'uom_id' => $uomRow->uom_id,
                            'uom_name' => $uomRow->uom->name ?? null,
                            'sap_name' => $uomRow->uom->sap_name ?? null,
                            'upc' => $uomRow->upc_num ?? $uomRow->upc,
                            'price' => $uomRow->price
                        ];
                    }

                    $itemsData[] = [
                        'item_code' => $itemCode,
                        'item_id' => $item->id,
                        'uoms' => $uoms
                    ];
                } else {
                    $itemsData[] = [
                        'item_code' => $itemCode,
                        'item_id' => null,
                        'uoms' => []
                    ];
                }
            }
        }

        return [
            'status' => true,
            'data' => $data,
            'items' => $itemsData
        ];
    }


    public function syncReturn($data)
    {
        if (empty($data['item'])) {
            return [
                'status' => false,
                'message' => 'Items required'
            ];
        }



        $warehouse = Warehouse::find($data['warehouse_id']);

        if (!$warehouse) {
            return [
                'status' => false,
                'message' => 'Depot not found'
            ];
        }

        // 🔥 GOODS DETAILS
        $goodsdetails = [];

        foreach ($data['item'] as $key => $name) {

            $goodsdetails[] = [
                "item" => $name,
                "itemCode" => $data['code'][$key],
                "qty" => -$data['quantity'][$key],
                "unitOfMeasure" => $data['uom'][$key],
                "unitPrice" => $data['unitprice'][$key],
                "total" => -$data['total'][$key],
                "taxRate" => "0.18",
                "tax" => -$data['vat'][$key],
                "orderNumber" => (string)$key,
                "deemedFlag" => "2",
                "exciseFlag" => "2",
                "goodsCategoryId" => $data['categoryId'][$key]
            ];
        }

        $count = count($goodsdetails);

        $payload = [
            "oriInvoiceId" => $data['invoiceId'],
            "oriInvoiceNo" => $data['invoiceNo'],
            "reasonCode" => "102",
            "applicationTime" => now()->format('Y-m-d H:i:s'),
            "sellersReferenceNo" => "RETURN" . time(),
            "invoiceApplyCategoryCode" => "101",
            "currency" => "UGX",
            "source" => "103",
            "goodsDetails" => $goodsdetails,
            "taxDetails" => [[
                "netAmount" => -$data['total_net'],
                "taxRate" => "0.18",
                "taxAmount" => -$data['total_vat'],
                "grossAmount" => -$data['total_total'],
                "taxCategoryCode" => "01"
            ]],
            "summary" => [
                "netAmount" => -$data['total_net'],
                "taxAmount" => -$data['total_vat'],
                "grossAmount" => -$data['total_total'],
                "itemCount" => (string)$count,
                "modeCode" => "0",
                "qrCode" => ""
            ],
            "buyerDetails" => [
                "buyerTin" => $data['buyertin'],
                "buyerLegalName" => $data['buyername'],
                "buyerMobilePhone" => $data['buyerMobilePhone'],
                "buyerType" => $data['buyerType'],
                "buyerReferenceNo" => "00000000001"
            ],
            "basicInformation" => [
                "operator" => "system",
                "invoiceKind" => "1",
                "invoiceIndustryCode" => "102",
                "branchId" => (string)$warehouse->branch_id
            ]
        ];
        // dd($payload);
        // 🔥 CALL EFRIS
        $resp = $this->makePost("T110", $payload, $warehouse);

        if (($resp['returnCode'] ?? null) == "00") {

            DB::beginTransaction();

            try {

                // 🔥 GET INVOICE
                $invoice = InvoiceHeader::where('ura_invoice_no', $data['invoiceNo'])->first();

                // 🔥 INSERT RETURN HEADER
                $returnHeader = ReturnHeader::create([
                    'invoice_id' => $invoice->id ?? null,
                    'warehouse_id' => $warehouse->id,
                    'customer_id' => $invoice->customer_id ?? null,
                    'gross_total' => $data['total_total'],
                    'net_amount' => $data['total_net'],
                    'vat' => $data['total_vat'],
                    'total' => $data['total_total'],
                    'status' => 1
                ]);

                // 🔥 INSERT DETAILS
                foreach ($data['item'] as $key => $name) {

                    $item = Item::where('sap_id', $data['code'][$key])->first();

                    if (!$item) continue;

                    ReturnDetail::create([
                        'header_id' => $returnHeader->id,
                        'item_id' => $item->id,
                        'uom_id' => $this->getUomId($data['uom'][$key]),
                        'item_price' => $data['unitprice'][$key],
                        'item_quantity' => $data['quantity'][$key],
                        'gross_total' => $data['total'][$key],
                        'net_total' => $data['netamount'][$key],
                        'vat' => $data['vat'][$key],
                        'total' => $data['total'][$key],
                        'status' => 1
                    ]);
                }

                DB::commit();

                return [
                    'status' => true,
                    'message' => 'Return created successfully',
                    'response' => $resp
                ];
            } catch (\Exception $e) {

                DB::rollBack();

                return [
                    'status' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        return [
            'status' => false,
            'message' => $resp['message'] ?? 'EFRIS Error'
        ];
    }
}
