<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\PromotionGroupRequest;
use App\Http\Requests\V1\MasterRequests\Web\UpdatePromotionGroupRequest;
use App\Models\PromotionGroup;
use App\Services\V1\MasterServices\Web\PromotionGroupService;
use App\Http\Resources\V1\Master\Web\PromotionGroupResource;
use Illuminate\Http\JsonResponse;
use App\Helpers\ResponseHelper;

class PromotionGroupController extends Controller
{
    protected $service;

    public function __construct(PromotionGroupService $service)
    {
        $this->service = $service;
    }

/**
 * @OA\Get(
 *     path="/api/master/promotion-group/list",
 *     summary="Retrieve all promotion groups",
 *     description="Returns a paginated list of promotion groups",
 *     operationId="getPromotionGroups",
 *     tags={"PromotionGroups"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Promotion Group retrieved successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Promotion Group retrieved successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="name", type="string", example="Holiday Sale"),
 *                     @OA\Property(property="osa_code", type="string", example="OSA123"),
 *                     @OA\Property(property="status", type="string", example="active"),
 *                     @OA\Property(property="item_id", type="string", example="1,2,3"),
 *                     @OA\Property(
 *                         property="items",
 *                         type="array",
 *                         @OA\Items(
 *                             type="object",
 *                             @OA\Property(property="id", type="integer", example=1),
 *                             @OA\Property(property="item_code", type="string", example="ITM001"),
 *                             @OA\Property(property="item_name", type="string", example="Item Name")
 *                         )
 *                     ),
 *                     @OA\Property(property="created_user", type="string", example="admin")
 *                 )
 *             ),
 *             @OA\Property(
 *                 property="meta",
 *                 type="object",
 *                 @OA\Property(property="current_page", type="integer", example=1),
 *                 @OA\Property(property="last_page", type="integer", example=5),
 *                 @OA\Property(property="per_page", type="integer", example=10),
 *                 @OA\Property(property="total", type="integer", example=50)
 *             )
 *         )
 *     )
 * )
 */
       public function index()
    {
        $promotiongroup = $this->service->getAll();
        return ResponseHelper::paginatedResponse(
        'Promotion Group retrieved successfully',
        PromotionGroupResource::class,
        $promotiongroup
      );
    }
/**
 * @OA\Post(
 *     path="/api/master/promotion-group/create",
 *     summary="Create a new promotion group",
 *     description="Adds a new promotion group and returns the created record",
 *     operationId="createPromotionGroup",
 *     tags={"PromotionGroups"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="name", type="string", example="Holiday Sale"),
 *             @OA\Property(property="osa_code", type="string", example="OSA123"),
 *             @OA\Property(property="status", type="string", example="active"),
 *             @OA\Property(property="item", type="string", example="1,2,3")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Promotion group added successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Promotion group added successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="Holiday Sale"),
 *                 @OA\Property(property="osa_code", type="string", example="OSA123"),
 *                 @OA\Property(property="status", type="string", example="active"),
 *                 @OA\Property(property="item_id", type="string", example="1,2,3"),
 *                 @OA\Property(
 *                     property="items",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="item_code", type="string", example="ITM001"),
 *                         @OA\Property(property="item_name", type="string", example="Item Name")
 *                     )
 *                 ),
 *                 @OA\Property(property="created_user", type="string", example="admin")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Bad Request - Validation failed",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Validation failed"),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 example={"name": "The name field is required."}
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized - Invalid or missing token",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Unauthorized")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Internal Server Error")
 *         )
 *     )
 * )
 */
    public function store(PromotionGroupRequest $request): JsonResponse
    {
       $group = $this->service->create($request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Promotion group added successfully',
            'data' => $group,
        ], 201);
    }
/**
 * @OA\Get(
 *     path="/api/master/promotion-group/show/{uuid}",
 *     summary="Retrieve a single promotion group",
 *     description="Returns a single promotion group by UUID",
 *     operationId="getPromotionGroupByUuid",
 *     tags={"PromotionGroups"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="uuid",
 *         in="path",
 *         description="UUID of the promotion group",
 *         required=true,
 *         @OA\Schema(type="string", example="123e4567-e89b-12d3-a456-426614174000")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Promotion Group retrieved successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Promotion Groups retrieved successfully"),
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="Holiday Sale"),
 *                 @OA\Property(property="osa_code", type="string", example="OSA123"),
 *                 @OA\Property(property="status", type="string", example="active"),
 *                 @OA\Property(property="item_id", type="string", example="1,2,3"),
 *                 @OA\Property(
 *                     property="items",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="item_code", type="string", example="ITM001"),
 *                         @OA\Property(property="item_name", type="string", example="Item Name")
 *                     )
 *                 ),
 *                 @OA\Property(property="created_user", type="string", example="admin")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Promotion Group not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="No Promotion Groups found"),
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(property="data", type="null", example=null)
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized - Invalid or missing token",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Unauthorized")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Internal Server Error")
 *         )
 *     )
 * )
 */
     public function show($uuid)
    {
         $group = $this->service->getByUuid($uuid);

    if (!$group) {
        return response()->json([
            'message' => 'No Promotion Groups found',
            'code' => 200,
            'data' => null,
        ]);
    }

    return response()->json([
        'message' => 'Promotion Groups retrieved successfully',
        'code' => 200,
        'data' => new PromotionGroupResource($group),
       ]);
    }

/**
 * @OA\Put(
 *     path="/api/master/promotion-group/update/{uuid}",
 *     summary="Update a promotion group",
 *     description="Updates a promotion group by UUID and returns the updated record",
 *     operationId="updatePromotionGroup",
 *     tags={"PromotionGroups"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="uuid",
 *         in="path",
 *         description="UUID of the promotion group to update",
 *         required=true,
 *         @OA\Schema(type="string", example="123e4567-e89b-12d3-a456-426614174000")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="name", type="string", example="Updated Holiday Sale"),
 *             @OA\Property(property="osa_code", type="string", example="OSA456"),
 *             @OA\Property(property="status", type="string", example="inactive"),
 *             @OA\Property(property="item", type="string", example="1,2,4")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Promotion Group updated successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Promotion Group updated successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="Updated Holiday Sale"),
 *                 @OA\Property(property="osa_code", type="string", example="OSA456"),
 *                 @OA\Property(property="status", type="string", example="inactive"),
 *                 @OA\Property(property="item_id", type="string", example="1,2,4"),
 *                 @OA\Property(
 *                     property="items",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="item_code", type="string", example="ITM001"),
 *                         @OA\Property(property="item_name", type="string", example="Item Name")
 *                     )
 *                 ),
 *                 @OA\Property(property="created_user", type="string", example="admin")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Bad Request - Validation failed",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Validation failed"),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 example={"name": "The name field is required."}
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized - Invalid or missing token",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Unauthorized")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Promotion Group not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Promotion Group not found"),
 *             @OA\Property(property="code", type="integer", example=404),
 *             @OA\Property(property="data", type="null", example=null)
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Internal Server Error")
 *         )
 *     )
 * )
 */
   public function update(UpdatePromotionGroupRequest $request, string $uuid): JsonResponse
    {
        $promotionGroup = $this->service->update($uuid, $request->validated());

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Promotion Group updated successfully',
            'data'    => new PromotionGroupResource($promotionGroup),
        ]);
    }
/**
 * @OA\Delete(
 *     path="/api/master/promotion-group/delete/{uuid}",
 *     summary="Delete a promotion group",
 *     description="Deletes a promotion group by UUID",
 *     operationId="deletePromotionGroup",
 *     tags={"PromotionGroups"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="uuid",
 *         in="path",
 *         description="UUID of the promotion group to delete",
 *         required=true,
 *         @OA\Schema(type="string", example="123e4567-e89b-12d3-a456-426614174000")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Promotion group deleted successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Promotion group deleted successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Promotion group not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="code", type="integer", example=404),
 *             @OA\Property(property="message", type="string", example="Promotion group not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized - Invalid or missing token",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Unauthorized")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Internal Server Error")
 *         )
 *     )
 * )
 */
    public function destroy(string $uuid): JsonResponse
{
    try {
        $this->service->delete($uuid);

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Promotion group deleted successfully',
        ]);

    } catch (ModelNotFoundException $e) {
        return response()->json([
            'status'  => 'error',
            'code'    => 404,
            'message' => 'Promotion group not found',
        ], 404);
    }
}
}
