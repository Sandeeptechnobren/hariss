<?php

namespace App\Http\Controllers\V1\Agent_Transaction;

use App\Exports\StockTransferCollapseExport;
use App\Exports\StockTransferExport;
use App\Http\Controllers\Controller;
use App\Services\V1\Agent_Transaction\StockTransferService;
use App\Http\Resources\V1\Agent_Transaction\StockTransferHeaderResource;
use Illuminate\Http\JsonResponse;
use Throwable;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Models\StockTransferHeader;
use App\Models\WarehouseStock;

class StockTransferListController extends Controller
{
    protected StockTransferService $service;

    public function __construct(StockTransferService $service)
    {
        $this->service = $service;
    }

    public function list(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 10);

            // 🔹 Allowed filters
            $filters = $request->only([
                'osa_code',
                'status',
                'source_warehouse',
                'destiny_warehouse',
                // 'warehouse_id',   // ✅ SINGLE PARAM
                'transfer_date',
                'from_date',
                'to_date',
            ]);

            $paginator = $this->service->list($perPage, $filters);

            return response()->json([
                'status' => 'success',
                'data'   => StockTransferHeaderResource::collection($paginator->items()),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                    'last_page'    => $paginator->lastPage(),
                ],
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    // public function list(Request $request): JsonResponse
    // {
    //     try {
    //         $perPage = (int) $request->get('per_page', 10);

    //         $paginator = $this->service->list($perPage);

    //         return response()->json([
    //             'status' => 'success',
    //             'data'   => StockTransferHeaderResource::collection($paginator->items()),
    //             'pagination'   => [
    //                 'current_page' => $paginator->currentPage(),
    //                 'per_page'     => $paginator->perPage(),
    //                 'total'        => $paginator->total(),
    //                 'last_page'    => $paginator->lastPage(),
    //             ],
    //         ], 200);
    //     } catch (Throwable $e) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function show(string $uuid): JsonResponse
    {
        try {
            $record = $this->service->findByUuid($uuid);

            return response()->json([
                'status' => 'success',
                'data'   => $record,
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 404);
        }
    }


    public function getWarehouse(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|integer|exists:tbl_warehouse,id'
        ]);

        $result = $this->service
            ->getWarehouse($request->warehouse_id);

        if (!$result['success']) {
            return response()->json($result, 404);
        }

        return response()->json($result);
    }

    // public function exportHeader(Request $request)
    // {
    //     $format    = strtolower($request->input('format', 'xlsx'));
    //     $extension = $format === 'csv' ? 'csv' : 'xlsx';

    //     $date = now()->format('dmY');
    //     $baseName = 'Stock_Transfer_' . $date;

    //     $directory = 'stockexports/';

    //     // ✅ existing files fetch
    //     $files = Storage::disk('public')->files($directory);

    //     $existingNumbers = [];

    //     foreach ($files as $file) {
    //         if (preg_match('/' . $date . '_(\d+)/', $file, $matches)) {
    //             $existingNumbers[] = (int) $matches[1];
    //         }
    //     }

    //     // ✅ next count
    //     $next = empty($existingNumbers) ? 1 : max($existingNumbers) + 1;

    //     // ✅ format 01, 02
    //     $counter = str_pad($next, 2, '0', STR_PAD_LEFT);

    //     $filename = $baseName . '_' . $counter . '.' . $extension;
    //     $path     = $directory . $filename;

    //     $filters = $request->input('filter', []);

    //     $fromDate = $filters['from_date'] ?? null;
    //     $toDate   = $filters['to_date'] ?? null;

    //     $warehouseIds = !empty($filters['warehouse_id'])
    //         ? explode(',', $filters['warehouse_id'])
    //         : [];
    //     // dd($filters);
    //     $export = new StockTransferExport($fromDate, $toDate, $warehouseIds);

    //     if ($format === 'csv') {
    //         Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
    //     } else {
    //         Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
    //     }

    //     $fullUrl = rtrim(config('app.url'), '/') . '/storage/app/public/' . $path;

    //     return response()->json([
    //         'status' => 'success',
    //         'download_url' => $fullUrl,
    //     ]);
    // }

    public function exportHeader(Request $request)
{
    $format    = strtolower($request->input('format', 'xlsx'));
    $extension = $format === 'csv' ? 'csv' : 'xlsx';

    $date = now()->format('dmY');
    $baseName = 'Stock_Transfer_' . $date;

    $directory = 'stockexports/';

    // existing files
    $files = Storage::disk('public')->files($directory);

    $existingNumbers = [];

    foreach ($files as $file) {
        if (preg_match('/' . $date . '_(\d+)/', $file, $matches)) {
            $existingNumbers[] = (int) $matches[1];
        }
    }

    $next = empty($existingNumbers) ? 1 : max($existingNumbers) + 1;
    $counter = str_pad($next, 2, '0', STR_PAD_LEFT);

    $filename = $baseName . '_' . $counter . '.' . $extension;
    $path     = $directory . $filename;

    $filters = $request->input('filter', []);

    $fromDate = $filters['from_date'] ?? null;
    $toDate   = $filters['to_date'] ?? null;

    $warehouseIds = !empty($filters['warehouse_id'])
        ? explode(',', $filters['warehouse_id'])
        : [];

    $export = new StockTransferExport($fromDate, $toDate, $warehouseIds);

    if ($format === 'csv') {
        Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
    } else {
        Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
    }

    // ✅ correct public URL
    $fullUrl = asset('storage/' . $path);

    return response()->json([
        'status' => 'success',
        'download_url' => $fullUrl,
    ]);
}
    public function exportCollaps(Request $request)
    {
        $format    = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';

        $date = now()->format('dmY');
        $baseName = 'Stock_Transfer_' . $date;

        $directory = 'stockexports/';

        // ✅ existing files fetch
        $files = Storage::disk('public')->files($directory);

        $existingNumbers = [];

        foreach ($files as $file) {
            if (preg_match('/' . $date . '_(\d+)/', $file, $matches)) {
                $existingNumbers[] = (int) $matches[1];
            }
        }

        // ✅ next count
        $next = empty($existingNumbers) ? 1 : max($existingNumbers) + 1;

        // ✅ format 01, 02
        $counter = str_pad($next, 2, '0', STR_PAD_LEFT);

        $filename = $baseName . '_' . $counter . '.' . $extension;
        $path     = $directory . $filename;
        $filters = $request->input('filter', []);

        $fromDate = $filters['from_date'] ?? null;
        $toDate   = $filters['to_date'] ?? null;

        $warehouseIds = !empty($filters['warehouse_id'])
            ? explode(',', $filters['warehouse_id'])
            : [];
        // dd($request->uuid);
        $export = new StockTransferCollapseExport($fromDate, $toDate, $warehouseIds,  $request->uuid);

        if ($format === 'csv') {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
        } else {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
        }

        $fullUrl = rtrim(config('app.url'), '/') . '/storage/app/public/' . $path;

        return response()->json([
            'status' => 'success',
            'download_url' => $fullUrl,
        ]);
    }

    public function globalFilter(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 10);

            // ✅ FULL FILTER STRUCTURE PASS
            $filters = [
                'filter'       => $request->input('filter', []),
                'current_page' => $request->input('current_page')
            ];

            $paginator = $this->service->globalFilter($perPage, $filters);

            return response()->json([
                'status' => 'success',
                'data'   => StockTransferHeaderResource::collection($paginator->items()),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                    'last_page'    => $paginator->lastPage(),
                ],
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

public function exportstockpdffull(Request $request)
{
    $uuid   = $request->input('uuid');
    $format = strtolower($request->input('format', 'xlsx'));

    $extension = $format === 'csv' ? 'csv' : ($format === 'pdf' ? 'pdf' : 'xlsx');

    $deliveryHeader = StockTransferHeader::select('osa_code')
        ->where('uuid', $uuid)
        ->first();

    $code = $deliveryHeader?->osa_code ?? 'UNKNOWN';
    $code = preg_replace('/[^A-Za-z0-9\-]/', '', $code);

    $filename = 'stock_transfer_' . $code . '.' . $extension;
    $path = 'exports/' . $filename;

    if ($format === 'csv' || $format === 'xlsx') {

        $export = new DeliveryFulllExport($uuid);

        if ($format === 'csv') {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
        } else {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
        }
    }

    if ($format === 'pdf') {

        $delivery = StockTransferHeader::with([
            'sourceWarehouse',
            'destinyWarehouse',
            'details.item'
        ])->where('uuid', $uuid)->first();

        if (!$delivery) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Stocks not found.',
            ]);
        }

        $deliveryDetails = $delivery->details->map(function ($detail) use ($delivery) {

            $fromStock = WarehouseStock::where('warehouse_id', $delivery->source_warehouse)
                ->where('item_id', $detail->item_id)
                ->whereNull('deleted_at')
                ->first();

            $toStock = WarehouseStock::where('warehouse_id', $delivery->destiny_warehouse)
                ->where('item_id', $detail->item_id)
                ->whereNull('deleted_at')
                ->first();

            return [
                'erp_code'  => $detail->item->erp_code ?? '',
                'item_name' => $detail->item->name ?? '',
                'transfer_qty' => $detail->transfer_qty ?? 0,
                'source_warehouse_stock'  => $fromStock?->qty ?? 0,
                'destiny_warehouse_stock' => $toStock?->qty ?? 0,
            ];
        });

        $pdf = \PDF::loadView('stocktransfer', [
            'delivery'        => $delivery,
            'deliveryDetails' => $deliveryDetails
        ])->setPaper('A4');

        \Storage::disk('public')->makeDirectory('exports');
        \Storage::disk('public')->put($path, $pdf->output());
    }

    $appUrl  = rtrim(config('app.url'), '/');
    $fullUrl = $appUrl . '/storage/app/public/' . $path;

    return response()->json([
        'status'       => 'success',
        'download_url' => $fullUrl,
    ]);
}
}
