<?php
namespace App\Http\Controllers\V1\Agent_Transaction\Mob;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Agent_Transaction\Mob\StoreOrderRequest;
use App\Http\Requests\V1\Agent_Transaction\Mob\UpdateOrderRequest;
use App\Http\Resources\V1\Agent_Transaction\Mob\OrderHeaderResource;
use App\Services\V1\Agent_Transaction\Mob\OrderService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use App\Models\Agent_Transaction\OrderHeader;
use App\Models\Agent_Transaction\OrderDetail;

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
     *     path="/mob/master_mob/orders/list",
     *     tags={"Mob Orders"},
     *     summary="Get paginated list of orders with filters",
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
            if ($dropdown){
                return response()->json([   
                    'status' => 'success',
                    'code' => 200,
                    'data' => $orders,
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

    /**
     * @OA\Get(
     *     path="/mob/master_mob/orders/{uuid}",
     *     tags={"Mob Orders"},
     *     summary="Get single order by UUID",
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
     *     path="/mob/master_mob/orders/add",
     *     tags={"Mob Orders"},
     *     summary="Create a new order",
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
            
            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'Failed to create order'
                ], 400);
            }
            
            return response()->json([
                'status' => true,
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
     *     path="/mob/master_mob/orders/update/{uuid}",
     *     tags={"Mob Orders"},
     *     summary="Update an existing order",
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
}