<?php

namespace App\Http\Controllers\V1\Agent_Transaction;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Agent_Transaction\LoadDetailRequest;
use App\Http\Requests\V1\Agent_Transaction\LoadDetailUpdateRequest;
use App\Http\Resources\V1\Agent_Transaction\LoadDetailResource;
use App\Services\V1\Agent_Transaction\LoadDetailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

/**
 * @OA\Tag(
 *     name="Salesman Load Details",
 *     description="API endpoints for managing Load Details"
 * )
 */
class LoadDetailController extends Controller
{
    public function __construct(protected LoadDetailService $service) {}

    /**
     * @OA\Get(
     *     path="/api/agent_transaction/load-detail/list",
     *     tags={"Salesman Load Details"},
     *     security={{"bearerAuth":{}}}, 
     *     summary="List all Load Details",
     *     @OA\Response(
     *         response=200,
     *         description="List of all load details"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->all();
            $details = $this->service->all(50, $filters);
            return ResponseHelper::paginatedResponse(
                'Load details fetched successfully',
                LoadDetailResource::class,
                $details
            );
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // /**
    //  * @OA\Post(
    //  *     path="/api/agent_transaction/load-detail/add",
    //  *     tags={"Salesman Load Details"},
    //  *     security={{"bearerAuth":{}}},
    //  *     summary="Create a new Load Detail",
    //  *     @OA\RequestBody(
    //  *         required=true,
    //  *         @OA\JsonContent(
    //  *             type="object",
    //  *             required={"header_id","item_id","uom","qty","price"},
    //  *             @OA\Property(property="header_id", type="integer", example=1),
    //  *             @OA\Property(property="item_id", type="integer", example=10),
    //  *             @OA\Property(property="uom", type="integer", example=3),
    //  *             @OA\Property(property="qty", type="integer", example=100),
    //  *             @OA\Property(property="price", type="number", format="float", example=500.75),
    //  *             @OA\Property(property="status", type="integer", example=1)
    //  *         )
    //  *     ),
    //  *     @OA\Response(response=201, description="Load detail created successfully")
    //  * )
    //  */
    // public function store(LoadDetailRequest $request): JsonResponse
    // {
    //     try {
    //         $detail = $this->service->store($request->validated());
    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Load detail created successfully',
    //             'data' => new LoadDetailResource($detail)
    //         ], 201);
    //     } catch (Exception $e) {
    //         return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    //     }
    // }

    /**
     * @OA\Get(
     *     path="/api/agent_transaction/load-detail/{uuid}",
     *     tags={"Salesman Load Details"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get a specific Load Detail by UUID",
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="UUID of the Load Detail",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Load detail fetched successfully")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $detail = $this->service->findByUuid($uuid);
            return response()->json([
                'status' => 'success',
                'data' => new LoadDetailResource($detail)
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }

    // /**
    //  * @OA\Put(
    //  *     path="/api/agent_transaction/load-detail/{uuid}",
    //  *     tags={"Salesman Load Details"},
    //  *     security={{"bearerAuth":{}}},
    //  *     summary="Update Load Detail by UUID",
    //  *     @OA\Parameter(
    //  *         name="uuid",
    //  *         in="path",
    //  *         required=true,
    //  *         description="UUID of the Load Detail to update",
    //  *         @OA\Schema(type="string")
    //  *     ),
    //  *     @OA\RequestBody(
    //  *         required=true,
    //  *         @OA\JsonContent(
    //  *             type="object",
    //  *             @OA\Property(property="item_id", type="integer", example=15),
    //  *             @OA\Property(property="uom", type="integer", example=4),
    //  *             @OA\Property(property="qty", type="integer", example=120),
    //  *             @OA\Property(property="price", type="number", format="float", example=600.25),
    //  *             @OA\Property(property="status", type="integer", example=2)
    //  *         )
    //  *     ),
    //  *     @OA\Response(response=200, description="Load detail updated successfully")
    //  * )
    //  */
    // public function update(LoadDetailUpdateRequest $request, string $uuid): JsonResponse
    // {
    //     try {
    //         $detail = $this->service->updateByUuid($uuid, $request->validated());
    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Load detail updated successfully',
    //             'data' => new LoadDetailResource($detail)
    //         ]);
    //     } catch (Exception $e) {
    //         return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    //     }
    // }

    // /**
    //  * @OA\Delete(
    //  *     path="/api/agent_transaction/load-detail/{uuid}",
    //  *     tags={"Salesman Load Details"},
    //  *     security={{"bearerAuth":{}}},
    //  *     summary="Delete a Load Detail by UUID",
    //  *     @OA\Parameter(
    //  *         name="uuid",
    //  *         in="path",
    //  *         required=true,
    //  *         description="UUID of the Load Detail to delete",
    //  *         @OA\Schema(type="string")
    //  *     ),
    //  *     @OA\Response(response=200, description="Load detail deleted successfully")
    //  * )
    //  */
    // public function destroy(string $uuid): JsonResponse
    // {
    //     try {
    //         $this->service->deleteByUuid($uuid);
    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Load detail deleted successfully'
    //         ]);
    //     } catch (Exception $e) {
    //         return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    //     }
    // }
}
