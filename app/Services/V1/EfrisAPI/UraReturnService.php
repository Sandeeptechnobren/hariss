<?php

namespace App\Services\V1\EfrisAPI;

use App\Models\Warehouse;
use App\Models\Agent_Transaction\InvoiceHeader;
use App\Models\Agent_Transaction\ReturnHeader;
use App\Models\Agent_Transaction\ReturnDetail;
use App\Models\Item;
use App\Models\Uom;
use Illuminate\Support\Facades\DB;

class UraReturnService extends BaseEfrisService
{
    public function getReturnsList($warehouseId, $date, $toDate)
    {
        $warehouse = Warehouse::find($warehouseId);

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
        // dd($warehouse);
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

    private function clean($value)
    {
        return trim(preg_replace('/[^0-9A-Za-z]/', '', $value));
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
            $qty   = (float) ($data['quantity'][$key] ?? 0);
            $price = (float) ($data['unitprice'][$key] ?? 0);
            $total = (float) ($data['total'][$key] ?? 0);
            $vat   = (float) ($data['vat'][$key] ?? 0);
            $goodsdetails[] = [
                "item" => $name,
                "itemCode" => $data['code'][$key] ?? '',
                "qty" => -$qty,
                "unitOfMeasure" => $data['uom'][$key] ?? '',
                "unitPrice" => $price,
                "total" => -$total,
                "taxRate" => "0.18",
                "tax" => -$vat,
                "orderNumber" => (string)$key,
                "deemedFlag" => "2",
                "exciseFlag" => "2",
                "goodsCategoryId" => $data['categoryId'][$key] ?? ''
            ];
        }
        // dd($data['invoiceId']);
        $count = count($goodsdetails);

        $payload = [
            "oriInvoiceId" => $this->clean($data['invoiceId']),
            "oriInvoiceNo" => $this->clean($data['invoiceNo']),
            "reasonCode" => "102",
            "applicationTime" => now()->format('Y-m-d H:i:s'),
            "sellersReferenceNo" => "RETURN" . time(),
            "invoiceApplyCategoryCode" => "101",
            "currency" => "UGX",
            "source" => "103",
            "goodsDetails" => $goodsdetails,
            "taxDetails" => [[
                "taxRate" => "0.18",
                "netAmount" => -(float) ($data['total_net'] ?? 0),
                "taxAmount" => -(float) ($data['total_vat'] ?? 0),
                "grossAmount" => -(float) ($data['total_total'] ?? 0),
                "taxCategoryCode" => "01"
            ]],
            "summary" => [
                "netAmount" => -(float) ($data['total_net'] ?? 0),
                "taxAmount" => -(float) ($data['total_vat'] ?? 0),
                "grossAmount" => -(float) ($data['total_total'] ?? 0),
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
        $innerResponse = $resp['inner_response'] ?? [];

        // 🔥 NEW: handle response (for debug/log)
        $this->handleResponse(
            $data['header'] ?? null,
            $warehouse,
            $payload,
            $resp,
            $innerResponse
        );
        if (($resp['returnCode'] ?? null) == "00") {

            DB::beginTransaction();

            try {

                // 🔥 GET INVOICE
                $invoice = InvoiceHeader::where('ura_invoice_no', $data['invoiceNo'])->first();
                $returnCode = $this->generateReturnCode();
                $returnHeader = ReturnHeader::create([
                    'osa_code' => $returnCode,
                    'invoice_id' => $invoice->id ?? null,
                    'warehouse_id' => $warehouse->id,
                    'customer_id' => $invoice->customer_id ?? null,
                    'route_id' => $invoice->route_id ?? null,
                    'salesman_id' => $invoice->salesman_id ?? null,
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
                        'item_price' => (float) ($data['unitprice'][$key] ?? 0),
                        'item_quantity' => (float) ($data['quantity'][$key] ?? 0),
                        'gross_total' => (float) ($data['total'][$key] ?? 0),
                        'net_total' => (float) ($data['netamount'][$key] ?? 0),
                        'vat' => (float) ($data['vat'][$key] ?? 0),
                        'total' => (float) ($data['total'][$key] ?? 0),
                        'status' => 1
                    ]);
                }

                DB::commit();

                return [
                    'status' => true,
                    'message' => 'Return created successfully',
                    'response' => $resp,
                    'inner_response' => $innerResponse
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
            'message' => $resp['message'] ?? 'EFRIS Error',
            'inner_response' => $innerResponse
        ];
    }

    private function generateReturnCode()
    {
        $last = ReturnHeader::whereNotNull('osa_code')
            ->lockForUpdate() // 🔥 IMPORTANT
            ->orderBy('id', 'desc')
            ->first();

        if (!$last || !$last->osa_code) {
            return 'RETNHD-001';
        }

        preg_match('/RETNHD-(\d+)/', $last->osa_code, $matches);

        $number = isset($matches[1]) ? (int)$matches[1] : 0;

        return 'RETNHD-' . str_pad($number + 1, 3, '0', STR_PAD_LEFT);
    }

    private function getUomId($uomName)
    {
        if (!$uomName) {
            return null;
        }

        return Uom::where(function ($q) use ($uomName) {
            $q->where('name', $uomName)
                ->orWhere('sap_name', $uomName);
        })
            ->value('id');
    }

    private function handleResponse($header, $warehouse, $payload, $response, $innerResponse)
    {
        \Log::info('EFRIS RETURN DEBUG', [
            'header' => $header,
            'warehouse_id' => $warehouse->id ?? null,
            'payload' => $payload,
            'response' => $response,
            'inner_response' => $innerResponse
        ]);
    }
}
