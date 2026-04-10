<?php

namespace App\Http\Controllers\V1\Hariss_Transaction\Web;

use App\Http\Controllers\Controller;
use App\Services\V1\Hariss_Transaction\Web\InvoiceService;
use App\Http\Resources\V1\Hariss_Transaction\Web\InvoiceHeaderResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use App\Exports\HtInvoiceFullExport;
use App\Exports\HtInvoiceCollapseExport;
use App\Http\Resources\V1\Hariss_Transaction\Web\CompensationResource;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Hariss_Transaction\Web\HTInvoiceHeader;
use App\Models\Hariss_Transaction\Web\HTInvoiceDetail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use App\Helpers\CommonLocationFilter;

class HTInvoiceController extends Controller
{
    protected $service;

    public function __construct(InvoiceService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/hariss_transaction/ht_invoice/list",
     *     tags={"Invoice"},
     *     summary="Get paginated list of invoices with filters",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="customer_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="salesman_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="invoice_code", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="from_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="dropdown", in="query", @OA\Schema(type="boolean")),
     *
     *     @OA\Response(response=200, description="Invoice List")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage  = $request->get('limit', 20);
            $dropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);
            $filters  = $request->except(['limit', 'dropdown']);

            $data = $this->service->getAll($perPage, $filters, $dropdown);

            if ($dropdown) {
                return response()->json([
                    'status' => 'success',
                    'code'   => 200,
                    'data'   => $data
                ]);
            }

            $pagination = [
                'page'         => $data->currentPage(),
                'limit'        => $data->perPage(),
                'totalPages'   => $data->lastPage(),
                'totalRecords' => $data->total()
            ];

            return response()->json([
                'status'     => 'success',
                'code'       => 200,
                'data'       => InvoiceHeaderResource::collection($data),
                'pagination' => $pagination
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve invoices',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/hariss_transaction/ht_invoice/{uuid}",
     *     tags={"Invoice"},
     *     summary="Get invoice by UUID",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Invoice details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $invoice = $this->service->getByUuid($uuid);

            if (!$invoice) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 404,
                    'message' => 'Invoice not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'code'   => 200,
                'data'   => new InvoiceHeaderResource($invoice)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve invoice',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function exportInvoiceHeader(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = 'Invoice_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'invoiceexports/' . $filename;

        $filters = $request->input('filter', []);

        $fromDate = $filters['from_date'] ?? Null;
        $toDate   = $filters['to_date'] ?? Null;

        $salesmanIds = !empty($filters['salesman_id'])
            ? explode(',', $filters['salesman_id'])
            : [];

        $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);

        $export = new HtInvoiceFullExport(
            $fromDate,
            $toDate,
            $warehouseIds,
            $salesmanIds
        );

        // $export = new HtInvoiceFullExport($fromDate, $toDate);

        if ($format === 'csv') {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
        } else {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
        }

        $appUrl = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json([
            'status'       => 'success',
            'download_url' => $fullUrl,
        ]);
    }

    public function exportInvoiceCollapse(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
         $filename = 'invoice_collapse_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'invoiceexports/' . $filename;

        $filters = $request->input('filter', []);

        $fromDate = $filters['from_date'] ?? Null;
        $toDate   = $filters['to_date'] ?? Null;

        $salesmanIds = !empty($filters['salesman_id'])
            ? explode(',', $filters['salesman_id'])
            : [];

        $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);

        $export = new HtInvoiceCollapseExport(
            $fromDate,
            $toDate,
            $warehouseIds,
            $salesmanIds
        );

       
        // $export = new HtInvoiceCollapseExport($fromDate, $toDate);

        if ($format === 'csv') {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
        } else {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
        }

        $appUrl = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json([
            'status'       => 'success',
            'download_url' => $fullUrl,
        ]);
    }

    public function filter(Request $request)
    {
        // dd($request);
        $request->validate([
            "from_date" => "required|date",
            "to_date" => "required|date"
        ]);

        $perPage = $request->per_page ?? 50;

        // Call service
        $result = $this->service->filterInvoiceDetails($request->all(), $perPage);

        $data = $result['data'];

        return response()->json([
            "status" => "success",
            "message" => "Invoice details filtered successfully.",
            "data" => CompensationResource::collection($data->items()),

            "pagination" => [
                "total" => $data->total(),
                "per_page" => $data->perPage(),
                "current_page" => $data->currentPage(),
                "last_page" => $data->lastPage(),
            ]
        ]);
    }

    public function export(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';

        $fromDate = $request->input('from_date');
        $toDate   = $request->input('to_date');

        // Filename
        $filename = 'invoice_details_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'invoiceexports/' . $filename;

        // FETCH DATA FOR EXPORT USING SERVICE FLAG
        $filters = [
            "from_date" => $fromDate,
            "to_date"   => $toDate,
            "for_export" => true
        ];

        $result = $this->service->exportInvoiceDetails($filters);
        $rows = $result['data']; // FULL DATA (NO PAGINATION)

        // EXPORT CLASS
        $export = new \App\Exports\InvoiceFullExport($rows);

        // STORE FILE
        if ($format === 'csv') {
            \Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
        } else {
            \Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
        }

        // PUBLIC DOWNLOAD URL
        $appUrl = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json([
            'status'       => 'success',
            'download_url' => $fullUrl,
        ]);
    }

    public function exportHtInvoice(Request $request)
    {
        $uuid = $request->input('uuid');
        $format = strtolower($request->input('format', 'pdf'));
        $extension = in_array($format, ['pdf', 'csv', 'xlsx']) ? $format : 'pdf';
        $filename = 'htinvoice_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'exports/' . $filename;
        if ($extension === 'pdf') {
            $order = HTInvoiceHeader::with(['warehouse', 'customer'])
                ->where('uuid', $uuid)
                ->firstOrFail();
            $orderDetails = HTInvoiceDetail::with(['item', 'uoms'])
                ->where('header_id', $order->id)
                ->get();
            $pdf = \PDF::loadView('htinvoice', [
                'order'         => $order,
                'orderDetails'  => $orderDetails
            ]);
            Storage::disk('public')->put($path, $pdf->output());
            $appUrl = rtrim(config('app.url'), '/');
            $fullUrl = $appUrl . '/storage/app/public/' . $path;
            return response()->json([
                'status'        => 'success',
                'download_url'  => $fullUrl
            ]);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'Unsupported export format.'
        ], 400);
    }

    public function getFilteredInvoiceDetails(Request $request)
    {
        $request->validate([
            'item_id' => 'required|integer',
            'upc'     => 'required',
            'quantity' => 'required|numeric'
        ]);

        $itemId   = $request->item_id;
        $upc      = $request->upc;
        $quantity = $request->quantity;

        $details = HTInvoiceDetail::select('id', 'item_id', 'uom_id', 'quantity')
            ->where('item_id', $itemId)
            ->where('uom_id', $upc)
            ->where('quantity', '>', $quantity)
            ->get();

        return response()->json([
            'status' => 'success',
            'count'  => $details->count(),
            'data'   => $details
        ], 200);
    }

    public function filterByExpiry(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|integer',
            'item_id'      => 'required|integer',
            'uom'          => 'required|integer',
            'quantity'     => 'required|numeric',
            'expiry_date'  => 'required|date'
        ]);

        $warehouseId = $request->warehouse_id;
        $itemId      = $request->item_id;
        $uom         = $request->uom;
        $quantity    = $request->quantity;

        $customerIds = HtInvoiceHeader::where('warehouse_id', $warehouseId)
            ->distinct()
            ->pluck('customer_id');

        if ($customerIds->isEmpty()) {
            return response()->json([
                'status'  => 'not_found',
                'message' => 'No customers found for this distributor.'
            ], 404);
        }

        $convertedDate = date('Y-m-d', strtotime($request->expiry_date));
        $newDate       = date('Y-m-d', strtotime($convertedDate . ' +2 days'));
        $oldDate       = date('Y-m-d', strtotime($convertedDate . ' -2 days'));

        $headerIds = HtInvoiceHeader::where('warehouse_id', $warehouseId)
            ->whereIn('customer_id', $customerIds)
            ->pluck('id');

        if ($headerIds->isEmpty()) {
            return response()->json([
                'status'  => 'not_found',
                'message' => 'No invoice headers found for this distributor.'
            ], 404);
        }

        $upcValue = 1;

        if (in_array($uom, [1, 3])) {
            $upcValue = DB::table('item_uoms')
                ->where('item_id', $itemId)
                ->where('uom_id', $uom)
                ->value('upc');

            if (!$upcValue || $upcValue <= 0) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Invalid UPC configuration for this item and UOM.'
                ], 422);
            }
        }

        $details = HtInvoiceDetail::select(
            'ht_invoice_detail.header_id',
            'ht_invoice_detail.batch_number',
            'ht_invoice_detail.inv_position_no',
            'ht_invoice_detail.quantity',
            'ht_invoice_detail.batch_expiry_date',
            'ht_invoice_header.invoice_number',
            'ht_invoice_header.sap_id',
            DB::raw("
                CASE 
                    WHEN {$uom} IN (1,3) 
                    THEN ht_invoice_detail.item_price / {$upcValue}
                    ELSE ht_invoice_detail.item_price
                END AS item_price
            ")
        )
            ->join('ht_invoice_header', 'ht_invoice_header.id', '=', 'ht_invoice_detail.header_id')
            ->whereIn('ht_invoice_detail.header_id', $headerIds)
            ->where('ht_invoice_detail.item_id', $itemId)
            ->where('ht_invoice_detail.uom_id', $uom)
            ->where('ht_invoice_detail.quantity', '>', $quantity)
            ->whereBetween('ht_invoice_detail.batch_expiry_date', [$oldDate, $newDate])
            ->get();

        return response()->json([
            'status' => 'success',
            'count'  => $details->count(),
            'data'   => $details
        ], 200);
    }


    public function globalFilter(Request $request): JsonResponse
    {
        try {

            $perPage  = $request->get('limit', 20);
            $dropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);

            $filters  = $request->except(['limit', 'dropdown']);

            $data = $this->service->globalFilter($perPage, $filters);

            if ($dropdown) {
                return response()->json([
                    'status' => 'success',
                    'code'   => 200,
                    'data'   => $data
                ]);
            }

            $pagination = [
                'page'         => $data->currentPage(),
                'limit'        => $data->perPage(),
                'totalPages'   => $data->lastPage(),
                'totalRecords' => $data->total()
            ];

            return response()->json([
                'status'     => 'success',
                'code'       => 200,
                'data'       => InvoiceHeaderResource::collection($data),
                'pagination' => $pagination
            ]);
        } catch (Exception $e) {

            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve invoices',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
