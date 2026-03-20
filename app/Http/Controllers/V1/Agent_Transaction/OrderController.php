<?php

namespace App\Http\Controllers\V1\Agent_Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Agent_Transaction\StoreOrderRequest;
use App\Http\Requests\V1\Agent_Transaction\UpdateOrderRequest;
use App\Http\Resources\V1\Agent_Transaction\OrderHeaderResource;
use App\Services\V1\Agent_Transaction\OrderService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use App\Models\Agent_Transaction\OrderHeader;
use App\Models\Agent_Transaction\OrderDetail;
use App\Exports\OrderHeaderFullExport;
use App\Exports\InvoiceItemExport;
use App\Exports\OrderHeaderDetailExport;
use App\Exports\OrderCollapseExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\LogHelper;
use App\Helpers\CommonLocationFilter;

class OrderController extends Controller
{
    use ApiResponse;

    protected OrderService $service;

    public function __construct(OrderService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/agent_transaction/orders/list",
     *     tags={"Order"},
     *     summary="Get paginated list of orders with filters",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="warehouse_id", in="query", description="Filter by warehouse UUID", @OA\Schema(type="string", format="uuid")),
     *     @OA\Parameter(name="customer_id", in="query", description="Filter by customer UUID", @OA\Schema(type="string", format="uuid")),
     *     @OA\Parameter(name="salesman_id", in="query", description="Filter by salesman UUID", @OA\Schema(type="string", format="uuid")),
     *     @OA\Parameter(name="order_number", in="query", description="Search by order number", @OA\Schema(type="string")),
     *     @OA\Parameter(name="from_date", in="query", description="Filter from date", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to_date", in="query", description="Filter to date", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="country", in="query", description="Filter by country", @OA\Schema(type="string")),
     *     @OA\Parameter(name="limit", in="query", description="Items per page", @OA\Schema(type="integer", default=50)),
     *     @OA\Parameter(name="dropdown", in="query", description="Return as dropdown format", @OA\Schema(type="boolean", default=false)),
     *     @OA\Response(
     *         response=200,
     *         description="List of orders",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="page", type="integer"),
     *                 @OA\Property(property="limit", type="integer"),
     *                 @OA\Property(property="totalPages", type="integer"),
     *                 @OA\Property(property="totalRecords", type="integer")
     *             )
     *         )
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
                    'code' => 200,
                    'data' => OrderHeaderResource::collection($orders),
                ]);
            }
            $pagination = [
                'page' => $orders->currentPage(),
                'limit' => $orders->perPage(),
                'totalPages' => $orders->lastPage(),
                'totalRecords' => $orders->total(),
            ];
            return $this->success(
                OrderHeaderResource::collection($orders),
                'Orders fetched successfully',
                200,
                $pagination
            );
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to retrieve orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function globalFilter(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('limit', 50);
            $filters = $request->except(['limit']);
            $orders = $this->service->globalFilter($perPage, $filters);
            $pagination = [
                'page'          => $orders->currentPage(),
                'limit'         => $orders->perPage(),
                'totalPages'    => $orders->lastPage(),
                'totalRecords'  => $orders->total(),
            ];
            return $this->success(
                OrderHeaderResource::collection($orders),
                'Orders fetched successfully',
                200,
                $pagination
            );
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve orders',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/agent_transaction/orders/{uuid}",
     *     tags={"Order"},
     *     summary="Get single order by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, description="Order UUID", @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(
     *         response=200,
     *         description="Order details",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Order not found")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $order = $this->service->getByUuid($uuid);
            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Order not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'data' => new OrderHeaderResource($order)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to retrieve order',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * @OA\Post(
     *     path="/api/agent_transaction/orders/add",
     *     tags={"Order"},
     *     summary="Create a new order",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"currency", "country_id", "order_code", "warehouse_id", "customer_id", "salesman_id", "details"},
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="country_id", type="number"),
     *             @OA\Property(property="order_code", type="string", example="ORD-2025-001"),
     *             @OA\Property(property="warehouse_id", type="number"),
     *             @OA\Property(property="route_id", type="number", nullable=true),
     *             @OA\Property(property="customer_id", type="number"),
     *             @OA\Property(property="salesman_id", type="number"),
     *             @OA\Property(property="delivery_date", type="string", format="date", example="2025-11-01"),
     *             @OA\Property(property="gross_total", type="number", format="double", example=1000.00),
     *             @OA\Property(property="vat", type="number", format="double", example=50.00),
     *             @OA\Property(property="net_amount", type="number", format="double", example=950.00),
     *             @OA\Property(property="total", type="number", format="double", example=1050.00),
     *             @OA\Property(property="discount", type="number", format="double", example=50.00),
     *             @OA\Property(
     *                 property="details",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"item_id", "item_price", "quantity", "uom_id"},
     *                     @OA\Property(property="item_id", type="number"),
     *                     @OA\Property(property="item_price", type="number", format="double", example=100.00),
     *                     @OA\Property(property="quantity", type="number", format="double", example=10),
     *                     @OA\Property(property="vat", type="number", format="double", example=5.00),
     *                     @OA\Property(property="uom_id", type="number"),
     *                     @OA\Property(property="discount", type="number", format="double", example=0),
     *                     @OA\Property(property="discount_id", type="number", nullable=true),
     *                     @OA\Property(property="gross_total", type="number", format="double", example=1000.00),
     *                     @OA\Property(property="net_total", type="number", format="double", example=950.00),
     *                     @OA\Property(property="total", type="number", format="double", example=1000.00),
     *                     @OA\Property(property="is_promotional", type="boolean", example=false),
     *                     @OA\Property(property="parent_id", type="number", nullable=true),
     *                     @OA\Property(property="promotion_id", type="number",nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Order created successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->service->create($request->validated());
            if ($order) {
                LogHelper::store(
                    '13',
                    '35',
                    'add',
                    null,
                    $order->getAttributes(),
                    auth()->id()
                );
            }
            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'Failed to create order'
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'code' => 201,
                'data' => new OrderHeaderResource($order)
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/agent_transaction/orders/update/{uuid}",
     *     tags={"Order"},
     *     summary="Update an existing order",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, description="Order UUID", @OA\Schema(type="string", format="uuid")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="country_id", type="integer", format="id"),
     *             @OA\Property(property="order_code", type="integer", example="ORD-2025-001"),
     *             @OA\Property(property="warehouse_id", type="integer", format="id"),
     *             @OA\Property(property="route_id", type="integer", format="id", nullable=true),
     *             @OA\Property(property="customer_id", type="integer", format="id"),
     *             @OA\Property(property="salesman_id", type="integer", format="id"),
     *             @OA\Property(property="delivery_date", type="integer", format="date"),
     *             @OA\Property(property="gross_total", type="number", format="double"),
     *             @OA\Property(property="vat", type="number", format="double"),
     *             @OA\Property(property="net_amount", type="number", format="double"),
     *             @OA\Property(property="total", type="number", format="double"),
     *             @OA\Property(property="discount", type="number", format="double"),
     *             @OA\Property(
     *                 property="details",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"item_id", "item_price", "quantity", "uom_id"},
     *                     @OA\Property(property="id", type="integer", format="id", nullable=true, description="Include ID to update existing detail"),
     *                     @OA\Property(property="item_id", type="integer", format="id"),
     *                     @OA\Property(property="item_price", type="number", format="double"),
     *                     @OA\Property(property="quantity", type="number", format="double"),
     *                     @OA\Property(property="vat", type="number", format="double"),
     *                     @OA\Property(property="uom_id", type="integer", format="id"),
     *                     @OA\Property(property="discount", type="number", format="double"),
     *                     @OA\Property(property="discount_id", type="integer", format="id", nullable=true),
     *                     @OA\Property(property="gross_total", type="number", format="double"),
     *                     @OA\Property(property="net_total", type="number", format="double"),
     *                     @OA\Property(property="total", type="number", format="double"),
     *                     @OA\Property(property="is_promotional", type="boolean"),
     *                     @OA\Property(property="parent_id", type="integer", format="id", nullable=true),
     *                     @OA\Property(property="promotion_id", type="integer", format="id", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Order updated successfully"),
     *     @OA\Response(response=404, description="Order not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateOrderRequest $request, string $uuid): JsonResponse
    {
        try {
            $updated = $this->service->update($uuid, $request->validated());

            if (!$updated) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Order not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'data' => new OrderHeaderResource($updated)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Failed to update order',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/agent_transaction/orders/{uuid}",
     *     tags={"Order"},
     *     summary="Soft delete an order",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, description="Order UUID", @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Order deleted successfully"),
     *     @OA\Response(response=404, description="Order not found")
     * )
     */


    /**
     * @OA\Get(
     *     path="/api/agent_transaction/orders/statistics",
     *     tags={"Order"},
     *     summary="Get order statistics",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="warehouse_id", in="query", description="Filter by warehouse UUID", @OA\Schema(type="string", format="uuid")),
     *     @OA\Parameter(name="from_date", in="query", description="Filter from date", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to_date", in="query", description="Filter to date", @OA\Schema(type="string", format="date")),
     *     @OA\Response(
     *         response=200,
     *         description="Order statistics",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_orders", type="integer"),
     *                 @OA\Property(property="total_amount", type="number", format="double"),
     *                 @OA\Property(property="total_vat", type="number", format="double"),
     *                 @OA\Property(property="total_discount", type="number", format="double"),
     *                 @OA\Property(property="average_order_value", type="number", format="double")
     *             )
     *         )
     *     )
     * )
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['warehouse_id', 'from_date', 'to_date']);
            $stats = $this->service->getStatistics($filters);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/agent_transaction/orders/update-status",
     *     summary="Update status for multiple orders",
     *     description="Updates the status of multiple orders by their UUIDs.",
     *     operationId="updateMultipleOrderStatus",
     *     tags={"Order"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order_ids", "status"},
     *             @OA\Property(
     *                 property="order_ids",
     *                 type="array",
     *                 @OA\Items(type="string", format="uuid"),
     *                 example={"9d445a1c-538e-4461-a89c-d01d93f6043b", "9d445a1c-538e-4461-a89c-d01d93f6043c"}
     *             ),
     *             @OA\Property(
     *                 property="status",
     *                 type="integer",
     *                 example=1
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order statuses updated.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order statuses updated.")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Update failed")
     * )
     */
    public function updateMultipleOrderStatus(Request $request): JsonResponse
    {
        $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'uuid|exists:agent_order_headers,uuid',
            'status' => 'required|integer',
        ]);

        $orderIds = $request->input('order_ids');
        $status = $request->input('status');

        $result = $this->service->updateOrdersStatus($orderIds, $status);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Order statuses updated.'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Update failed.'
        ], 500);
    }
    public function exportOrderHeader(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = 'Agent_order_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'orderexports/' . $filename;

        // 🔥 GET FULL FILTER OBJECT
        $filters = $request->input('filter', []);

        $fromDate = $filters['from_date'] ?? null;
        $toDate   = $filters['to_date'] ?? null;

        // Route & Salesman (array safe)
        // $routeIds    = $filters['route_id'] ?? [];
        // $salesmanIds = $filters['salesman_id'] ?? [];
        $routeIds    = CommonLocationFilter::normalizeIds($filters['route_id'] ?? []);
        $salesmanIds = CommonLocationFilter::normalizeIds($filters['salesman_id'] ?? []);
        // 🔥 SAME LOCATION HELPER
        $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);

        $export = new OrderHeaderFullExport(
            $fromDate,
            $toDate,
            $warehouseIds,
            $routeIds,
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
            'status' => 'success',
            'download_url' => $fullUrl,
        ]);
    }

    public function exportOrders(Request $request)
    {
        $uuid = $request->input('uuid');
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : ($format === 'pdf' ? 'pdf' : 'xlsx');
        $orderHerader = OrderHeader::select('order_code')
            ->where('uuid', $uuid)
            ->first();

        $code = $orderHerader?->order_code ?? 'UNKNOWN';
        $code = preg_replace('/[^A-Za-z0-9\-]/', '', $code);

        $filename = 'Agent_order_' . $code . '.' . $extension;
        $path = 'exports/' . $filename;
        // $filename = 'order_export_' . now()->format('Ymd_His') . '.' . $extension;
        // $path = 'exports/' . $filename;
        if ($format === 'pdf') {
            $order = OrderHeader::with(['warehouse', 'customer'])->where('uuid', $uuid)->firstOrFail();
            $orderDetails = OrderDetail::with(['item', 'uom'])->where('header_id', $order->id)->get();
            // dd($order);
            $pdf = \PDF::loadView('order', [
                'order' => $order,
                'orderDetails' => $orderDetails
            ]);
            \Storage::disk('public')->put($path, $pdf->output());
            $appUrl = rtrim(config('app.url'), '/');
            $fullUrl = $appUrl . '/storage/app/public/' . $path;
            return response()->json([
                'status' => 'success',
                'download_url' => $fullUrl
            ]);
        }
        $export = new OrderHeaderDetailExport($uuid);
        if ($format === 'csv') {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
        } else {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
        }
        $appUrl = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $path;
        return response()->json([
            'status' => 'success',
            'download_url' => $fullUrl,
        ]);
    }


    public function exportCollapseOrders(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = 'Agent_order_detail_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'orderexports/' . $filename;

        $filters = $request->input('filter', []);

        $fromDate = $filters['from_date'] ?? null;
        $toDate   = $filters['to_date'] ?? null;

        $routeIds    = CommonLocationFilter::normalizeIds($filters['route_id'] ?? []);
        $salesmanIds = CommonLocationFilter::normalizeIds($filters['salesman_id'] ?? []);

        // 🔥 SAME LOCATION HELPER (:contentReference[oaicite:0]{index=0})
        $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);

        $export = new OrderCollapseExport(
            $fromDate,
            $toDate,
            $warehouseIds,
            $routeIds,
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
            'status' => 'success',
            'download_url' => $fullUrl,
        ]);
    }
    public function exportInvoiceByItem(Request $request)
    {
        $itemId = $request->input('item_id');
        $format = strtolower($request->input('format', 'xlsx'));

        if (!$itemId) {
            return response()->json([
                'status' => 'error',
                'message' => 'The item_id field is required.'
            ], 400);
        }
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = 'invoice_item_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'invoiceitemexports/' . $filename;

        $export = new InvoiceItemExport($itemId);
        if ($format === 'csv') {
            Excel::store($export, $path, \Maatwebsite\Excel\Excel::CSV);
        } else {
            Excel::store($export, $path, \Maatwebsite\Excel\Excel::XLSX);
        }

        $appUrl = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json([
            'status'       => 'success',
            'code'         => 200,
            'message'      => 'Invoice export generated successfully.',
            'download_url' => $fullUrl,
        ]);
    }
}
