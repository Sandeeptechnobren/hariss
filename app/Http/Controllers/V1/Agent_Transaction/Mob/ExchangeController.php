<?php

namespace App\Http\Controllers\V1\Agent_Transaction\Mob;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Agent_Transaction\Mob\StoreExchangeRequest;
use App\Http\Resources\V1\Agent_Transaction\Mob\ExchangeHeaderResource;
use App\Services\V1\Agent_Transaction\Mob\ExchangeService;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Http\Request;
use App\Models\Agent_Transaction\ExchangeHeader;


class ExchangeController extends Controller
{
    protected ExchangeService $service;

    public function __construct(ExchangeService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Post(
     *     path="/mob/master_mob/exchange/create",
     *     summary="Create Exchange Transaction",
     *     description="Creates a new exchange transaction with its related invoices and returns.",
     *     tags={"Mob Exchange"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Exchange transaction data",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 example={
     *                     "exchange_code":"EXC20261243",
     *                     "currency": "USD",
     *                     "warehouse_id": 2,
     *                     "route_id": 7,
     *                     "customer_id": 258961,
     *                     "salesman_id": 3,
     *                     "gross_total": 500.00,
     *                     "vat": 25.00,
     *                     "net_amount": 475.00,
     *                     "total": 500.00,
     *                     "discount": 0,
     *                     "status": 1,
     *                     "comment": "chdfgehjsd",
     *                      "latitude":12.0324545,
     *                      "longitude":14.021546,
     *                     "invoices": {
     *                         {
     *                             "item_id": 101,
     *                             "uom_id": 1,
     *                             "item_price": 100,
     *                             "item_quantity": 5,
     *                             "vat": 5,
     *                             "discount": 0,
     *                             "gross_total": 500,
     *                             "net_total": 475,
     *                             "total": 500,
     *                             "is_promotional": false,
     *                             "status": 1
     *                         }
     *                     },
     *                     "returns": {
     *                         {
     *                             "item_id": 202,
     *                             "uom_id": 2,
     *                             "item_price": 50,
     *                             "item_quantity": 2,
     *                             "vat": 2.5,
     *                             "discount": 0,
     *                             "gross_total": 100,
     *                             "net_total": 95,
     *                             "total": 100,
     *                             "is_promotional": false,
     *                             "status": 1,
     *                             "return_type":"2",
     *                              "reason":"2"
     *                         }
     *                     }
     *                 }
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Exchange transaction created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=201),
     *             @OA\Property(property="data", type="object", description="Created exchange transaction with invoices and returns")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Failed to create exchange transaction",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Failed to create exchange transaction"),
     *             @OA\Property(property="error", type="string", example="Validation error or exception message")
     *         )
     *     )
     * )
     */
public function store(StoreExchangeRequest $request): JsonResponse
    {
        try {
            $exchange = $this->service->create($request->validated());
            if (!$exchange) {
                return response()->json([
                    'status'  => false,
                    'code'    => 400,
                    'message' => 'Failed to create exchange transaction',
                ], 400);
            }
            return response()->json([
                'status' => true,
                'code'   => 201,
                'data'   => new ExchangeHeaderResource($exchange),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'code'    => 400,
                'message' => 'Failed to create exchange transaction',
                'error'   => $e->getMessage(),
            ], 400);
        }
    }
    /**
 * @OA\Get(
 *     path="/mob/master_mob/exchange/reason-types",
 *     tags={"Mob Exchange"},
 *     summary="Get list of reason types",
 *     description="Returns list of reason types with return information",
 *     @OA\Response(
 *         response=200,
 *         description="Successful response",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="name", type="string", example="Reason One"),
 *                     @OA\Property(property="return_id", type="integer", example=1),
 *                     @OA\Property(property="return_name", type="string", example="Good")
 *                 )
 *             )
 *         )
 *     )
 * )
 */
public function index(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data'   => $this->service->getList()
        ], 200);
    }
}
