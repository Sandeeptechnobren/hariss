<?php

namespace App\Http\Controllers\V1\Agent_Transaction\Mob;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Agent_Transaction\Mob\StoreCapsCollectionRequest;
use App\Http\Resources\V1\Agent_Transaction\Mob\CapsCollectionHeaderResource;
use App\Services\V1\Agent_Transaction\Mob\CapsCollectionService;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Http\Request;

class CapsCollectionController extends Controller
{
    protected CapsCollectionService $service;

    public function __construct(CapsCollectionService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Post(
     *     path="/mob/master_mob/caps_collection/create",
     *     tags={"Caps Collection mob"},
     *     summary="Create a new caps collection transaction",
     *     description="Creates a new caps collection header with its associated details.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Caps collection creation payload",
     *         @OA\JsonContent(
     *             example={
     *                 "code":"CPC20260017",
     *                 "warehouse_id": 113,
     *                 "route_id": 54,
     *                 "salesman_id": 113,
     *                 "customer": 258961,
     *                 "status": 1,
     *                 "latitude":17.02144,
     *                 "longitude":12.01245,
     *                 "details": {
     *                     {
     *                         "item_id": 45,
     *                         "uom_id": 3,
     *                         "collected_quantity": 10,
     *                         "status": 1
     *                     },
     *                     {
     *                         "item_id": 46,
     *                         "uom_id": 2,
     *                         "collected_quantity": 5,
     *                         "status": 1
     *                     }
     *                 }
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Caps collection transaction created successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Failed to create caps collection transaction"
     *     )
     * )
     */

    public function store(StoreCapsCollectionRequest $request): JsonResponse
    {
        try {
            $collection = $this->service->create($request->validated());

            if (!$collection) {
                return response()->json([
                    'status' => false,
                    'code' => 400,
                    'message' => 'Failed to create caps collection transaction',
                ], 400);
            }

            return response()->json([
                'status' => true,
                'code' => 201,
                'data' => new CapsCollectionHeaderResource($collection),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'code' => 400,
                'message' => 'Failed to create caps collection transaction',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
