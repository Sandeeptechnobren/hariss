<?php

namespace App\Http\Controllers\V1\Hariss_Transaction\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Hariss_Transaction\Web\POHeaderResource;
use App\Http\Resources\V1\Hariss_Transaction\Web\PODetailResource;
use App\Http\Requests\V1\Hariss_Transaction\Web\PoOrderRequest;
use App\Services\V1\Hariss_Transaction\Web\POHeaderService;
use App\Models\Hariss_Transaction\Web\PoOrderHeader;
use App\Models\Hariss_Transaction\Web\PoOrderDetail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Exports\PoOrderExport;
use App\Exports\PoOrderCollapseExport;
use Maatwebsite\Excel\Facades\Excel;
use Exception;
use Illuminate\Support\Facades\Storage;
use App\Helpers\LogHelper;
use App\Exports\ItemPoOrderCollapseExport;
use App\Helpers\DataAccessHelper;
use App\Exports\PoOrderCustomerExport;
use Carbon\Carbon;
use App\Helpers\CommonLocationFilter;


class POHeaderController extends Controller
{
    protected $service;

    public function __construct(POHeaderService $service)
    {
        $this->service = $service;
    }
    // public function exportPoOrderHeader(Request $request)
    // {
    //     $format    = strtolower($request->input('format', 'xlsx'));
    //     $extension = $format === 'csv' ? 'csv' : 'xlsx';

    //     $filename = 'Purchase_Order_' . now()->format('Ymd_His') . '.' . $extension;
    //     $path     = 'poorderexports/' . $filename;

    //     $filters = $request->input('filter', []);

    //     $fromDate = $filters['from_date'] ?? Null;
    //     $toDate   = $filters['to_date'] ?? Null;

    //     $routeIds = !empty($filters['route_id'])
    //         ? explode(',', $filters['route_id'])
    //         : [];

    //     $salesmanIds = !empty($filters['salesman_id'])
    //         ? explode(',', $filters['salesman_id'])
    //         : [];

    //     $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);

    //     $export = new PoOrderExport(
    //         $fromDate,
    //         $toDate,
    //         $warehouseIds,
    //         $routeIds,
    //         $salesmanIds
    //     );

    //     if ($format === 'csv') {
    //         Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
    //     } else {
    //         Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
    //     }

    //     $appUrl = rtrim(config('app.url'), '/');
    //     $fullUrl = $appUrl . '/storage/app/public/' . $path;

    //     return response()->json([
    //         'status'       => 'success',
    //         'download_url' => $fullUrl,
    //     ]);
    // }
public function exportPoOrderHeader(Request $request)
{
    $format    = strtolower($request->input('format', 'xlsx'));
    $extension = $format === 'csv' ? 'csv' : 'xlsx';
    $filename = 'Purchase_Order_' . now()->format('Ymd_His') . '.' . $extension;
    $path     = 'poorderexports/' . $filename;
    $filters = $request->input('filter', []);
    $user    = auth()->user();
    $filter = $filters;
    $query = PoOrderHeader::latest('id');
    // $query = DataAccessHelper::filterAgentTransaction($query, $user);
    $warehouseIds = CommonLocationFilter::resolveWarehouseIds([
        'company_id'   => $filter['company_id'] ?? null,
        'region_id'    => $filter['region_id'] ?? null,
        'area_id'      => $filter['area_id'] ?? null,
        'warehouse_id' => $filter['warehouse_id'] ?? null,
        'route_id'     => $filter['route_id'] ?? null,
        'salesman_id'     => $filter['salesman_id'] ?? null,
    ]);
    if (!empty($warehouseIds)) {
        $warehouseIds = is_array($warehouseIds)? $warehouseIds: explode(',', $warehouseIds);
        $query->whereIn('warehouse_id', $warehouseIds);
    }
    if (!empty($filter['salesman_id'])) {
        $salesmanIds = is_array($filter['salesman_id'])
            ? $filter['salesman_id']
            : explode(',', $filter['salesman_id']);
        $query->whereIn('salesman_id', $salesmanIds);
    }
    if (isset($filter['status'])) {
        $query->where('status', $filter['status']);
    }
    if (!empty($filter['from_date']) && !empty($filter['to_date'])) {
        $query->whereBetween('order_date', [
            $filter['from_date'],
            $filter['to_date']
        ]);
    } else {
        if (!empty($filter['from_date'])) {
            $query->whereDate('order_date', '>=', $filter['from_date']);
        }
        if (!empty($filter['to_date'])) {
            $query->whereDate('order_date', '<=', $filter['to_date']);
        }
    }
    $export = new PoOrderExport(
        $filter['from_date'] ?? null,
        $filter['to_date'] ?? null,
        $warehouseIds ?? [],
        !empty($filter['route_id']) ? explode(',', $filter['route_id']) : [],
        !empty($filter['salesman_id']) ? explode(',', $filter['salesman_id']) : []
    );
    PoOrderHeader::addGlobalScope('export_filter', function ($builder) use ($query) {
        $builder->whereIn('id', $query->pluck('id'));
    });
    if ($format === 'csv') {
        Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
    } else {
        Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
    }
    PoOrderHeader::withoutGlobalScope('export_filter');
    $appUrl  = rtrim(config('app.url'), '/');
    $fullUrl = $appUrl . '/storage/app/public/' . $path;
    return response()->json([
        'status'       => 'success',
        'download_url' => $fullUrl,
    ]);
}

    public function exportPoOrders(Request $request)
    {
        $uuid = $request->input('uuid');
        $format = strtolower($request->input('format', 'pdf'));
        $extension = in_array($format, ['pdf', 'csv', 'xlsx']) ? $format : 'pdf';

        $filename = 'poorder_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'exports/' . $filename;

        if ($extension === 'pdf') {
            $order = PoOrderHeader::with(['warehouse', 'customer'])
                ->where('uuid', $uuid)
                ->firstOrFail();
            $orderDetails = PoOrderDetail::with(['item', 'uom'])
                ->where('header_id', $order->id)
                ->get();

            $order->excise = $orderDetails->sum('excise');
            $order->vat   = $orderDetails->sum('vat');
            $order->net   = $orderDetails->sum('net');
            $order->total = $orderDetails->sum('total');

            $pdf = \PDF::loadView('purchaseorder', [
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
    /**
     * @OA\Get(
     *     path="/api/hariss_transaction/po_orders/list",
     *     tags={"PO Orders"},
     *     summary="Get paginated list of PO Orders with filters",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="customer_id", in="query", description="Filter by customer ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="salesman_id", in="query", description="Filter by salesman ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="order_code", in="query", description="Search by order code", @OA\Schema(type="string")),
     *     @OA\Parameter(name="from_date", in="query", description="Filter from date", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to_date", in="query", description="Filter to date", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="status", in="query", description="Filter by status", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="limit", in="query", description="Items per page", @OA\Schema(type="integer", default=20)),
     *     @OA\Parameter(name="dropdown", in="query", description="Return dropdown format", @OA\Schema(type="boolean")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of PO Orders",
     *         @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="code", type="integer", example=200),
     *              @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *              @OA\Property(property="pagination", type="object")
     *          )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('limit', 50);
            $dropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);

            $filters = $request->except(['limit', 'dropdown']);

            $orders = $this->service->getAll($perPage, $filters, $dropdown);

            if ($dropdown) {
                return response()->json([
                    'status' => 'success',
                    'code'   => 200,
                    'data'   => $orders
                ]);
            }

            $pagination = [
                'page'         => $orders->currentPage(),
                'limit'        => $orders->perPage(),
                'totalPages'   => $orders->lastPage(),
                'totalRecords' => $orders->total(),
            ];

            return response()->json([
                'status'     => 'success',
                'code'       => 200,
                'data'       => POHeaderResource::collection($orders),
                'pagination' => $pagination
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve PO orders',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/hariss_transaction/po_orders/{uuid}",
     *     tags={"PO Orders"},
     *     summary="Get PO Order by UUID",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="PO Order UUID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="PO Order details",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="PO Order not found")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $order = $this->service->getByUuid($uuid);

            if (!$order) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 404,
                    'message' => 'PO Order not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'code'   => 200,
                'data'   => new POHeaderResource($order)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve PO order',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    public function store(PoOrderRequest $request)
    {
        $order = $this->service->createOrder($request->validated());
        if ($order) {
            LogHelper::store(
                '17',
                '92',
                'add',
                null,
                $order->getAttributes(),
                auth()->id()
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'PO Order created successfully',
            'data' => $order,
        ], 201);
    }

    public function exportPoOrderCollapse(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filters = $request->input('filter', []);
        $fromDate = $filters['from_date'] ?? Null;
        $toDate   = $filters['to_date'] ?? Null;
        $customerId = $filters['customer_id'] ?? null;
        $salesmanIds = CommonLocationFilter::normalizeIds($filters['salesman_id'] ?? []);
        $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);
        $filename = 'Purchase_Order_Detail_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'poorderexports/' . $filename;
        $export = new PoOrderCollapseExport(
            $fromDate,
            $toDate,
            $customerId,
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

    // public function exportPoOrderCollapse(Request $request)
    // {
    //     $format = strtolower($request->input('format', 'xlsx'));
    //     $extension = $format === 'csv' ? 'csv' : 'xlsx';

    //     $filters = $request->input('filter', []);

    //     $fromDate = $filters['from_date'] ?? Null;
    //     $toDate   = $filters['to_date'] ?? Null;

    //     $customerId = $filters['customer_id'] ?? null;

    //     $salesmanIds = CommonLocationFilter::normalizeIds($filters['salesman_id'] ?? []);

    //     $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);

    //     $filename = 'Purchase_Order_Detail_' . now()->format('Ymd_His') . '.' . $extension;
    //     $path = 'poorderexports/' . $filename;

    //     $export = new PoOrderCollapseExport(
    //         $fromDate,
    //         $toDate,
    //         $customerId,
    //         $warehouseIds,
    //         $salesmanIds
    //     );

    //     $writerType = $format === 'csv'
    //         ? \Maatwebsite\Excel\Excel::CSV
    //         : \Maatwebsite\Excel\Excel::XLSX;

    //     // queued export (non-blocking)
    //     Excel::queue($export, $path, 'public', $writerType);

    //     return response()->json([
    //         'status' => 'success',
    //         'download_url' => rtrim(config('app.url'), '/') . '/storage/app/public/' . $path,
    //     ]);
    // }

    public function exportItembsPoOrderCollapse(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';

        $itemId     = $request->input('item_id');
        $customerId = $request->input('customer_id');

        $fromDate = $request->input('from_date')
            ? \Carbon\Carbon::parse($request->from_date)->startOfDay()
            : \Carbon\Carbon::now()->startOfMonth();

        $toDate = $request->input('to_date')
            ? \Carbon\Carbon::parse($request->to_date)->endOfDay()
            : \Carbon\Carbon::now()->endOfMonth();

        $filename = 'po_order_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'poorderexports/' . $filename;

        $export = new ItemPoOrderCollapseExport(
            $fromDate,
            $toDate,
            $customerId,
            $itemId
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

    public function exportCustomerBasedPoOrder(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';

        $fromDate    = $request->input('from_date');
        $toDate      = $request->input('to_date');
        $customerId  = $request->input('customer_id');

        $warehouseIds = $request->input('warehouse_ids', []);
        $salesmanIds  = $request->input('salesman_ids', []);

        $filename = 'customer_po_order_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'poorderexports/' . $filename;

        $export = new PoOrderCustomerExport(
            $customerId,
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

    // public function listByCustomer(Request $request)
    // {
    //     $request->validate([
    //         'customer_id' => 'required|integer',
    //         'limit'       => 'nullable|integer|min:1|max:100',
    //     ]);

    //     $limit = $request->input('limit', 10);

    //     $orders = PoOrderHeader::with([
    //             'customer',
    //             'details',             
    //             'details.item',       
    //             'details.uom',       
    //         ])
    //         ->where('customer_id', $request->customer_id)
    //         ->where('status', 1)
    //         ->orderBy('id', 'desc')
    //         ->paginate($limit);

    //     $pagination = [
    //         'page'         => $orders->currentPage(),
    //         'limit'        => $orders->perPage(),
    //         'totalPages'   => $orders->lastPage(),
    //         'totalRecords' => $orders->total(),
    //     ];

    //     return response()->json([
    //         'status'     => 'success',
    //         'code'       => 200,
    //         'data'       => POHeaderResource::collection($orders),
    //         'pagination' => $pagination,
    //     ]);
    // }

    public function listByCustomer(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|integer',
            'limit'       => 'nullable|integer|min:1|max:100',
            'from_date'   => 'nullable|date_format:Y-m-d',
            'to_date'     => 'nullable|date_format:Y-m-d',
        ]);

        $limit    = $request->input('limit', 10);
        $fromDate = $request->input('from_date');
        $toDate   = $request->input('to_date');

        $orders = PoOrderHeader::with([
            'customer',
            'details',
            'details.item',
            'details.uom',
        ])
            ->where('customer_id', $request->customer_id)
            ->where('status', 1)

            // ✅ DATE FILTER FIXED
            ->when($fromDate && $toDate, function ($q) use ($fromDate, $toDate) {
                $q->whereDate('created_at', '<=', $fromDate)
                    ->whereDate('created_at', '>=', $toDate);
            })

            ->when($fromDate && !$toDate, function ($q) use ($fromDate) {
                $q->whereDate('created_at', '>=', $fromDate);
            })
            ->when(!$fromDate && $toDate, function ($q) use ($toDate) {
                $q->whereDate('created_at', '<=', $toDate);
            })
            ->when(!$fromDate && !$toDate, function ($q) {
                $q->whereBetween('created_at', [
                    now()->startOfMonth()->startOfDay(),
                    now()->endOfMonth()->endOfDay()
                ]);
            })

            ->orderBy('id', 'desc')
            ->paginate($limit);
        $pagination = [
            'page'         => $orders->currentPage(),
            'limit'        => $orders->perPage(),
            'totalPages'   => $orders->lastPage(),
            'totalRecords' => $orders->total(),
        ];

        return response()->json([
            'status'     => 'success',
            'code'       => 200,
            'data'       => POHeaderResource::collection($orders),
            'pagination' => $pagination,
        ]);
    }

    public function getByItem(Request $request)
    {
        $request->validate([
            'item_id'  => 'required|integer',
            'from_date'=> 'nullable|date',
            'to_date'  => 'nullable|date',
        ]);

        $limit  = $request->input('limit', 10);
        $itemId = $request->item_id;

        // ✅ Proper date handling
        $fromDate = $request->input('from_date')
            ? Carbon::parse($request->from_date)->startOfDay()
            : Carbon::now()->startOfMonth();

        $toDate = $request->input('to_date')
            ? Carbon::parse($request->to_date)->endOfDay()
            : Carbon::now()->endOfMonth();

        // ✅ MAIN FIX: filter on details.created_at
        $headers = PoOrderHeader::whereHas('details', function ($q) use ($itemId, $fromDate, $toDate) {
                $q->where('item_id', $itemId)
                ->whereBetween('created_at', [$fromDate, $toDate]);
            })
            ->with([
                'details' => function ($q) use ($itemId, $fromDate, $toDate) {
                    $q->where('item_id', $itemId)
                    ->whereBetween('created_at', [$fromDate, $toDate]);
                }
            ])
            ->paginate($limit);

        return response()->json([
            'status' => 'success',
            'code'   => 200,
            'data'   => POHeaderResource::collection($headers),
            'meta'   => [
                'page'         => $headers->currentPage(),
                'limit'        => $headers->perPage(),
                'totalPages'   => $headers->lastPage(),
                'totalRecords' => $headers->total(),
            ],
        ]);
    }

    public function globalFilter(Request $request): JsonResponse
    {
        try {
            $perPage  = $request->get('limit', 50);
            $dropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);

            $filters = $request->except(['limit', 'dropdown']);

            $orders = $this->service->globalFilter($perPage, $filters);

            if ($dropdown) {
                return response()->json([
                    'status' => 'success',
                    'code'   => 200,
                    'data'   => $orders
                ]);
            }

            $pagination = [
                'page'         => $orders->currentPage(),
                'limit'        => $orders->perPage(),
                'totalPages'   => $orders->lastPage(),
                'totalRecords' => $orders->total(),
            ];

            return response()->json([
                'status'     => 'success',
                'code'       => 200,
                'data'       => POHeaderResource::collection($orders),
                'pagination' => $pagination
            ]);
        } catch (Exception $e) {

            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve PO orders',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
