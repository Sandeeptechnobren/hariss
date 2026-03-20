<?php

namespace App\Http\Controllers\V1\Settings\Web;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\CustomerCategory;
use App\Http\Resources\V1\Settings\Web\CustomerCategoryResource;
use App\Services\V1\Settings\Web\CustomerCategoryService;
use App\Traits\ApiResponse;
use App\Helpers\LogHelper;

/**
 * @OA\Schema(
 *     schema="CustomerCategory",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="customer_category_code", type="string", example="CAT01"),
 *     @OA\Property(property="customer_category_name", type="string", example="Premium Customers"),
 *     @OA\Property(property="status", type="integer", example=1, description="0=Active, 1=Inactive"),
 * )
 *
 * @OA\Schema(
 *     schema="ApiResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Customer categories retrieved successfully"),
 *     @OA\Property(property="data", type="object"),
 *     @OA\Property(
 *         property="pagination",
 *         type="object",
 *         @OA\Property(property="page", type="integer", example=1),
 *         @OA\Property(property="limit", type="integer", example=10),
 *         @OA\Property(property="totalPages", type="integer", example=5),
 *         @OA\Property(property="totalRecords", type="integer", example=50)
 *     )
 * )
 */
class CustomerCategoryController extends Controller
{
    use ApiResponse;
    protected CustomerCategoryService $service;

    public function __construct(CustomerCategoryService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/settings/customer-category/list",
     *     summary="Get all customer categories with filters & pagination",
     *     tags={"Customer Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="name", in="query", description="Filter by category name", @OA\Schema(type="string")),
     *     @OA\Parameter(name="code", in="query", description="Filter by category code", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", description="Filter by status (0=Active, 1=Inactive)", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="limit", in="query", description="Number of records per page", @OA\Schema(type="integer", example=10)),
     *     @OA\Parameter(name="page", in="query", description="Page number", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(
     *         response=200,
     *         description="Customer categories retrieved successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="array",
     *                         @OA\Items(ref="#/components/schemas/CustomerCategory")
     *                     )
     *                 )
     *             }
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'customer_category_name',
            'customer_category_code',
            'status',
            'outlet_channel_id',
            'dropdown'
        ]);

        $perPage = $request->get('limit', 10);

        $categories = $this->service->getAll($filters, $perPage);

        // 🔹 If dropdown = true → no pagination
        if ($request->boolean('dropdown')) {
            return response()->json([
                'success' => true,
                'code'    => 200,
                'message' => 'Customer categories retrieved successfully',
                'data'    => $categories
            ], 200);
        }

        // 🔹 Normal paginated response
        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'Customer categories retrieved successfully',
            'data'    => $categories->items(),
            'pagination' => [
                'page'         => $categories->currentPage(),
                'limit'        => $categories->perPage(),
                'totalPages'   => $categories->lastPage(),
                'totalRecords' => $categories->total(),
            ]
        ], 200);
    }

    // public function index(Request $request): JsonResponse
    // {
    //     $filters = $request->only(['customer_category_name', 'customer_category_code', 'status', 'outlet_channel_id']);
    //     $perPage = $request->get('limit', 10);

    //     $categories = $this->service->getAll($filters, $perPage);
    //     // return response()->json([
    //     //     'status'     => 'success',
    //     //     'code'       => 200,
    //     //     'message'    => 'Customers fetched successfully',
    //     //     'data'       => CustomerCategoryResource::collection($categories->items()),
    //     //     'pagination' => [
    //     //         'page'         => $categories->currentPage(),
    //     //         'limit'        => $categories->perPage(),
    //     //         'totalPages'   => $categories->lastPage(),
    //     //         'totalRecords' => $categories->total(),
    //     //     ]
    //     // ]);
    //     return response()->json([
    //         'success' => true,
    //         'code'=>200,
    //         'message' => 'Customer categories retrieved successfully',
    //         'data' => $categories->items(),
    //         'pagination' => [
    //             'page' => $categories->currentPage(),
    //             'limit' => $categories->perPage(),
    //             'totalPages' => $categories->lastPage(),
    //             'totalRecords' => $categories->total(),
    //         ]
    //     ], 200);
    // }

    /**
     * @OA\Get(
     *     path="/api/settings/customer-category/{id}",
     *     summary="Get customer category by ID",
     *     tags={"Customer Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(
     *         response=200,
     *         description="Customer category retrieved successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/CustomerCategory")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(response=404, description="Customer category not found")
     * )
     */
    public function show($id): JsonResponse
    {
        $data = $this->service->getById($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'code' => 200,
                'message' => 'Customer category not found',
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Customer category retrieved successfully',
            'data' => new CustomerCategoryResource($data)
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/settings/customer-category/create",
     *     summary="Create a new customer category",
     *     tags={"Customer Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"customer_category_name", "customer_category_code", "outlet_channel_id"},
     *             @OA\Property(property="outlet_channel_id", type="integer", example=1),
     *             @OA\Property(property="customer_category_name", type="string", example="Regular Customers"),
     *             @OA\Property(property="status", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created successfully")
     * )
     */
    public function store(CustomerCategory $request): JsonResponse
    {
        $data = $this->service->create($request->validated());
        if ($data) {
        LogHelper::store(
            'settings',                        
            'customer_category',             
            'add',                            
            null,                             
            $data->getAttributes(),           
            auth()->id()                    
        );
    }
        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Customer category created successfully',
            'data' => new CustomerCategoryResource($data)
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/settings/customer-category/{id}/update",
     *     summary="Update customer category",
     *     tags={"Customer Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"customer_category_name","outlet_channel_id", "status"},
     *             @OA\Property(property="outlet_channel_id", type="int", example="1"),
     *             @OA\Property(property="customer_category_name", type="string", example="VIP Customers"),
     *             @OA\Property(property="status", type="integer", example=0)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated successfully"),
     *     @OA\Response(response=404, description="Customer category not found")
     * )
     */
    public function update(CustomerCategory $request, $id): JsonResponse
    {
       $oldCategory = \App\Models\CustomerCategory::find($id);
        $previousData = $oldCategory ? $oldCategory->getOriginal() : null;
        $data = $this->service->update($id, $request->validated());

        if ($data && $previousData) {
                LogHelper::store(
                    'settings',                        
                    'customer_category',             
                    'update',                 
                    $previousData,                      
                    $data->getAttributes(),             
                    auth()->id()                    
                );
            }

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Customer category updated successfully',
            'data' => new CustomerCategoryResource($data)
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/settings/customer-category/{id}/delete",
     *     summary="Delete customer category",
     *     tags={"Customer Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Deleted successfully"),
     *     @OA\Response(response=404, description="Customer category not found")
     * )
     */
    public function destroy($id): JsonResponse
    {
        $deleted = $this->service->delete($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'code' => 200,
                'message' => 'Customer category not found or could not be deleted',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Customer category deleted successfully',
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/settings/customer-category/global_search",
     *     summary="Search customer categories globally across multiple fields",
     *     tags={"Customer Categories"},
     *     description="Search across customer_category_code, customer_category_name, status, created_user, updated_user, and return paginated results.",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         description="Search keyword to match across multiple fields"
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", example=10),
     *         description="Number of records per page"
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", example=1),
     *         description="Page number"
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Search results with pagination",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="array",
     *                         @OA\Items(ref="#/components/schemas/CustomerCategory")
     *                     )
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="pagination",
     *                         type="object",
     *                         @OA\Property(property="current_page", type="integer"),
     *                         @OA\Property(property="last_page", type="integer"),
     *                         @OA\Property(property="per_page", type="integer"),
     *                         @OA\Property(property="total", type="integer")
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseError")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server Error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseError")
     *     )
     * )
     */

    public function global_search(Request $request)
    {
        // dd($request);  
        try {
            $perPage = $request->get('per_page', 10);
            $keyword = $request->get('query');

            $customerCategories = $this->service->search($perPage, $keyword);

            return $this->success(
                $customerCategories->items(),
                'Search results',
                200,
                [
                    'pagination' => [
                        'current_page' => $customerCategories->currentPage(),
                        'last_page'    => $customerCategories->lastPage(),
                        'per_page'     => $customerCategories->perPage(),
                        'total'        => $customerCategories->total(),
                    ]
                ]
            );
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }
}
