<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\PromotionDetailRequest;
use App\Http\Resources\V1\Master\Web\PromotionDetailResource;
use App\Services\V1\MasterServices\Web\PromotionDetailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromotionDetailController extends Controller
{
     protected $service;

    public function __construct(PromotionDetailService $service)
    {
        $this->service = $service;
    }
    /**
 * @OA\Get(
 *     path="/api/master/promotion-details/list",
 *     summary="Get list of promotion details with pagination and search",
 *     security={{"bearerAuth":{}}},
 *     tags={"Promotion Details"},
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="Global search keyword",
 *         required=false,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         description="Items per page",
 *         required=false,
 *         @OA\Schema(type="integer", default=10)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="List retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Promotion details retrieved successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="name", type="string", example="Promotion A"),
 *                     @OA\Property(property="description", type="string", example="Details about promotion"),
 *                     @OA\Property(property="header", type="object",
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="title", type="string", example="Header Title")
 *                     )
 *                 )
 *             ),
 *             @OA\Property(
 *                 property="pagination",
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

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'search'   => $request->query('search'),
            'per_page' => $request->query('per_page', 10),
        ];

        $promotionDetails = $this->service->list($filters);

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Promotion details retrieved successfully',
            'data'    => PromotionDetailResource::collection($promotionDetails),
            'pagination'    => [
                'current_page' => $promotionDetails->currentPage(),
                'last_page'    => $promotionDetails->lastPage(),
                'per_page'     => $promotionDetails->perPage(),
                'total'        => $promotionDetails->total(),
            ],
        ]);
    }

    /**
 * @OA\Get(
 *     path="/api/master/promotion-details/show/{uuid}",
 *     summary="Get a specific promotion detail",
 *     security={{"bearerAuth":{}}},
 *     tags={"Promotion Details"},
 *     @OA\Parameter(
 *         name="uuid",
 *         in="path",
 *         description="Promotion Detail ID",
 *         required=true,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Detail retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Promotion detail retrieved successfully"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="Promo X"),
 *                 @OA\Property(property="description", type="string", example="Some details"),
 *                 @OA\Property(property="header", type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="title", type="string", example="Header Title")
 *                 )
 *             )
 *         )
 *     )
 * )
 */

    public function show(string $uuid): JsonResponse
    {
        try {
        $promotionDetail = $this->service->show($uuid);

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Promotion detail retrieved successfully',
            'data'    => new PromotionDetailResource($promotionDetail),
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Promotion detail not found',
        ], 404);
    }
    }

    /**
 * @OA\Post(
 *     path="/api/master/promotion-details/create",
 *     summary="Create a new promotion detail",
 *     security={{"bearerAuth":{}}},
 *     tags={"Promotion Details"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name", "description"},
 *             @OA\Property(property="name", type="string", example="New Promo"),
 *             @OA\Property(property="description", type="string", example="Promo details"),
 *             @OA\Property(property="header_id", type="integer", example=1)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Promotion detail created successfully"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer", example=5),
 *                 @OA\Property(property="name", type="string", example="New Promo"),
 *                 @OA\Property(property="description", type="string", example="Promo details")
 *             )
 *         )
 *     )
 * )
 */
    
    public function store(PromotionDetailRequest $request): JsonResponse
    {
        $promotionDetail = $this->service->create($request->validated());
        return response()->json([
            'status' => 'success',
            'code'=> 200,
            'message' => 'Promotion detail created successfully',
            'data' => new PromotionDetailResource($promotionDetail)
        ]);
    }

    /**
 * @OA\Put(
 *     path="/api/master/promotion-details/update/{uuid}",
 *     summary="Update an existing promotion detail",
 *     security={{"bearerAuth":{}}},
 *     tags={"Promotion Details"},
 *     @OA\Parameter(
 *         name="uuid",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="name", type="string", example="Updated Promo"),
 *             @OA\Property(property="description", type="string", example="Updated details")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Promotion detail updated successfully"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer", example=5),
 *                 @OA\Property(property="name", type="string", example="Updated Promo"),
 *                 @OA\Property(property="description", type="string", example="Updated details")
 *             )
 *         )
 *     )
 * )
 */
    
   public function update(PromotionDetailRequest $request, string $uuid): JsonResponse
    {
        $promotionDetail = $this->service->update($uuid, $request->validated());

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Promotion detail updated successfully',
            'data'    => new PromotionDetailResource($promotionDetail),
        ]);
    }
    /**
 * @OA\Delete(
 *     path="/api/master/promotion-details/delete/{uuid}",
 *     summary="Delete a promotion detail",
 *     security={{"bearerAuth":{}}},
 *     tags={"Promotion Details"},
 *     @OA\Parameter(
 *         name="uuid",
 *         in="path",
 *         description="Promotion Detail uuid",
 *         required=true,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Deleted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Promotion detail deleted successfully")
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
            'message' => 'Promotion detail deleted successfully',
        ]);

    } catch (ModelNotFoundException $e) {
        return response()->json([
            'status'  => 'error',
            'code'    => 404,
            'message' => 'Promotion detail not found',
        ], 404);
    }
}
}
