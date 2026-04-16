<?php

namespace App\Services\V1\EfrisAPI;

use App\Models\Agent_Transaction\InvoiceHeader;
use App\Models\Agent_Transaction\InvoiceDetail;
use App\Models\Item;
use App\Models\Warehouse;
use App\Models\Salesman;
use App\Models\AgentCustomer;
use App\Models\Route;
use App\Models\EfrisAPI\EfrisInvoiceLog;
use App\Models\EfrisAPI\EfrisInvoiceFlag;
use App\Helpers\DataAccessHelper;
use App\Helpers\CommonLocationFilter;

class UraInvoiceService extends BaseEfrisService
{
    public function getInvoices(array $filters = [])
    {
        try {
            $user = auth()->user();
            $filter = $filters['filter'] ?? [];

            $query = InvoiceHeader::with([
                'warehouse:id,warehouse_code,warehouse_name',
                'customer:id,name,osa_code',
                'details.item:id,name',
                'details.itemuom:id,name,price'
            ])
                ->whereNull('ura_invoice_id')
                ->where('status', 1)
                ->latest();

            $query = DataAccessHelper::filterAgentTransaction($query, $user);

            if (!empty($filter)) {
                $warehouseIds = CommonLocationFilter::resolveWarehouseIds([
                    'company_id'   => $filter['company_id']   ?? null,
                    'region_id'    => $filter['region_id']    ?? null,
                    'area_id'      => $filter['area_id']      ?? null,
                    'warehouse_id' => $filter['warehouse_id'] ?? null,
                ]);

                if (!empty($warehouseIds)) {
                    $query->whereIn('warehouse_id', $warehouseIds);
                }
            }

            if (!empty($filter['warehouse_id'])) {
                $warehouseIds = is_array($filter['warehouse_id'])
                    ? $filter['warehouse_id']
                    : explode(',', $filter['warehouse_id']);

                $query->whereIn('warehouse_id', array_map('intval', $warehouseIds));
            }

            if (!empty($filter['from_date'])) {
                $query->whereDate('invoice_date', '>=', $filter['from_date']);
            }

            if (!empty($filter['to_date'])) {
                $query->whereDate('invoice_date', '<=', $filter['to_date']);
            }

            $invoices = $query->get();

            $formatted = $invoices->map(function ($invoice) {
                return [
                    'invoice_id'   => $invoice->id,
                    'invoice_date' => $invoice->invoice_date,

                    'customer_name' => optional($invoice->customer)->name,
                    'customer_code' => optional($invoice->customer)->osa_code ?? "",

                    'warehouse_name' => optional($invoice->warehouse)->warehouse_name,
                    'warehouse_code' => optional($invoice->warehouse)->warehouse_code,

                    'fdn_no'       => $invoice->ura_invoice_no,
                    'gross_amount' => $invoice->total_amount,

                    'items' => $invoice->details->map(function ($detail) {
                        return [
                            'item_name' => optional($detail->item)->name,
                            'quantity'  => $detail->quantity,
                            'uom'       => optional($detail->itemuom)->name,   // ✅ FIX
                            'price'     => optional($detail->itemuom)->price,  // ✅ FIX
                            'total'     => $detail->item_total
                        ];
                    })
                ];
            });

            return $formatted;
        } catch (Throwable $e) {

            \Log::error('EFRIS Invoice Fetch Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            throw new \Exception("Unable to fetch invoices at the moment.", 500);
        }
    }


    public function syncInvoice($id)
    {
        try {
            $data = $this->fetchInvoiceData($id);
            // dd($data['warehouse']);

            if (!$data['success']) {
                return $data;
            }

            $payload = $this->buildPayload($data);
            // dd($payload);
            // ✅ Base service call
            $response = $this->makePost("T109", $payload, $data['warehouse']);
            // dd($response)
            $this->handleResponse(
                $data['header'],
                $data['warehouse'],
                $payload,
                $response
            );

            return $response;
        } catch (\Throwable $e) {

            \Log::error('EFRIS Sync Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function fetchInvoiceData($id)
    {
        $header = InvoiceHeader::with(['customer', 'warehouse'])->find($id);
        if (!$header) return ['success' => false, 'message' => 'Invoice not found'];

        $salesman = Salesman::find($header->salesman_id);
        if (!$salesman) return ['success' => false, 'message' => 'Salesman not found'];

        $customer = AgentCustomer::find($header->customer_id);
        if (!$salesman) return ['success' => false, 'message' => 'Agent not found'];

        $route = Route::find($header->route_id);
        if (!$route) return ['success' => false, 'message' => 'Route not found'];

        $warehouse = Warehouse::where('id', $header->warehouse_id)
            ->where('is_efris', 1)
            ->first();

        if (!$warehouse) return ['success' => false, 'message' => 'EFRIS warehouse not found'];

        $items = InvoiceDetail::with(['item.uoms'])
            ->where('header_id', $id)
            ->get();
        // dd($header);
        return [
            'success' => true,
            'header' => $header,
            'salesman' => $salesman,
            'warehouse' => $warehouse,
            'customer' => $customer,
            'items' => $items
        ];
    }

    private function buildPayload($data)
    {
        $header = $data['header'];
        $warehouse = $data['warehouse'];
        $salesman = $data['salesman'];
        $items = $data['items'];
        $customer = $data['customer'];

        $itemdetails = [];
        $all_total = 0;
        $all_vat = 0;

        foreach ($items as $key => $value) {

            $item = $value->item;
            if (!$item) continue;

            $altuom = $this->getUom($value->uom);

            $total = $value->itemvalue * $value->quantity;
            $vat = $total - ($total / 1.18);

            $all_total += $total;
            $all_vat += $vat;

            $itemdetails[] = [
                "item" => $item->name,
                "itemCode" => $item->sap_id,
                "qty" => $value->quantity,
                "unitOfMeasure" => $altuom,
                "unitPrice" => $value->itemvalue,
                "total" => number_format($total, 2, '.', ''),
                "taxRate" => "0.18",
                "tax" => number_format($vat, 2, '.', ''),
                "orderNumber" => $key,
                "discountFlag" => "2",
                "deemedFlag" => "2",
                "exciseFlag" => "2",
                "goodsCategoryId" => $item->commodity_goods_code
            ];
        }

        $tonet = $all_total - $all_vat;

        if ($header->ura_invoice_no !== '') {
            $modeCode = "1";
        } else {
            $modeCode = "0";
        }

        return [
            "sellerDetails" => [
                "tin" => $warehouse->tin_no,
                "legalName" => $warehouse->warehouse_name,
                "businessName" => $warehouse->warehouse_name,
                "emailAddress" => "$warehouse->owner_email",
                "placeOfBusiness" => $warehouse->city,
                "referenceNo" => $header->invoice_code,
                "branchName" => $warehouse->warehouse_name,
                "branchId" => $warehouse->branch_id,
            ],

            "basicInformation" => [
                "invoiceNo" => $header->ura_invoice_no,
                "deviceNo" => $warehouse->device_no,
                "antifakeCode" => $header->ura_antifake_code,
                "issuedDate" => $header->created_date,
                "operator" => $salesman->name,
                "currency" => "UGX",
                "oriInvoiceId" => "",
                "invoiceType" => "1",
                "invoiceKind" => "1",
                "dataSource" => "103",
                "invoiceIndustryCode" => "101",
            ],

            "buyerDetails" => [
                "buyerTin" => $customer->vat_no,
                "buyerMobilePhone" => $customer->contact_no,
                "buyerLegalName" => $customer->name,
                "buyerType" =>  "$customer->buyertype",
                "buyerCitizenship" => "Ugandan",
                "buyerSector" => "1",
            ],
            "buyerExtend" => [
                "district" => $customer->city,
            ],

            "goodsDetails" => $itemdetails,

            "taxDetails" => [[
                "taxCategoryCode" => "01",
                "netAmount" => number_format($tonet, 2, '.', ''),
                "taxRate" => "0.18",
                "taxAmount" => number_format($all_vat, 2, '.', ''),
                "grossAmount" => number_format($all_total, 2, '.', '')
            ]],

            "summary" => [
                "netAmount" => number_format($tonet, 2, '.', ''),
                "taxAmount" => number_format($all_vat, 2, '.', ''),
                "grossAmount" => number_format($all_total, 2, '.', ''),
                "itemCount" => count($itemdetails),
                "modeCode" => $modeCode,
                "remarks" => "Invoice from system",
                "qrCode" => ""
            ],

            "payWay" => [[
                "paymentMode" => "101",
                "paymentAmount" => number_format($all_total, 2, '.', ''),
                "orderNumber" => "a"
            ]],
            // "importServicesSeller" => array("importInvoiceDate" => $getagentheader->created_date),
            // "airlineGoodsDetails" => array(0 => array("item" => $getitemdetails[0]->item_name, "itemCode" => "", "qty" => "2", "unitOfMeasure" => "$altuom", "unitPrice" => "150.00", "total" => "1", "orderNumber" => "1")),
            // "edcDetails" => array("tankNo" => "1111", "pumpNo" => "2222", "nozzleNo" => "3333")
        ];
    }

    private function handleResponse($header, $warehouse, $payload, $response)
    {
        try {

            $inner = $response['inner_response'] ?? null;

            $isSuccess = ($response['returnCode'] ?? '') === "00";

            if ($isSuccess && isset($inner['basicInformation'])) {

                $basic = $inner['basicInformation'];
                $summary = $inner['summary'] ?? [];

                if (empty($header->ura_invoice_no) || empty($header->ura_antifake_code)) {

                    $header->update([
                        'ura_invoice_id'    => $basic['invoiceId'] ?? null,
                        'ura_invoice_no'    => $basic['invoiceNo'] ?? null,
                        'ura_antifake_code' => $basic['antifakeCode'] ?? null,
                        'ura_qr_code'       => $summary['qrCode'] ?? null,
                        'promotion_total'   => $header->promotion_total ?? 0
                    ]);
                } else {

                    $header->update([
                        'ura_invoice_id'  => $basic['invoiceId'] ?? null,
                        'promotion_total' => $header->promotion_total ?? 0
                    ]);
                }

                EfrisInvoiceFlag::updateOrCreate(
                    [
                        'invoice_id' => $header->id,
                        'warehouse_id' => $warehouse->id
                    ],
                    ['is_synced' => 1]
                );

                $this->writeLogFile(
                    "SyncedEfrisInvoice__" . $header->invoice_code,
                    $payload
                );
            } else {

                $message = $response['message'] ?? 'Unknown error';

                EfrisInvoiceErrorLog::create([
                    'interface_code' => 'T109',
                    'reference_number' => $header->invoice_code . $header->ura_invoice_no,
                    'reference_type' => 'Invoice',
                    'message' => $message,
                    'tin_no' => $warehouse->tin_no
                ]);

                if (str_contains($message, 'Invoice number already exists')) {

                    $header->update([
                        'ura_invoice_no' => null,
                        'ura_antifake_code' => null,
                        'ura_qr_code' => null
                    ]);
                }

                EfrisInvoiceFlag::updateOrCreate(
                    [
                        'invoice_id' => $header->id,
                        'warehouse_id' => $warehouse->id
                    ],
                    ['is_synced' => 0]
                );
            }
            EfrisInvoiceLog::create([
                'invoice_id' => $header->id,
                'warehouse_id' => $warehouse->id,
                'request_payload' => json_encode($payload),
                'response_payload' => json_encode($response),
                'is_success' => $isSuccess,
                'error_message' => $isSuccess ? null : ($response['message'] ?? null),
                'synced_at' => now()
            ]);
        } catch (\Throwable $e) {

            \Log::error('EFRIS HANDLE RESPONSE ERROR', [
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
        }
    }
    private function getUom($uom)
    {
        return match ($uom) {
            1, 3 => 'PP',
            2 => 'CS',
            4 => '110',
            default => 'PP'
        };
    }


    public function getUraInvoices($warehouseId, $from_date, $to_date, $page = 1)
    {
        $warehouse = Warehouse::find($warehouseId);

        if (!$warehouse) {
            return [
                'status' => false,
                'message' => 'Depot not found'
            ];
        }

        $payload = [
            "invoiceType" => "1",
            "startDate" => $from_date,
            "branchName" => $warehouse->branch_name ?? '',
            "endDate" => $to_date,
            "pageNo" => (string)$page,
            "pageSize" => "10"
        ];

        $resp = $this->makePost("T107", $payload, $warehouse);

        $records = $resp['inner_response']['records'] ?? [];
        if (!empty($records)) {

            foreach ($records as $value) {

                $invoiceNo = $value['invoiceNo'] ?? null;

                if (!$invoiceNo) continue;

                $invoice = InvoiceHeader::where('ura_invoice_no', $invoiceNo)->first();

                if ($invoice && !empty($invoice->ura_invoice_id)) {
                    continue;
                }

                $verifyResp = $this->makePost("T108", [
                    "invoiceNo" => $invoiceNo
                ], $warehouse);
                $verifyData = $verifyResp['inner_response'] ?? [];

                if (empty($verifyData)) continue;

                $totalDiscount = 0;

                foreach ($verifyData['goodsDetails'] ?? [] as $item) {
                    if (($item['discountFlag'] ?? null) == "1") {
                        $totalDiscount += abs($item['discountTotal'] ?? 0);
                    }
                }

                $referenceNo = $verifyData['sellerDetails']['referenceNo'] ?? null;

                if (!$referenceNo) continue;

                InvoiceHeader::where('invoice_number', $referenceNo)
                    ->update([
                        'ura_invoice_id' => $verifyData['basicInformation']['invoiceId'] ?? null,
                        'ura_invoice_no' => $verifyData['basicInformation']['invoiceNo'] ?? null,
                        'ura_antifake_code' => $verifyData['basicInformation']['antifakeCode'] ?? null,
                        'promotion_total' => $totalDiscount,
                        'ura_qr_code' => $verifyData['summary']['qrCode'] ?? null
                    ]);
            }
        }
        $invoiceNos = collect($records)
            ->pluck('invoiceNo')
            ->filter()
            ->toArray();

        $dbInvoices = InvoiceHeader::whereIn('ura_invoice_no', $invoiceNos)
            ->get(['ura_invoice_no', 'ura_antifake_code', 'uuid'])
            ->keyBy('ura_invoice_no');

        $records = collect($records)->map(function ($item) use ($dbInvoices) {

            $invoiceNo = $item['invoiceNo'] ?? null;

            $db = $invoiceNo ? ($dbInvoices[$invoiceNo] ?? null) : null;

            $item['ura_antifake_code'] = $db->ura_antifake_code ?? null;
            $item['uuid'] = $db->uuid ?? null;

            return $item;
        })->toArray();


        return [
            'status' => true,
            'message' => 'Invoice list fetched successfully',
            'data' => $records
        ];
    }
}
