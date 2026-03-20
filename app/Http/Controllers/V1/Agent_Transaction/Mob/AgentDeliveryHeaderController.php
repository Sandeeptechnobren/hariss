<?php

namespace App\Http\Controllers\V1\Agent_Transaction\Mob;

use App\Exports\AgentDeliveryExport;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Agent_Transaction\Mob\AgentDeliveryHeaderRequest;
use App\Http\Requests\V1\Agent_Transaction\Mob\AgentDeliveryHeaderUpdateRequest;
use App\Http\Resources\V1\Agent_Transaction\Mob\AgentDeliveryHeaderResource;
use App\Models\Agent_Transaction\AgentDeliveryHeaders;
use App\Services\V1\Agent_Transaction\Mob\AgentDeliveryHeaderService;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


/**
 * @OA\Tag(
 *     name="Agent Delivery Header",
 *     description="API endpoints for managing Delivery Headers and their Details"
 * )
 */
class AgentDeliveryHeaderController extends Controller
{
    public function __construct(protected AgentDeliveryHeaderService $service) {}

    // /**
    //  * @OA\Get(
    //  *     path="/api/agent_transaction/agent-delivery/list",
    //  *     tags={"Agent Delivery Header"},
    //  *     security={{"bearerAuth":{}}},
    //  *     summary="List all agent deliveries",
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="List of all agent deliveries"
    //  *     )
    //  * )
    //  */
    // public function index(): JsonResponse
    // {
    //     try {
    //         $headers = $this->service->all();
    //         return ResponseHelper::paginatedResponse(
    //             'Deliveries fetched successfully',
    //             AgentDeliveryHeaderResource::class,
    //             $headers
    //         );
    //     } catch (Exception $e) {
    //         return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    //     }
    // }

    /**
     * @OA\Get(
     *     path="/mob/master_mob/agent-delivery/list",
     *     tags={"Mob Delivery Header"},
     *     security={{"bearerAuth":{}}},
     *     summary="List all agent deliveries",
     *     @OA\Response(
     *         response=200,
     *         description="List of all agent deliveries"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 50);
            $headers = $this->service->all($perPage);
            return ResponseHelper::paginatedResponse(
                'Deliveries fetched successfully',
                AgentDeliveryHeaderResource::class,
                $headers
            );
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * @OA\Post(
     *     path="/mob/master_mob/agent-delivery/add",
     *     tags={"Mob Delivery Header"},
     *     security={{"bearerAuth":{}}},
     *     summary="Create a new delivery with details",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"warehouse_id","route_id","salesman_id","details"},
     *             @OA\Property(property="warehouse_id", type="integer", example=101),
     *             @OA\Property(property="route_id", type="integer", example=22),
     *             @OA\Property(property="salesman_id", type="integer", example=45),
     *             @OA\Property(property="customer_id", type="integer", example=89),
     *             @OA\Property(property="country_id", type="integer", example=1),
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="gross_total", type="number", format="float", example=1000.00),
     *             @OA\Property(property="discount", type="number", format="float", example=50.00),
     *             @OA\Property(property="vat", type="number", format="float", example=150.00),
     *             @OA\Property(property="total", type="number", format="float", example=1100.00),
     *             @OA\Property(property="comment", type="string", example="Delivered on schedule"),
     *             @OA\Property(
     *                 property="details",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"item_id","uom_id","quantity","item_price"},
     *                     @OA\Property(property="item_id", type="integer", example=77),
     *                     @OA\Property(property="uom_id", type="integer", example=5),
     *                     @OA\Property(property="quantity", type="integer", example=10),
     *                     @OA\Property(property="item_price", type="number", example=250.5),
     *                     @OA\Property(property="vat", type="number", example=25.5),
     *                     @OA\Property(property="discount", type="number", example=10.0),
     *                     @OA\Property(property="gross_total", type="number", example=2555.0),
     *                     @OA\Property(property="net_total", type="number", example=2300.0),
     *                     @OA\Property(property="total", type="number", example=2400.0)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Delivery created successfully")
     * )
     */
    public function store(AgentDeliveryHeaderRequest $request): JsonResponse
    {
        try {
            $header = $this->service->store($request->validated());
            return response()->json([
                'status' => 'success',
                'message' => 'Delivery created successfully',
                'data' => $header
            ], 201);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/mob/master_mob/agent-delivery/{uuid}",
     *     tags={"Mob Delivery Header"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get a specific delivery by UUID",
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="UUID of the delivery",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Delivery fetched successfully")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $current  = $this->service->findByUuid($uuid);

            return response()->json([
                'status'  => 'success',
                'message' => 'Delivery fetched successfully',
                'data'    => new AgentDeliveryHeaderResource($current),
            ], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/mob/master_mob/agent-delivery/update/{uuid}",
     *     tags={"Mob Delivery Header"},
     *     security={{"bearerAuth":{}}},
     *     summary="Update delivery header and details by UUID",
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="UUID of the delivery to update",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="warehouse_id", type="integer", example=112),
     *             @OA\Property(property="route_id", type="integer", example=33),
     *             @OA\Property(property="salesman_id", type="integer", example=44),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(property="comment", type="string", example="Updated after delivery check"),
     *             @OA\Property(
     *                 property="details",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="item_id", type="integer", example=77),
     *                     @OA\Property(property="uom_id", type="integer", example=12),
     *                     @OA\Property(property="quantity", type="integer", example=25),
     *                     @OA\Property(property="item_price", type="number", example=120.75)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Delivery updated successfully")
     * )
     */
    public function update(AgentDeliveryHeaderUpdateRequest $request, string $uuid): JsonResponse
    {
        try {
            $header = $this->service->updateByUuid($uuid, $request->validated());
            return response()->json([
                'status' => 'success',
                'message' => 'Delivery updated successfully',
                'data' => new AgentDeliveryHeaderResource($header)
            ], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}