<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\PromotionTypeRequest;
use App\Http\Resources\V1\Settings\Web\PromotionTypeResource;
use App\Models\PromotionType;
use App\Services\V1\Settings\Web\PromotionTypeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


/**
 * @OA\Schema(
 *     schema="PromotionType",
 *     type="object",
 *     required={"code","name","status","created_user"},
 *     @OA\Property(property="id", type="integer", readOnly=true, example=1),
 *     @OA\Property(property="code", type="string", example="PROMO001"),
 *     @OA\Property(property="name", type="string", example="Discount Offer"),
 *     @OA\Property(property="status", type="integer", enum={0,1}, example=0, description="0=Active, 1=Inactive"),
 *     @OA\Property(property="created_user", type="integer", example=1),
 *     @OA\Property(property="updated_user", type="integer", nullable=true, example=2),
 *     @OA\Property(property="created_date", type="string", format="date-time", example="2025-09-17 10:00:00"),
 *     @OA\Property(property="updated_date", type="string", format="date-time", example="2025-09-17 12:00:00")
 * )
 */
class PromotionTypeController extends Controller
{
    use ApiResponse;

    protected $promotionTypeService;

    public function __construct(PromotionTypeService $promotionTypeService)
    {
        $this->promotionTypeService = $promotionTypeService;
    }

    /**
     * @OA\Get(
     *     path="/api/settings/promotion_type/list",
     *     summary="Get all promotion types",
     *     tags={"Promotion Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List of promotion types", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/PromotionType")))
     * )
     */
public function index(): JsonResponse
{
    $paginator = $this->promotionTypeService->getAll();
    return $this->success(
        PromotionTypeResource::collection($paginator->items()),
        'Promotion types fetched successfully',
        200,
        [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
    );
}


    /**
     * @OA\Get(
     *     path="/api/settings/promotion_type/{id}",
     *     summary="Get promotion type by ID",
     *     tags={"Promotion Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Promotion type details", @OA\JsonContent(ref="#/components/schemas/PromotionType")),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show($id): JsonResponse
    {
        $promotionType = $this->promotionTypeService->getById($id);

        if (!$promotionType) {
            return $this->fail('Promotion type not found', 404);
        }

        return $this->success(new PromotionTypeResource($promotionType), 'Promotion type fetched successfully');
    }

    /**
 * @OA\Post(
 *     path="/api/settings/promotion_type/create",
 *     summary="Create a new promotion type",
 *     tags={"Promotion Types"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"code","name","status"},
 *             @OA\Property(property="code", type="string", example="PROMO002"),
 *             @OA\Property(property="name", type="string", example="Seasonal Sale"),
 *             @OA\Property(property="status", type="integer", enum={0,1}, example=0)
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Created",
 *         @OA\JsonContent(ref="#/components/schemas/PromotionType")
 *     )
 * )
 */

public function store(Request $request): JsonResponse
{
    $response = $this->promotionTypeService->create($request->all());
    return response()->json($response, $response['code']);
}


    /**
     * @OA\Put(
     *     path="/api/settings/promotion_type/{id}/update",
     *     summary="Update promotion type",
     *     tags={"Promotion Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=2)),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code","name","status","updated_user"},
     *             @OA\Property(property="code", type="string", example="PROMO002"),
     *             @OA\Property(property="name", type="string", example="Updated Promotion"),
     *             @OA\Property(property="status", type="integer", enum={0,1}, example=1),
     *             @OA\Property(property="updated_user", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/PromotionType")),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
public function update(PromotionTypeRequest $request, $id): JsonResponse
{
    try {
        $promotionType = $this->promotionTypeService->update($id, $request->validated());

        return $this->success(
            new PromotionTypeResource($promotionType),
            'Promotion type updated successfully'
        );
    } catch (\Exception $e) {
        return $this->fail(
            'Failed to update promotion type',
            500,
            [$e->getMessage()]
        );
    }
}

    /**
     * @OA\Delete(
     *     path="/api/settings/promotion_type/{id}/delete",
     *     summary="Delete promotion type",
     *     tags={"Promotion Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=3)),
     *     @OA\Response(response=204, description="Deleted"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
public function destroy($id): JsonResponse
{
    $deleted = $this->promotionTypeService->delete($id);
    if ($deleted) {
        return response()->json([
            'status'=>'success',
            'code'=>200,
            'message'=>'Promotion type deleted successfully',
        ]);
    }

    return $this->fail('Failed to delete promotion type', 500);
}


}
