<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\ItemSubCategoryRequest;
use App\Http\Resources\V1\Settings\Web\ItemSubCategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\ItemSubCategory;
use App\Services\V1\Settings\Web\ItemSubCategoryService;
use App\Helpers\LogHelper;
/**
 * @OA\Schema(
 *     schema="ItemSubCategory",
 *     type="object",
 *     required={"category_id", "sub_category_name", "status"},
 *     @OA\Property(property="id", type="integer", readOnly=true, example=1),
 *     @OA\Property(property="category_id", type="integer", example=2),
 *     @OA\Property(property="sub_category_name", type="string", example="Soft Drinks"),
 *     @OA\Property(property="status", type="integer", enum={0,1}, example=0, description="0=Active, 1=Inactive"),
 *     @OA\Property(property="created_user", type="integer", nullable=true, readOnly=true, example=1),
 *     @OA\Property(property="updated_user", type="integer", nullable=true, readOnly=true, example=2),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true)
 * )
 */
class ItemSubCategoryController extends Controller
{
    protected $service;

    public function __construct(ItemSubCategoryService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/settings/item-sub-category/list",
     *     summary="Get all item sub categories with pagination and filters",
     *     tags={"Item Sub Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="sub_category_name", in="query", description="Filter by sub category name", @OA\Schema(type="string")),
     *     @OA\Parameter(name="category_id", in="query", description="Filter by category ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", description="Filter by status (0=Active, 1=Inactive)", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=10)),
     *     @OA\Response(
     *         response=200,
     *         description="List of item sub categories with pagination",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ItemSubCategory")),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="page", type="integer", example=1),
     *                 @OA\Property(property="limit", type="integer", example=10),
     *                 @OA\Property(property="totalPages", type="integer", example=5),
     *                 @OA\Property(property="totalRecords", type="integer", example=45)
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = array_filter(
            $request->only(['sub_category_name', 'category_id', 'sub_category_code', 'status']),
            fn($value) => $value !== null && $value !== ''
        );
        $perPage = (int) $request->get('per_page', 50);
        $data = $this->service->getAll($filters, $perPage);
        return response()->json([
            'success' => true,
            'message' => 'Item sub categories retrieved successfully',
            'data' => ItemSubCategoryResource::collection($data->items()),
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
     *     path="/api/settings/item-sub-category/{id}",
     *     summary="Get a single item sub category by ID",
     *     tags={"Item Sub Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Item sub category details", @OA\JsonContent(ref="#/components/schemas/ItemSubCategory")),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show($id): JsonResponse
    {
        $subCategory = $this->service->getById($id);
        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => "data Fetched Successfully",
            'data' => new ItemSubCategoryResource($subCategory)
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/settings/item-sub-category/create",
     *     summary="Create a new item sub category",
     *     tags={"Item Sub Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ItemSubCategory")),
     *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/ItemSubCategory")),
     *     @OA\Response(response=400, description="Validation error")
     * )
     */
    public function store(ItemSubCategoryRequest $request): JsonResponse
    {
        $subCategory = $this->service->create($request->validated());
        
        if ($subCategory) {
        LogHelper::store(
            'settings',                 
            'item_sub_category',                
            'add',                   
            null,                      
            $subCategory->getAttributes(),     
            auth()->id()                
        );
    }
        // dd($subCategory);
        if (!$subCategory) {
            return response()->json(['success' => false, 'message' => 'Failed to create item sub category'], 500);
        }
        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Item Subcategories created',
            'data' => new ItemSubCategoryResource($subCategory)
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/settings/item-sub-category/{id}/update",
     *     summary="Update an existing item sub category",
     *     tags={"Item Sub Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ItemSubCategory")),
     *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/ItemSubCategory")),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function update(ItemSubCategoryRequest $request, $id): JsonResponse
    {
        $oldSubCategory = ItemSubCategory::find($id);
        $previousData = $oldSubCategory ? $oldSubCategory->getOriginal() : null;
        $subCategory = $this->service->update($id, $request->validated());

        if ($subCategory && $previousData) {
        LogHelper::store(
            'settings',                     
            'item_sub_category',             
            'update',                    
            $previousData,                    
            $subCategory->getAttributes(),   
            auth()->id()                  
        );
    }
        if (!$subCategory) {
            return response()->json(['success' => false, 'message' => 'Failed to update item sub category'], 500);
        }
        return response()->json(['success' => true, 'code' => 200, 'message' => 'Subcategory Updated Successfully', 'data' => new ItemSubCategoryResource($subCategory)]);
    }

    /**
     * @OA\Delete(
     *     path="/api/settings/item-sub-category/{id}/delete",
     *     summary="Delete an item sub category",
     *     tags={"Item Sub Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Deleted", @OA\JsonContent(example={"message": "Item sub category deleted successfully"})),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */

    public function destroy($id): JsonResponse
{
    $subCategory = ItemSubCategory::findOrFail($id);
    $previousData = $subCategory->getOriginal();

    $deleted = $this->service->delete($subCategory);

    if ($deleted && $previousData) {
        LogHelper::store(
            'settings',                  
            'item_sub_category',      
            'delete',                  
            $previousData,            
            null,                      
            auth()->id()              
        );
    }

    if (!$deleted) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to delete item sub category'
        ], 500);
    }

    return response()->json([
        'success' => true,
        'message' => 'Item sub category deleted successfully'
    ]);
}

}
