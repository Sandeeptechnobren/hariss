<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Models\CustomerSubCategory;
use App\Http\Requests\V1\Settings\Web\CustomerSubCategoryRequest;
use App\Services\V1\Settings\Web\CustomerSubCategoryService;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Helpers\LogHelper;

/**
 * @OA\Schema(
 *     schema="CustomerSubCategoryRequest",
 *     type="object",
 *     required={"customer_category_id","customer_sub_category_code","customer_sub_category_name"},
 *     @OA\Property(property="customer_category_id", type="integer", example=1),
 *     @OA\Property(property="customer_sub_category_name", type="string", example="Retail"),
 *     @OA\Property(property="status", type="integer", example=0)
 * )
 */
class CustomerSubCategoryController extends Controller
{
    use ApiResponse;

    protected $service;

    public function __construct(CustomerSubCategoryService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/settings/customer-sub-category/list",
     *     tags={"CustomerSubCategory"},
     *     summary="Get all customer sub categories with filters & pagination",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="customer_sub_category_name", in="query", description="Filter by name", @OA\Schema(type="string")),
     *     @OA\Parameter(name="customer_sub_category_code", in="query", description="Filter by code", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", description="Filter by status", @OA\Schema(type="integer", enum={0,1})),
     *     @OA\Parameter(name="limit", in="query", description="Items per page", @OA\Schema(type="integer", example=10)),
     *     @OA\Response(response=200, description="List of customer sub categories")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage  = $request->get('limit', 10);
            $dropdown = $request->boolean('dropdown', false);

            $filters = $request->only([
                'customer_sub_category_name',
                'customer_sub_category_code',
                'status',
                'customer_category_id'
            ]);

            $subCategories = $this->service->getAll(
                $perPage,
                $filters,
                $dropdown
            );

            // 🔹 DROPDOWN RESPONSE
            if ($dropdown) {
                return response()->json([
                    'status'  => true,
                    'code'    => 200,
                    'message' => 'Customer SubCategories fetched successfully',
                    'data'    => $subCategories
                ]);
            }

            // 🔹 EXISTING RESPONSE (UNCHANGED)
            return $this->success(
                $subCategories->items(),
                'Customer SubCategories fetched successfully',
                200,
                [
                    'page'         => $subCategories->currentPage(),
                    'limit'        => $subCategories->perPage(),
                    'totalPages'   => $subCategories->lastPage(),
                    'totalRecords' => $subCategories->total()
                ]
            );
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    // public function index(Request $request): JsonResponse
    // {
    //     try {
    //         $perPage = $request->get('limit', 10);
    //         $filters = $request->only(['customer_sub_category_name', 'customer_sub_category_code', 'status','customer_category_id']);
    //         $subCategories = $this->service->getAll($perPage, $filters);

    //         return $this->success(
    //             $subCategories->items(),
    //             'Customer SubCategories fetched successfully',
    //             200,
    //             [
    //                 'page' => $subCategories->currentPage(),
    //                 'limit' => $subCategories->perPage(),
    //                 'totalPages' => $subCategories->lastPage(),
    //                 'totalRecords' => $subCategories->total()
    //             ]
    //         );
    //     } catch (\Exception $e) {
    //         return $this->fail($e->getMessage());
    //     }
    // }

    /**
     * @OA\Post(
     *     path="/api/settings/customer-sub-category/create",
     *     tags={"CustomerSubCategory"},
     *     summary="Create a new customer sub category",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/CustomerSubCategoryRequest")),
     *     @OA\Response(response=201, description="Customer sub category created successfully")
     * )
     */
    public function store(CustomerSubCategoryRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $userId = Auth::id();
            if (!$userId) throw new \Exception("Unauthenticated: No user logged in");
            $data['created_user'] = $userId;
            $data['updated_user'] = $userId;

            $subCategory = $this->service->create($data);
            DB::commit();

            if ($subCategory) {
                LogHelper::store(
                    'settings',
                    'customer_sub_category',
                    'add',
                    null,
                    $subCategory->getAttributes(),
                    auth()->id()
                );
            }

            return $this->success($subCategory, 'Customer SubCategory created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Create CustomerSubCategory failed: ' . $e->getMessage(), ['data' => $request->all()]);
            return $this->fail($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/settings/customer-sub-category/{id}",
     *     tags={"CustomerSubCategory"},
     *     summary="Get a single customer sub category by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Customer SubCategory details"),
     *     @OA\Response(response=404, description="Customer SubCategory not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $subCategory = $this->service->getById($id);
            return $this->success($subCategory, 'Customer SubCategory fetched successfully');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/settings/customer-sub-category/{id}/update",
     *     tags={"CustomerSubCategory"},
     *     summary="Update a customer sub category",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/CustomerSubCategoryRequest")),
     *     @OA\Response(response=200, description="Customer sub category updated successfully")
     * )
     */
    public function update(CustomerSubCategoryRequest $request, int $id): JsonResponse
    {
        $oldSubCategory = CustomerSubCategory::find($id);
        $previousData = $oldSubCategory ? $oldSubCategory->getOriginal() : null;

        DB::beginTransaction();
        try {
            $data = $request->validated();
            $subCategory = $this->service->update($id, $data);
            DB::commit();

            if ($subCategory && $previousData) {
                LogHelper::store(
                    'settings',
                    'customer_sub_category',
                    'update',
                    $previousData,
                    $subCategory->getAttributes(),
                    auth()->id()
                );
            }
            return $this->success($subCategory, 'Customer SubCategory updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Update CustomerSubCategory failed: ID {$id}, Error: " . $e->getMessage(), ['data' => $request->all()]);
            return $this->fail($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/settings/customer-sub-category/{id}/delete",
     *     tags={"CustomerSubCategory"},
     *     summary="Delete a customer sub category",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Customer sub category deleted successfully"),
     *     @OA\Response(response=404, description="Customer SubCategory not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $oldSubCategory = CustomerSubCategory::find($id);
        $previousData = $oldSubCategory ? $oldSubCategory->getOriginal() : null;
        DB::beginTransaction();
        try {
            $this->service->delete($id);
            DB::commit();
            if ($previousData) {
                LogHelper::store(
                    'settings',
                    'customer_sub_category',
                    'delete',
                    $previousData,
                    null,
                    auth()->id()
                );
            }
            return $this->success(null, 'Customer SubCategory deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Delete CustomerSubCategory failed: ID {$id}, Error: " . $e->getMessage());
            return $this->fail($e->getMessage(), 404);
        }
    }
}
