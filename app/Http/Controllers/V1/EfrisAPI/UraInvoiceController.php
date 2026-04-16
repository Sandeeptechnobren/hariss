<?php

namespace App\Http\Controllers\V1\EfrisAPI;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\V1\EfrisAPI\UraInvoiceService;
use App\Models\Agent_Transaction\InvoiceHeader;
use App\Models\Agent_Transaction\InvoiceDetail;
use App\Models\Salesman;
use App\Models\SalesmanType;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Warehouse;
use App\Exports\EfrisExport\UraInvoiceExport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;
use App\Services\V1\EfrisAPI\BaseEfrisService;


class UraInvoiceController extends Controller
{
    protected $service;

    public function __construct(UraInvoiceService $service)
    {
        $this->service = $service;
    }

    // public function sync(Request $request)
    // {
    //     try {

    //         $request->validate([
    //             'invoice_ids' => 'required'
    //         ]);

    //         $ids = [];

    //         if ($request->invoice_ids === 'all') {

    //             $ids = InvoiceHeader::whereNull('ura_invoice_id')
    //                 ->where('status', 1)
    //                 ->pluck('id')
    //                 ->toArray();
    //         } elseif (is_array($request->invoice_ids)) {

    //             $ids = array_map('intval', $request->invoice_ids);
    //         } else {

    //             $ids = [(int) $request->invoice_ids];
    //         }

    //         if (empty($ids)) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'No invoices found for sync'
    //             ]);
    //         }

    //         $results = [];
    //         $successCount = 0;
    //         $failCount = 0;
    //         // dd($ids);
    //         foreach ($ids as $id) {

    //             try {

    //                 $response = $this->service->syncInvoice($id);
    //                 // dd($response);
    //                 if ($response['success'] ?? false) {
    //                     $successCount++;
    //                 } else {
    //                     $failCount++;
    //                 }

    //                 $results[] = [
    //                     'invoice_id' => $id,
    //                     'success' => $response['success'] ?? false,
    //                     'message' => $response['message'] ?? '',
    //                     'returnCode' => $response['returnCode'] ?? null,
    //                     'inner_response' => $response['inner_response'] ?? null
    //                 ];
    //             } catch (\Throwable $e) {

    //                 $failCount++;

    //                 \Log::error('Invoice Sync Failed', [
    //                     'invoice_id' => $id,
    //                     'message' => $e->getMessage()
    //                 ]);

    //                 $results[] = [
    //                     'invoice_id' => $id,
    //                     'success' => false,
    //                     'message' => $e->getMessage()
    //                 ];
    //             }
    //         }

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Invoice sync completed',
    //             'total' => count($ids),
    //             'success_count' => $successCount,
    //             'fail_count' => $failCount,
    //             'data' => $results
    //         ]);
    //     } catch (\Throwable $e) {

    //         return response()->json([
    //             'status' => false,
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }


    public function sync(Request $request)
    {
        try {

            $request->validate([
                'invoice_ids' => 'required'
            ]);

            $input = $request->invoice_ids;

            if (!preg_match('/^\d+(,\s*\d+)*$/', $input)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid format. Use "1,2,3"'
                ], 422);
            }

            $ids = array_map('intval', array_map('trim', explode(',', $input)));

            $ids = array_values(array_filter(array_unique($ids)));

            if (empty($ids)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No valid invoice ids provided'
                ], 422);
            }

            $results = [];
            $successCount = 0;
            $failCount = 0;

            foreach ($ids as $id) {

                try {

                    $response = $this->service->syncInvoice($id);

                    // 🔥 STRICT EFRIS CHECK
                    $isSuccess =
                        ($response['returnCode'] ?? null) === "00" &&
                        strtoupper($response['message'] ?? '') === "SUCCESS";

                    if ($isSuccess) {
                        $successCount++;
                    } else {
                        $failCount++;
                    }

                    $results[] = [
                        'invoice_id' => $id,
                        'success' => $isSuccess,
                        'message' => $response['message'] ?? '',
                        'returnCode' => $response['returnCode'] ?? null,
                        'inner_response' => $response['inner_response'] ?? null
                    ];
                } catch (\Throwable $e) {

                    $failCount++;

                    \Log::error('Invoice Sync Failed', [
                        'invoice_id' => $id,
                        'error' => $e->getMessage()
                    ]);

                    $results[] = [
                        'invoice_id' => $id,
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
            }

            // 🔥 FINAL STATUS DECISION
            $status = $successCount > 0;

            return response()->json([
                'status' => $status,
                'message' => $status
                    ? 'Invoice sync completed'
                    : 'All invoice sync failed',
                'total' => count($ids),
                'success_count' => $successCount,
                'fail_count' => $failCount,
                'data' => $results
            ], $status ? 200 : 422);
        } catch (\Throwable $e) {

            \Log::error('Invoice Sync Fatal Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function getInvoices(Request $request)
    {
        try {
            $data = $this->service->getInvoices(
                $request->all()
            );

            return response()->json([
                'success' => true,
                'message' => 'Invoices fetched successfully',
                'data' => $data
            ]);
        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateUraInvoice(Request $request)
    {
        try {
            $request->validate([
                'invoice_FNo' => 'required|string'
            ]);

            $invoiceFNo = $request->invoice_FNo;
            // dd($invoiceFNo);
            $updated = InvoiceHeader::where('ura_invoice_no', $invoiceFNo)
                ->whereNull('ura_invoice_id')
                ->update([
                    'ura_invoice_id' => '1234'
                ]);

            if ($updated === 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'No record found or already updated.'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Invoice updated successfully.',
                'updated_rows' => $updated
            ]);
        } catch (Throwable $e) {

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getUraInvoices(Request $request)
    {
        $request->validate([
            'filter.warehouse_id' => 'required',
            'filter.from_date' => 'required|date',
            'filter.to_date' => 'required|date',
            'filter.page' => 'nullable|integer'
        ]);

        $filter = $request->input('filter');

        $response = $this->service->getUraInvoices(
            $filter['warehouse_id'],
            $filter['from_date'],
            $filter['to_date'],
            $filter['page'] ?? 1
        );

        return response()->json($response, $response['status'] ? 200 : 422);
    }

    // public function exportUraInvoicesPDF($uuid)
    // {
    //     $header = InvoiceHeader::where('uuid', $uuid)->first();

    //     if (!$header) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Invoice not found'
    //         ]);
    //     }

    //     $invoiceItems = InvoiceDetail::where('header_id', $header->id)->get();

    //     $type = '';

    //     if ($header->salesman_id) {

    //         $salesman = Salesman::find($header->salesman_id);

    //         if ($salesman) {
    //             $salesmanType = SalesmanType::where('id', $salesman->type)->first();

    //             if ($salesmanType) {
    //                 $type = $salesmanType->name;
    //             }
    //         }
    //     }

    //     // if ($header->salesman_id) {

    //     //     $salesman = Salesman::find($header->salesman_id);

    //     //     if ($salesman && $salesman->type) {

    //     //         $salesmanType = SalesmanType::where('id', $salesman->type)->first();

    //     //         $type = $salesmanType->name ?? '';
    //     //     }
    //     // }
    //     $code = $header->invoice_code ?? ('INV-' . time());
    //     $code = preg_replace('/[^A-Za-z0-9\-]/', '', $code);

    //     $folder = 'urainvoiceexports';
    //     Storage::disk('public')->makeDirectory($folder);

    //     $count = 1;

    //     do {
    //         $suffix = str_pad($count, 2, '0', STR_PAD_LEFT);
    //         $filename = "Ura_Invoice_{$code}_{$suffix}.pdf";
    //         $path = "{$folder}/{$filename}";

    //         $exists = Storage::disk('public')->exists($path);
    //         $count++;
    //     } while ($exists);

    //     if (!empty($header->id)) {

    //         $pdf = Pdf::loadView('UraInvoice', [
    //             'header' => $header,
    //             'items' => $invoiceItems,
    //             'type' => $type
    //         ])->setPaper('A4');

    //         Storage::disk('public')->put($path, $pdf->output());
    //     }

    //     $appUrl = rtrim(config('app.url'), '/');
    //     $fullUrl = $appUrl . '/storage/app/public/' . $path;

    //     return response()->json([
    //         'status' => 'success',
    //         'download_url' => $fullUrl,
    //     ]);
    // }

    public function exportUraInvoices(Request $request)
    {
        $request->validate([
            'filter.warehouse_id' => 'required',
            'filter.from_date' => 'required|date',
            'filter.to_date' => 'required|date',
            'filter.page' => 'nullable|integer'
        ]);

        $format    = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';

        $date = now()->format('Ymd_His');
        $random = rand(1000, 9999);

        $filename = "EfrisInvoiceReport_{$date}_{$random}.{$extension}";
        $path = 'invoiceexports/' . $filename;

        $filter = $request->input('filter');

        $warehouse = Warehouse::find($filter['warehouse_id']);

        if (!$warehouse) {
            return response()->json([
                'status' => false,
                'message' => 'Depot not found'
            ], 422);
        }

        // 🔥 T107 payload
        $payload = [
            "invoiceType" => "1",
            "startDate" => $filter['from_date'],
            "branchName" => $warehouse->branch_name ?? '',
            "endDate" => $filter['to_date'],
            "pageNo" => (string) ($filter['page'] ?? 1),
            "pageSize" => "10"
        ];

        $efris = app(BaseEfrisService::class);
        $resp = $efris->callApi("T107", $payload, $warehouse);

        $records = $resp['inner_response']['records'] ?? [];

        // 🔥 MAP DB DATA (ONLY READ)
        $invoiceNos = collect($records)->pluck('invoiceNo')->filter()->toArray();

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

        // 🔥 EXPORT FILE
        $export = new UraInvoiceExport($records);

        if ($format === 'csv') {
            \Maatwebsite\Excel\Facades\Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
        } else {
            \Maatwebsite\Excel\Facades\Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
        }

        $fullUrl = rtrim(config('app.url'), '/') . '/storage/app/public/' . $path;

        return response()->json([
            'status' => true,
            'download_url' => $fullUrl
        ]);
    }
}
