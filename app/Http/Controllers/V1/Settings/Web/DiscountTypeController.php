<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\DiscountTypeRequest;
use App\Http\Resources\V1\Settings\Web\DiscountTypeResource;
use App\Models\DiscountType;
use App\Services\V1\Settings\Web\DiscountTypeService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request; 
use Illuminate\Http\JsonResponse;

/**
 * @OA\Schema(
 *     schema="DiscountType",
 *     type="object",
 *     required={"code","name","status","created_user"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="code", type="string", example="DTC001"),
 *     @OA\Property(property="name", type="string", example="Percentage Discount"),
 *     @OA\Property(property="status", type="integer", enum={0,1}, example=0),
 *     @OA\Property(property="created_user", type="integer", example=1),
 *     @OA\Property(property="updated_user", type="integer", nullable=true, example=2),
 *     @OA\Property(property="created_date", type="string", format="date-time", example="2025-09-17 10:30:00"),
 *     @OA\Property(property="updated_date", type="string", format="date-time", example="2025-09-17 12:00:00")
 * )
 */
class DiscountTypeController extends Controller
{
    use ApiResponse;

    protected DiscountTypeService $discountTypeService;

    public function __construct(DiscountTypeService $discountTypeService)
    {
        $this->discountTypeService = $discountTypeService;
    }

    /**
     * @OA\Get(
     *     path="/api/settings/discount_type/list",
     *     summary="Get all discount types",
     *     tags={"Discount Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/DiscountType")))
     * )
     */
public function index(Request $request): JsonResponse
{
    $filters = $request->only(['discount_type_code', 'discount_type_name', 'status']);
    $perPage = $request->input('per_page', 10);

    $paginator = $this->discountTypeService->getAll($filters, $perPage);

    return $this->success(
        DiscountTypeResource::collection($paginator->items()),
        'Discount types retrieved successfully',
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
     *     path="/api/settings/discount_type/{id}",
     *     summary="Get discount type by ID",
     *     tags={"Discount Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Details", @OA\JsonContent(ref="#/components/schemas/DiscountType")),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show($id): JsonResponse
    {
        $data = new DiscountTypeResource($this->discountTypeService->getById($id));
        return $this->success($data, 'Discount type retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/api/settings/discount_type/create",
     *     summary="Create a new discount type",
     *     tags={"Discount Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","status"},
     *             @OA\Property(property="discount_name", type="string", example="Flat Discount"),
     *             @OA\Property(property="discount_status", type="integer", enum={0,1}, example=0)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/DiscountType"))
     * )
     */
    public function store(DiscountTypeRequest $request)
    {
        $discountType = $this->discountTypeService->create($request->validated());
        return $this->success(new DiscountTypeResource($discountType), 'Discount type created successfully', 201);
    }

    /**
     * @OA\Put(
     *     path="/api/settings/discount_type/{id}/update",
     *     summary="Update discount type",
     *     tags={"Discount Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=2)),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","status"},
     *             @OA\Property(property="discount_name", type="string", example="Special Discount"),
     *             @OA\Property(property="discount_status", type="integer", enum={0,1}, example=1)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/DiscountType")),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function update(DiscountTypeRequest $request,$id): JsonResponse
    {
        $updated = $this->discountTypeService->update($id, $request->validated());
        return $this->success(new DiscountTypeResource($updated), 'Discount type updated successfully');
    }

    /**
     * @OA\Delete(
     *     path="/api/settings/discount_type/{id}/delete",
     *     summary="Delete discount type",
     *     tags={"Discount Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=3)),
     *     @OA\Response(response=204, description="Deleted"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
public function destroy($id): JsonResponse
{
    try {
        $this->discountTypeService->delete($id);

        // ✅ Pass null as data, message as second param, code as third param
        return response()->json([
            'status'=>'success',
            'message'=>"Discount Type Deleted Successfully",
            'code'=>200

        ]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return $this->fail("Discount type not found with ID: {$id}", 404);
    } catch (\Exception $e) {
        return $this->fail($e->getMessage(), 500);
    }
}



}
