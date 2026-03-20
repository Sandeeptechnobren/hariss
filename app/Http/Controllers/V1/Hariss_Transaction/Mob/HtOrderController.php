<?php

namespace App\Http\Controllers\V1\Hariss_Transaction\Mob;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Hariss_Transaction\Web\POHeaderResource;
use App\Http\Resources\V1\Hariss_Transaction\Web\PODetailResource;
use App\Http\Requests\V1\Hariss_Transaction\Mob\HtOrderRequest;
use App\Services\V1\Hariss_Transaction\Mob\HtOrderService;
use App\Models\Hariss_Transaction\Web\PoOrderHeader;
use App\Models\Hariss_Transaction\Web\PoOrderDetail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use App\Helpers\LogHelper;


class HtOrderController extends Controller
{
    protected $service;

    public function __construct(HtOrderService $service)
    {
        $this->service = $service;
    }
    /**
 * @OA\Post(
 *     path="/mob/merchendisher_mob/order/create",
 *     summary="Create PO Order",
 *     description="Create a new Purchase Order with header and details",
 *     operationId="createPOOrder",
 *     tags={"PO Orders mob"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"sap_msg","customer_id","details"},
 *
 *             @OA\Property(property="order_code", type="string", example="ORD-1001"),
 *             @OA\Property(property="sap_id", type="string", example="SAP-7788"),
 *             @OA\Property(property="sap_msg", type="string", example="Order synced from mobile"),
 *             @OA\Property(property="customer_id", type="integer", example=12),
 *             @OA\Property(property="delivery_date", type="string", format="date", example="2026-03-10"),
 *             @OA\Property(property="comment", type="string", example="Urgent delivery"),
 *             @OA\Property(property="status", type="integer", example=1),
 *             @OA\Property(property="currency", type="string", example="USD"),
 *             @OA\Property(property="country_id", type="integer", example=1),
 *             @OA\Property(property="salesman_id", type="integer", example=5),
 *             @OA\Property(property="warehouse_id", type="integer", example=3),
 *
 *             @OA\Property(property="gross_total", type="number", format="float", example=1000),
 *             @OA\Property(property="discount", type="number", format="float", example=50),
 *             @OA\Property(property="pre_vat", type="number", format="float", example=950),
 *             @OA\Property(property="vat", type="number", format="float", example=95),
 *             @OA\Property(property="excise", type="number", format="float", example=20),
 *             @OA\Property(property="net", type="number", format="float", example=1020),
 *             @OA\Property(property="total", type="number", format="float", example=1020),
 *
 *             @OA\Property(property="order_flag", type="integer", example=1),
 *             @OA\Property(property="log_file", type="string", example="order_log.txt"),
 *             @OA\Property(property="doc_type", type="string", example="PO"),
 *             @OA\Property(property="order_date", type="string", format="date", example="2026-03-10"),
 *
 *             @OA\Property(
 *                 property="details",
 *                 type="array",
 *                 @OA\Items(
 *                     required={"item_id","uom_id","quantity"},
 *
 *                     @OA\Property(property="item_id", type="integer", example=101),
 *                     @OA\Property(property="uom_id", type="integer", example=1),
 *                     @OA\Property(property="discount_id", type="integer", example=2),
 *                     @OA\Property(property="promotion_id", type="integer", example=3),
 *
 *                     @OA\Property(property="item_price", type="number", format="float", example=100),
 *                     @OA\Property(property="quantity", type="integer", example=5),
 *                     @OA\Property(property="discount", type="number", format="float", example=10),
 *                     @OA\Property(property="gross_total", type="number", format="float", example=500),
 *
 *                     @OA\Property(property="promotion", type="boolean", example=false),
 *                     @OA\Property(property="net", type="number", format="float", example=450),
 *                     @OA\Property(property="excise", type="number", format="float", example=5),
 *                     @OA\Property(property="pre_vat", type="number", format="float", example=445),
 *                     @OA\Property(property="vat", type="number", format="float", example=44.5),
 *                     @OA\Property(property="total", type="number", format="float", example=489.5)
 *                 )
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="PO Order created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="PO Order created successfully"),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=422,
 *         description="Validation error"
 *     ),
 *
 *     @OA\Response(
 *         response=500,
 *         description="Server error"
 *     )
 * )
 */
    public function store(HtOrderRequest $request)
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
            'status' => true,
            'message' => 'PO Order created successfully',
            'data' => $order,
        ], 201);
    }


}