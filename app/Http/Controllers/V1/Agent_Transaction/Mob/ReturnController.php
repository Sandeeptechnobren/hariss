<?php

namespace App\Http\Controllers\V1\Agent_Transaction\Mob;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Agent_Transaction\StoreReturnRequest;
use App\Http\Requests\V1\Agent_Transaction\UpdateReturnRequest;
use App\Http\Resources\V1\Agent_Transaction\ReturnHeaderResource;
use App\Services\V1\Agent_Transaction\Mob\ReturnService;
use Illuminate\Http\JsonResponse;
use Exception;
use App\Models\Agent_Transaction\ReturnType;
use App\Models\Agent_Transaction\ReturnHeader;
use App\Models\Agent_Transaction\ResonType;
use App\Models\AgentCustomer;
use App\Exports\ReturnHeaderExport;
use App\Exports\ReturnHeaderDetailExport;
use App\Exports\ReturnAgentCustomerExport;
use App\Exports\ReturnCollapseExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    protected ReturnService $service;

    public function __construct(ReturnService $service)
    {
        $this->service = $service;
    }


    /**
     * @OA\Post(
     *     path="/mob/master_mob/returns/create",
     *     tags={"Mob Agent Returns"},
     *     summary="Create a new return transaction",
     *     description="Creates a return header and its details",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"currency","country_id","warehouse_id","route_id","customer_id","details"},
     *             @OA\Property(property="osa_code", type="string", example="RET-2025-0001"),
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="country_id", type="integer", example=1),
     *             @OA\Property(property="order_id", type="integer", nullable=true, example=12),
     *             @OA\Property(property="delivery_id", type="integer", nullable=true, example=7),
     *             @OA\Property(property="warehouse_id", type="integer", example=3),
     *             @OA\Property(property="route_id", type="integer", example=5),
     *             @OA\Property(property="customer_id", type="integer", example=22),
     *             @OA\Property(property="salesman_id", type="integer", nullable=true, example=8),
     *             @OA\Property(property="gross_total", type="number", format="float", example=450.00),
     *             @OA\Property(property="vat", type="number", format="float", example=22.50),
     *             @OA\Property(property="net_amount", type="number", format="float", example=427.50),
     *             @OA\Property(property="total", type="number", format="float", example=450.00),
     *             @OA\Property(property="discount", type="number", format="float", example=0),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(
     *                 property="details",
     *                 type="array",
     *                 minItems=1,
     *                 @OA\Items(
     *                     type="object",
     *                     required={"item_id","uom_id","item_price","item_quantity"},
     *                     @OA\Property(property="item_id", type="integer", example=101),
     *                     @OA\Property(property="uom_id", type="integer", example=1),
     *                     @OA\Property(property="discount_id", type="integer", nullable=true, example=null),
     *                     @OA\Property(property="promotion_id", type="integer", nullable=true, example=null),
     *                     @OA\Property(property="parent_id", type="integer", nullable=true, example=null),
     *                     @OA\Property(property="item_price", type="number", format="float", example=150.00),
     *                     @OA\Property(property="item_quantity", type="number", format="float", example=2),
     *                     @OA\Property(property="vat", type="number", format="float", example=15.00),
     *                     @OA\Property(property="discount", type="number", format="float", example=0),
     *                     @OA\Property(property="gross_total", type="number", format="float", example=300.00),
     *                     @OA\Property(property="net_total", type="number", format="float", example=285.00),
     *                     @OA\Property(property="total", type="number", format="float", example=300.00),
     *                     @OA\Property(property="is_promotional", type="boolean", example=false),
     *                     @OA\Property(property="status", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Return transaction created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=201),
     * @OA\Property(property="data",type="object",@OA\Property(property="uuid", type="string"),@OA\Property(property="osa_code", type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error or bad request",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Failed to create return transaction")
     *         )
     *     )
     * )
     */

    public function store(StoreReturnRequest $request): JsonResponse
    {
        try {
            $return = $this->service->create($request->validated());

            if (!$return) {
                return response()->json([
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'Failed to create return transaction',
                ], 400);
            }

            return response()->json([
                'status' => true,
                'code' => 201,
                'data' => new ReturnHeaderResource($return),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Failed to create return transaction',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
    /**
     * @OA\Get(
     *     path="/mob/master_mob/returns/list",
     *     tags={"Mob Agent Returns"},
     *     summary="Get list of return transactions",
     *     description="Fetch paginated list of return transactions or simple dropdown list when dropdown=true.",
     *     @OA\Response(
     *         response=200,
     *         description="Returns fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Returns fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="osa_code", type="string", example="RET-2025-0001"),
     *                     @OA\Property(property="customer_name", type="string", example="John Doe"),
     *                     @OA\Property(property="warehouse_name", type="string", example="Main Warehouse"),
     *                     @OA\Property(property="gross_total", type="number", format="float", example=450.00),
     *                     @OA\Property(property="net_amount", type="number", format="float", example=427.50),
     *                     @OA\Property(property="status", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", example="2025-11-08T10:00:00Z")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="page", type="integer", example=1),
     *                 @OA\Property(property="limit", type="integer", example=50),
     *                 @OA\Property(property="totalPages", type="integer", example=10),
     *                 @OA\Property(property="totalRecords", type="integer", example=500)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error or failed to retrieve returns",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve returns"),
     *             @OA\Property(property="error", type="string", example="SQLSTATE[HY000]: General error ...")
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

            // dd($perPage);
            $returns = $this->service->getAll($perPage, $filters, $dropdown);
            if ($dropdown) {
                return response()->json([
                    'status' => 'success',
                    'code'   => 200,
                    'data'   => $returns,
                ]);
            }

            $pagination = [
                'page'          => $returns->currentPage(),
                'limit'         => $returns->perPage(),
                'totalPages'    => $returns->lastPage(),
                'totalRecords'  => $returns->total(),
            ];

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Returns fetched successfully',
                'data'    => ReturnHeaderResource::collection($returns),
                'meta'    => $pagination,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve returns',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // /**
    //  * @OA\Get(
    //  *     path="/mob/master_mob/returns/show/{uuid}",
    //  *     tags={"Agent Returns"},
    //  *     summary="Get a single return transaction by UUID",
    //  *     description="Fetch detailed information for a specific return transaction including header and details.",
    //  *     @OA\Parameter(
    //  *         name="uuid",
    //  *         in="path",
    //  *         description="UUID of the return transaction",
    //  *         required=true,
    //  *         @OA\Schema(type="string", example="a1f2e5f0-9abc-11ee-b9d1-0242ac120002")
    //  *     ),
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="Return transaction retrieved successfully",
    //  *         @OA\JsonContent(
    //  *             type="object",
    //  *             @OA\Property(property="status", type="string", example="success"),
    //  *             @OA\Property(property="code", type="integer", example=200),
    //  *             @OA\Property(property="data", ref="#/components/schemas/ReturnHeaderResource")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=404,
    //  *         description="Return transaction not found",
    //  *         @OA\JsonContent(
    //  *             type="object",
    //  *             @OA\Property(property="status", type="string", example="error"),
    //  *             @OA\Property(property="code", type="integer", example=404),
    //  *             @OA\Property(property="message", type="string", example="Return transaction not found")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=500,
    //  *         description="Server error retrieving return transaction",
    //  *         @OA\JsonContent(
    //  *             type="object",
    //  *             @OA\Property(property="status", type="string", example="error"),
    //  *             @OA\Property(property="code", type="integer", example=500),
    //  *             @OA\Property(property="message", type="string", example="Failed to retrieve return transaction"),
    //  *             @OA\Property(property="error", type="string", example="Exception message")
    //  *         )
    //  *     )
    //  * )
    //  */

    public function show(string $uuid): JsonResponse
    {
        try {
            $return = $this->service->getByUuid($uuid);

            if (!$return) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 404,
                    'message' => 'Return transaction not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'code'   => 200,
                'data'   => new ReturnHeaderResource($return),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve return transaction',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // /**
    //  * @OA\Delete(
    //  *     path="/mob/master_mob/returns/delete/{uuid}",
    //  *     tags={"Agent Returns"},
    //  *     summary="Delete a return transaction by UUID",
    //  *     description="Permanently deletes a specific return transaction and its associated details.",
    //  *     operationId="deleteReturnTransaction",
    //  *     @OA\Parameter(
    //  *         name="uuid",
    //  *         in="path",
    //  *         description="UUID of the return transaction to delete",
    //  *         required=true,
    //  *         @OA\Schema(type="string", example="a1f2e5f0-9abc-11ee-b9d1-0242ac120002")
    //  *     ),
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="Return transaction deleted successfully",
    //  *         @OA\JsonContent(
    //  *             type="object",
    //  *             @OA\Property(property="status", type="string", example="success"),
    //  *             @OA\Property(property="code", type="integer", example=200),
    //  *             @OA\Property(property="message", type="string", example="Return transaction deleted successfully")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=404,
    //  *         description="Return transaction not found",
    //  *         @OA\JsonContent(
    //  *             type="object",
    //  *             @OA\Property(property="status", type="string", example="error"),
    //  *             @OA\Property(property="code", type="integer", example=404),
    //  *             @OA\Property(property="message", type="string", example="Return transaction not found")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=500,
    //  *         description="Internal server error",
    //  *         @OA\JsonContent(
    //  *             type="object",
    //  *             @OA\Property(property="status", type="string", example="error"),
    //  *             @OA\Property(property="code", type="integer", example=500),
    //  *             @OA\Property(property="message", type="string", example="Failed to delete return transaction"),
    //  *             @OA\Property(property="error", type="string", example="Exception message")
    //  *         )
    //  *     )
    //  * )
    //  */

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $result = $this->service->delete($uuid);

            if (! $result) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 404,
                    'message' => 'Return transaction not found'
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Return transaction deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to delete return transaction',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // /**
    //  * @OA\Post(
    //  *     path="/mob/master_mob/returns/updatestatus",
    //  *     summary="Update multiple return statuses",
    //  *     description="Updates the status of multiple return records based on their UUIDs.",
    //  *     tags={"Return"},
    //  *     @OA\RequestBody(
    //  *         required=true,
    //  *         @OA\JsonContent(
    //  *             type="object",
    //  *             @OA\Property(
    //  *                 property="return_ids",
    //  *                 type="array",
    //  *                 @OA\Items(type="string", format="uuid"),
    //  *                 example={"a2d9e0e1-4b72-4f98-92a3-784ea931bcb9", "b4c2a7a3-6e52-4f8b-8c93-98afaa48df24"}
    //  *             ),
    //  *             @OA\Property(property="status", type="integer", example=2)
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="Return statuses updated successfully",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="success", type="boolean", example=true),
    //  *             @OA\Property(property="message", type="string", example="Return statuses updated.")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=400,
    //  *         description="Validation failed or bad request",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="success", type="boolean", example=false),
    //  *             @OA\Property(property="message", type="string", example="Invalid data provided.")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=500,
    //  *         description="Server error during update",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="success", type="boolean", example=false),
    //  *             @OA\Property(property="message", type="string", example="Update failed.")
    //  *         )
    //  *     )
    //  * )
    //  */
    public function updateMultipleOrderStatus(Request $request): JsonResponse
    {
        $request->validate([
            'return_ids' => 'required|array|min:1',
            'return_ids.*' => 'uuid|exists:return_header,uuid',
            'status' => 'required|integer',
        ]);

        $returnIds = $request->input('return_ids');
        $status = $request->input('status');

        $result = $this->service->updateOrdersStatus($returnIds, $status);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Return statuses updated.'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Update failed.'
        ], 500);
    }

    // /**
    //  * @OA\Put(
    //  *     path="/mob/master_mob/returns/update/{uuid}",
    //  *     summary="Update a return record",
    //  *     description="Updates an existing return entry using its UUID.",
    //  *     tags={"Return"},
    //  *     @OA\Parameter(
    //  *         name="uuid",
    //  *         in="path",
    //  *         required=true,
    //  *         description="UUID of the return record to update",
    //  *         @OA\Schema(type="string", format="uuid"),
    //  *         example="15d2a94a-334c-47be-b6f9-426afd9eeb71"
    //  *     ),
    //  *     @OA\RequestBody(
    //  *         required=true,
    //  *         @OA\JsonContent(
    //  *             type="object",
    //  *             @OA\Property(property="status", type="integer", example=1, description="Updated status of the return"),
    //  *             @OA\Property(property="remarks", type="string", example="Return approved"),
    //  *             @OA\Property(property="updated_by", type="integer", example=5)
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="Return updated successfully",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="status", type="string", example="success"),
    //  *             @OA\Property(property="code", type="integer", example=200),
    //  *             @OA\Property(property="data", type="object",
    //  *                 @OA\Property(property="uuid", type="string", example="15d2a94a-334c-47be-b6f9-426afd9eeb71"),
    //  *                 @OA\Property(property="status", type="integer", example=1),
    //  *                 @OA\Property(property="remarks", type="string", example="Return approved")
    //  *             )
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=404,
    //  *         description="Return not found",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="status", type="string", example="error"),
    //  *             @OA\Property(property="code", type="integer", example=404),
    //  *             @OA\Property(property="message", type="string", example="Return not found")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=400,
    //  *         description="Validation or update failed",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="status", type="string", example="error"),
    //  *             @OA\Property(property="code", type="integer", example=400),
    //  *             @OA\Property(property="message", type="string", example="Failed to update return"),
    //  *             @OA\Property(property="error", type="string", example="SQLSTATE[23502]: Not null violation ...")
    //  *         )
    //  *     )
    //  * )
    //  */

    public function update(UpdateReturnRequest $request, string $uuid): JsonResponse
    {
        try {
            $updated = $this->service->update($uuid, $request->validated());

            if (!$updated) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Return not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'data' => new ReturnHeaderResource($updated)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Failed to update return',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}