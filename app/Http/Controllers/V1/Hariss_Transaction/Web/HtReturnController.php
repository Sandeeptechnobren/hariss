<?php

namespace App\Http\Controllers\V1\Hariss_Transaction\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Hariss_Transaction\Web\HtReturnHeaderResource;
use App\Http\Resources\V1\Hariss_Transaction\Web\ReturnheaderResource;
use App\Services\V1\Hariss_Transaction\Web\HtReturnService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Hariss_Transaction\Web\HtReturnHeader;
use App\Models\Hariss_Transaction\Web\TempReturnH;
use App\Models\Hariss_Transaction\Web\TempReturnD;
use App\Models\Hariss_Transaction\Web\HtReturnDetail;
use App\Exports\HTReturnExport;
use App\Exports\ReturnHCollapseExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\V1\Hariss_Transaction\Web\HtReturnStoreRequest;
use App\Helpers\LogHelper;
use App\Helpers\DataAccessHelper;
use App\Helpers\CommonLocationFilter;


class HtReturnController extends Controller
{
    protected $service;

    public function __construct(HtReturnService $service)
    {
        $this->service = $service;
    }

    public function exportHtReturnHeader(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = 'returnht_header_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'htreturnexports/' . $filename;

        $filters = $request->input('filter', []);

        $fromDate = $filters['from_date'] ?? Null;
        $toDate   = $filters['to_date'] ?? Null;

        // $salesmanIds = !empty($filters['salesman_id'])
        //     ? explode(',', $filters['salesman_id'])
        //     : [];

        $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);

        $export = new HTReturnExport(
            $fromDate,
            $toDate,
            $warehouseIds
        );

        // $export = new HTReturnExport($fromDate, $toDate);

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

    public function exportReturnCollapse(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = 'return_collapse_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'returnexports/' . $filename;

        $filters = $request->input('filter', []);
        $fromDate = $filters['from_date'] ?? Null;
        $toDate   = $filters['to_date'] ?? Null;

        $salesmanIds = !empty($filters['salesman_id'])
            ? explode(',', $filters['salesman_id'])
            : [];
        // dd($filters);
        $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);
        $export = new ReturnHCollapseExport(
            $fromDate,
            $toDate,
            $warehouseIds,
            $salesmanIds
        );

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

    public function exportHtReturns(Request $request)
    {
        $uuid = $request->input('uuid');
        $format = strtolower($request->input('format', 'pdf'));
        $extension = in_array($format, ['pdf', 'csv', 'xlsx']) ? $format : 'pdf';
        $filename = 'htreturn_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'exports/' . $filename;
        if ($extension === 'pdf') {
            $order = TempReturnH::with(['customer'])
                ->where('uuid', $uuid)
                ->first();
            
            if (!$order) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Record not found for given UUID'
                ], 404);
            }

            $orderDetails = TempReturnD::with(['item'])
                ->where('header_id', $order->id)
                ->get();

            $order->vat   = $orderDetails->sum('vat');
            $order->net   = $orderDetails->sum('net');
            $order->total = $orderDetails->sum('total');

            $pdf = \PDF::loadView('htreturn', [
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

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage  = $request->get('limit', 50);
            $dropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);
            $filters = $request->except(['limit', 'dropdown']);
            $returns = $this->service->list($perPage, $filters, $dropdown);
            if ($dropdown) {
                return response()->json([
                    'status' => 'success',
                    'code'   => 200,
                    'data'   => $returns
                ]);
            }
            $pagination = [
                'page'         => $returns->currentPage(),
                'limit'        => $returns->perPage(),
                'totalPages'   => $returns->lastPage(),
                'totalRecords' => $returns->total(),
            ];
            return response()->json([
                'status'     => 'success',
                'code'       => 200,
                'data'       => ReturnheaderResource::collection($returns),
                'pagination' => $pagination
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve return records',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, string $uuid)
    {
        try {
            $record = $this->service->viewByUuid($uuid);
            if (!$record) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 404,
                    'message' => 'Return record not found for the given UUID'
                ], 404);
            }
            return response()->json([
                'status' => 'success',
                'code'   => 200,
                'data'   => new HtReturnHeaderResource($record)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve return record',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function fetchBatch(Request $request)
    {
        $request->validate([
            'expery_date' => 'required',
            'item_id'     => 'required|integer',
            'qty'         => 'required|numeric',
            'uom_id'      => 'required|integer',
            'customer_id' => 'required|integer',
        ]);

        $data = $this->service->fetchBatch(
            $request->expery_date,
            $request->item_id,
            $request->customer_id,
            $request->qty,
            $request->uom_id
        );

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function saveReturn(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = $this->service->processReturn($request->all());
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Return processed successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }
    // public function create(Request $request)
    //     {
    //         $validated = $request->validate([
    //             'return_code'   => 'required|string',
    //             'customer_id'   => 'required|integer|exists:tbl_company_customer,id',
    //             'vat'           => 'required|integer',
    //             'net'           => 'required|integer',
    //             'amount'        => 'required|integer',
    //             'driver_id'     => 'required|integer|exists:drivers,id',
    //             'truck_no'      => 'required|string',
    //             'contact_no'    => 'required|string',
    //             'sap_id'        => 'required|integer',
    //             'message'       => 'nullable|string',
    //             'company_id'    => 'nullable|integer',
    //             'warehouse_id'  => 'required|integer|exists:tbl_warehouse,id',
    //             'details'                   => 'required|array',
    //             'details.*.posnr'           => 'required|integer',
    //             'details.*.item_id'         => 'required|integer|exists:items,id',
    //             'details.*.item_value'      => 'required|numeric',
    //             'details.*.vat'             => 'required|numeric',
    //             'details.*.uom'             => 'required|integer',
    //             'details.*.qty'             => 'required|numeric|min:1',
    //             'details.*.net'             => 'required|numeric',
    //             'details.*.total'           => 'required|numeric',
    //             'details.*.expiry_batch'    => 'nullable|string',
    //             'details.*.return_type'     => 'required|integer|exists:return_type,id',
    //             'details.*.return_reason'   => 'required|integer|exists:reson_type,id',
    //             'details.*.batch_no'        => 'required|string',
    //             'details.*.actual_expiry_date'=> 'required|string',
    //             'details.*.invoice_sap_id'  => 'required|string',
    //         ]);
    //         $header=HtReturnHeader::create([
    //         'return_code'  => $validated['return_code'],
    //         'customer_id'  => $validated['customer_id'],
    //         'vat'          => $validated['vat'],
    //         'net'          => $validated['net'],
    //         'amount'       => $validated['amount'],
    //         'driver_id'    => $validated['driver_id'],
    //         'truck_no'     => $validated['truck_no'],
    //         'contact_no'   => $validated['contact_no'],
    //         'sap_id'       => $validated['sap_id'],
    //         'message'      => $validated['message'] ?? null,
    //         'company_id'   => $validated['company_id'] ?? null,
    //         'warehouse_id' => $validated['warehouse_id'],
    //         ]);
    //         $detailsInsert = [];

    //         foreach ($validated['details'] as $detail) {
    //             $detailsInsert[] = [
    //                 'header_id'         => $header->id,
    //                 'posnr'             => $detail['posnr'],
    //                 'item_id'           => $detail['item_id'],
    //                 'item_value'        => $detail['item_value'],
    //                 'vat'               => $detail['vat'],
    //                 'uom'               => $detail['uom'],
    //                 'qty'               => $detail['qty'],
    //                 'net'               => $detail['net'],
    //                 'total'             => $detail['total'],
    //                 'expiry_batch'      => $detail['expiry_batch'] ?? null,
    //                 'return_type'       => $detail['return_type'],
    //                 'return_reason'     => $detail['return_reason'],
    //                 'batch_no'          => $detail['batch_no'],
    //                 'actual_expiry_date' => $detail['actual_expiry_date'],
    //                 'invoice_sap_id'    => $detail['invoice_sap_id'],
    //                 'created_at'        => now(),
    //                 'updated_at'        => now(),
    //             ];
    //         }
    //         HtReturnDetail::insert($detailsInsert);

    //         dd("data inserted Successfully");
    //     }

    private function toArray($str)
    {
        if ($str === null || $str === '') {
            return [];
        }
        $arr = array_map('trim', explode(',', $str));
        return $arr;
    }

    public function getWarehouseStocks(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|integer',
            'item_id'     => 'required|integer',
            'uom'         => 'required|integer',
        ]);

        $customerId = $request->customer_id;
        $itemId     = $request->item_id;
        $uom        = $request->uom;

        $warehouseIds = DB::table('tbl_warehouse')
            ->where('company_customer_id', $customerId)
            ->pluck('id');

        if ($warehouseIds->isEmpty()) {
            return response()->json([
                'status'  => 200,
                'data'    => ['in_stock' => false],
                'message' => 'Customer has no warehouses'
            ]);
        }

        $allowedUoms = DB::table('item_uoms')
            ->where('item_id', $itemId)
            ->pluck('uom_id');

        if (!$allowedUoms->contains($uom)) {
            return response()->json([
                'status'  => 200,
                'data'    => ['in_stock' => false],
                'message' => 'UOM does not belong to this item'
            ]);
        }

        $itemExists = DB::table('tbl_warehouse_stocks')
            ->whereIn('warehouse_id', $warehouseIds)
            ->where('item_id', $itemId)
            ->exists();

        return response()->json([
            'status'  => 200,
            'data'    => [
                'in_stock' => $itemExists
            ],
            'message' => $itemExists
                ? "Item exists for this customer"
                : "Item does not exist for this customer"
        ]);
    }

    public function store(HtReturnStoreRequest $request): JsonResponse
    {
        try {
            $return = $this->service->storeData($request->validated());
            if ($return) {
                LogHelper::store(
                    '17',
                    '102',
                    'add',
                    null,
                    $return->getAttributes(),
                    auth()->id()
                );
            }
            return response()->json([
                'status'  => 'success',
                'code'    => 201,
                'message' => 'Return created successfully',
                'data'    => $return
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => $e->getMessage(),
                'data'    => null
            ], 500);
        }
    }
    // public function expliteData(HtReturnStoreRequest $request): JsonResponse
    // {
    //     try {
    //         $return = $this->service->storeData($request->validated());

    //         return response()->json([
    //             'status'  => 'success',
    //             'code'    => 201,
    //             'message' => 'Return created successfully',
    //             'data'    => $return
    //         ], 201);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'code'    => 500,
    //             'message' => $e->getMessage(),
    //             'data'    => null
    //         ], 500);
    //     }
    // }


    public function globalFilter(Request $request): JsonResponse
    {
        try {

            $perPage  = $request->get('limit', 50);

            $filters  = $request->except(['limit']);

            $returns = $this->service->globalFilter($perPage, $filters);

            $pagination = [
                'page'         => $returns->currentPage(),
                'limit'        => $returns->perPage(),
                'totalPages'   => $returns->lastPage(),
                'totalRecords' => $returns->total(),
            ];
            return response()->json([
                'status'     => 'success',
                'code'       => 200,
                'data'       => ReturnheaderResource::collection($returns),
                'pagination' => $pagination
            ]);
        } catch (Exception $e) {

            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve delivery orders',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // public function getByWarehouse(Request $request, $warehouse_id = null)
    // {
    //     $warehouseId = $warehouse_id ?? $request->input('warehouse_id');
        
    //     if (!$warehouseId) {
    //         return response()->json([
    //             'message' => 'warehouse_id is required'
    //         ], 400);
    //     }

    //     $perPage = $request->input('limit', 10);
    //     $returns = $this->service->getByWarehouseId($warehouseId, $perPage);

    //     $pagination = [
    //         'page'         => $returns->currentPage(),
    //         'limit'        => $returns->perPage(),
    //         'totalPages'   => $returns->lastPage(),
    //         'totalRecords' => $returns->total(),
    //     ];

    //     return response()->json([
    //         'status'     => 'success',
    //         'code'       => 200,
    //         'data'       => ReturnheaderResource::collection($returns),
    //         'pagination' => $pagination
    //     ]);
    // }
public function getByWarehouse(Request $request, $warehouse_id = null)
    {
        $warehouseId = $warehouse_id ?: $request->query('warehouse_id');
        if (!$warehouseId) {
            return response()->json([
                'status' => 'error',
                'code'   => 400,
                'message'=> 'warehouse_id is required'
            ], 400);
        }
        $perPage = $request->query('per_page', 10);
        $returns = $this->service->getByWarehouseId($warehouseId, $perPage);
        return response()->json([
            'status' => 'success',
            'code'   => 200,
            'data'   => ReturnheaderResource::collection($returns),
            'pagination' => [
                'page'         => $returns->currentPage(),
                'limit'        => $returns->perPage(),
                'totalPages'   => $returns->lastPage(),
                'totalRecords' => $returns->total(),
            ]
        ]);
    }
}
