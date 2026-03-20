<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\ItemCategoryRequest;
use App\Http\Resources\V1\Settings\Web\ItemCategoryResource;
use Illuminate\Http\JsonResponse;
use App\Models\ItemCategory;
use App\Services\V1\Settings\Web\ItemCategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(
 *     schema="ItemCategory",
 *     title="Item Category",
 *     description="Item Category model schema",
 *     type="object",
 *     @OA\Property(property="category_name", type="string", example="Electronics"),
 *     @OA\Property(property="status", type="int", example=1),
 * )
 */
class ItemCategoryController extends Controller
{
    protected $itemCategoryService;

    public function __construct(ItemCategoryService $service)
    {
        $this->itemCategoryService = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/settings/item_category/list",
     *     summary="Get all item categories with pagination and filters",
     *     tags={"Item Categories"},
     *     security={{"bearerAuth":{}}}, 
     *     @OA\Parameter(name="category_name", in="query", description="Filter by category name", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", description="Filter by status (0=Active, 1=Inactive)", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=10)),
     *     @OA\Response(
     *         response=200,
     *         description="List of item categories with pagination",
     *         @OA\JsonContent(type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="int", example=200),
     *             @OA\Property(property="message", type="string", example="Item categories retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ItemCategory")),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="page", type="integer", example=1),
     *                 @OA\Property(property="limit", type="integer", example=10),
     *                 @OA\Property(property="totalPages", type="integer", example=5),
     *                 @OA\Property(property="totalRecords", type="integer", example=45),
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['category_name', 'code', 'status']);
        $perPage = $request->get('per_page', 10);

        $data = $this->itemCategoryService->getAll($filters, $perPage);

        return response()->json([
            'success' => true,
            'code ' => 200,
            'message' => 'Item categories retrieved successfully',
            'data' => ItemCategoryResource::collection($data->items()),
            'pagination' => [
                'page' => $data->currentPage(),
                'limit' => $data->perPage(),
                'totalPages' => $data->lastPage(),
                'totalRecords' => $data->total(),
            ]
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/settings/item_category/{id}",
     *     summary="Get item category by ID",
     *     tags={"Item Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Item category details", @OA\JsonContent(ref="#/components/schemas/ItemCategory")),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show($id): JsonResponse
    {
        $data = $this->itemCategoryService->getById($id);
        return response()->json(['success' => true, 'code' => 200, 'data' => new ItemCategoryResource($data)]);
    }

    /**
     * @OA\Post(
     *     path="/api/settings/item_category/create",
     *     summary="Create a new item category",
     *     tags={"Item Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ItemCategory")),
     *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/ItemCategory"))
     * )
     */
    public function store(ItemCategoryRequest $request): JsonResponse
    {
        $data = $this->itemCategoryService->create($request->validated(), Auth::id());
        return response()->json(['success' => true, 'code' => 200,'message'=>'Item category created Successfully', 'data' => new ItemCategoryResource($data)]);
    }

    /**
     * @OA\Put(
     *     path="/api/settings/item_category/{id}",
     *     summary="Update item category",
     *     tags={"Item Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ItemCategory")),
     *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/ItemCategory"))
     * )
     */
    public function update(ItemCategoryRequest $request, $id): JsonResponse
    {
        $itemCategory = ItemCategory::find($id);

        if (!$itemCategory) {
            return response()->json([
                'success' => false,
                'code' => 200,
                'message' => 'Item Category not found'
            ], 404);
        }

        $data = $this->itemCategoryService->update(
            $itemCategory,
            $request->validated(),
            Auth::id()
        );

        return response()->json([
            'success' => true,
            "code" => 200,
            'data' => new ItemCategoryResource($data)
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/settings/item_category/{id}",
     *     summary="Delete item category",
     *     tags={"Item Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Deleted")
     * )
     */
    public function destroy($id): JsonResponse
    {
        $itemCategory = ItemCategory::findOrFail($id);

        $deleted = $this->itemCategoryService->delete($itemCategory);

        if ($deleted) {
            return response()->json([
                'success' => true,
                'code' => 200,
                'message' => 'Item Category deleted successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'code' => 200,
            'message' => 'Failed to delete Item Category'
        ], 500);
    }
}
