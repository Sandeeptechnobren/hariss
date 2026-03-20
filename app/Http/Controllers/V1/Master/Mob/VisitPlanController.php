<?php

namespace App\Http\Controllers\V1\Master\Mob;

use App\Http\Controllers\Controller;
use App\Services\V1\MasterServices\Mob\VisitPlanService;
use App\Http\Resources\V1\Master\Mob\VisitPlanResource;
use App\Http\Requests\V1\MasterRequests\Mob\VisitPlanRequest;
use App\Http\Requests\V1\MasterRequests\Mob\VisitPlanUpdateRequest;
use Illuminate\Http\Request;
use Exception;

class VisitPlanController extends Controller
{
        protected $service;

    public function __construct(VisitPlanService $service)
    {
        $this->service = $service;
    }
/**
 * @OA\Get(
 *     path="/mob/master_mob/visit_plan/list",
 *     summary="Get all visit plans",
 *     tags={"Visit Plan"},
 *     @OA\Response(
 *         response=200,
 *         description="List of visit plans",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(type="object")
 *         )
 *     )
 * )
 */
    public function index()
    {
        $data = $this->service->getAll();
        return VisitPlanResource::collection($data);
    }
 /**
 * @OA\Get(
 *     path="/mob/master_mob/visit_plan/show/{id}",
 *     summary="Get a visit plan by ID",
 *     tags={"Visit Plan"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Visit plan found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="boolean"),
 *             @OA\Property(property="message", type="string"),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Visit plan not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="boolean"),
 *             @OA\Property(property="message", type="string")
 *         )
 *     )
 * )
 */
public function show($id)
    {
        $data = $this->service->getById($id);
        if (!$data) {
            return response()->json(['status' => false, 'message' => 'Visit plan not found'], 404);
        }
        return new VisitPlanResource($data);
    }
/**
 * @OA\Post(
 *     path="/mob/master_mob/visit_plan/add",
 *     summary="Create a new visit plan",
 *     tags={"Visit Plan"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *                 @OA\Property(property="salesman_id", type="integer"),
 *                 @OA\Property(property="customer_id", type="integer"),
 *                 @OA\Property(property="warehouse_id", type="integer"),
 *                 @OA\Property(property="route_id", type="integer"),
 *                 @OA\Property(property="shop_status", type="string"),
 *                 @OA\Property(property="visit_start_time", type="string", format="date"),
 *                 @OA\Property(property="visit_end_time", type="string", format="date"),
 *                 @OA\Property(property="remarks", type="string"),
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Visit plan created successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="boolean"),
 *             @OA\Property(property="message", type="string"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="salesman_id", type="integer"),
 *                 @OA\Property(property="customer_id", type="integer"),
 *                 @OA\Property(property="warehouse_id", type="integer"),
 *                 @OA\Property(property="route_id", type="integer"),
 *                 @OA\Property(property="shop_status", type="string"),
 *                 @OA\Property(property="visit_start_time", type="string", format="date"),
 *                 @OA\Property(property="visit_end_time", type="string", format="date"),
 *                 @OA\Property(property="remarks", type="string"),
 *                 @OA\Property(property="created_at", type="string", format="date-time"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time")
 *             )
 *         )
 *     )
 * )
 */
public function store(VisitPlanRequest $request)
    {
        $data = $this->service->create($request->validated());
        return response()->json([
            'status' => true,
            'message' => 'Visit plan created successfully',
            'data' => new VisitPlanResource($data)
        ]);
    }
/**
 * @OA\Put(
 *     path="/mob/master_mob/visit_plan/update/{id}",
 *     summary="Update an existing visit plan",
 *     tags={"Visit Plan"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="shop_id", type="integer"),
 *             @OA\Property(property="visit_date", type="string", format="date"),
 *             @OA\Property(property="shop_status", type="boolean"),
 *             @OA\Property(property="remarks", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Visit plan updated successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="boolean"),
 *             @OA\Property(property="message", type="string"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer"),
 *                 @OA\Property(property="shop_id", type="integer"),
 *                 @OA\Property(property="shop_status", type="boolean"),
 *                 @OA\Property(property="visit_date", type="string", format="date"),
 *                 @OA\Property(property="remarks", type="string"),
 *                 @OA\Property(property="created_at", type="string", format="date-time"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Visit plan not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="boolean"),
 *             @OA\Property(property="message", type="string")
 *         )
 *     )
 * )
 */
public function update(VisitPlanUpdateRequest $request, $id)
    {
        $data = $this->service->update($id, $request->validated());
        if (!$data) {
            return response()->json(['status' => false, 'message' => 'Visit plan not found'], 404);
        }
        return response()->json([
            'status' => true,
            'message' => 'Visit plan updated successfully',
            'data' => new VisitPlanResource($data)
        ]);
    }
public function destroy($id)
    {
        $deleted = $this->service->delete($id);
        if (!$deleted) {
            return response()->json(['status' => false, 'message' => 'Visit plan not found'], 404);
        }
        return response()->json([
            'status' => true,
            'message' => 'Visit plan deleted successfully'
        ]);
    }
}