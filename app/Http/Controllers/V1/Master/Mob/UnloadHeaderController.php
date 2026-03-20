<?php

namespace App\Http\Controllers\V1\Master\Mob;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Mob\UnloadHeaderRequest;
use App\Http\Resources\V1\Agent_Transaction\UnloadHeaderResource;
use App\Services\V1\MasterServices\Mob\UnloadHeaderService;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Http\Request;


/**
 * @OA\Tag(
 *     name="Salesman Unload Header",
 *     description="API endpoints for managing Unload Headers and their Details"
 * )
 */
class UnloadHeaderController extends Controller
{
    public function __construct(protected UnloadHeaderService $service) {}

/**
 * @OA\Post(
 *     path="/mob/master_mob/unload/add",
 *     tags={"Mob Unload Header"},
 *     summary="Create a new Unload with details",
 *     description="Creates unload header with multiple unload detail items",
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             required={"unload_no","warehouse_id","salesman_id","details"},
 *
 *             @OA\Property(property="unload_no", type="string", example="SM1478000125"),
 *             @OA\Property(property="unload_date", type="string", format="date", example="2026-01-13"),
 *             @OA\Property(property="unload_time", type="string", example="10:45:00"),
 *
 *             @OA\Property(property="sync_date", type="string", format="date", example="2026-01-13"),
 *             @OA\Property(property="sync_time", type="string", example="10:46:00"),
 *
 *             @OA\Property(property="warehouse_id", type="integer", example=12),
 *             @OA\Property(property="route_id", type="integer", example=5),
 *             @OA\Property(property="salesman_id", type="integer", example=45),
 *
 *             @OA\Property(property="latitude", type="string", example="28.6139"),
 *             @OA\Property(property="longitude", type="string", example="77.2090"),
 *
 *             @OA\Property(property="salesman_type", type="integer", example=6),
 *             @OA\Property(property="project_type", type="integer", example=3),
 *
 *             @OA\Property(property="load_date", type="string", format="date", example="2026-01-12"),
 *             @OA\Property(property="unload_from", type="string", example="salesman"),
 *
 *             @OA\Property(
 *                 property="details",
 *                 type="array",
 *                 minItems=1,
 *                 @OA\Items(
 *                     type="object",
 *                     required={"item_id","uom","qty"},
 *                     @OA\Property(property="item_id", type="integer", example=101),
 *                     @OA\Property(property="uom", type="integer", example=2),
 *                     @OA\Property(property="qty", type="number", format="float", example=5)
 *                 )
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Unload created successfully"
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
    public function store(UnloadHeaderRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $header = $this->service->store($validated);

            return response()->json([
                'status'  => true,
                'message' => 'Unload created successfully',
                'data'    => new UnloadHeaderResource($header)
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}