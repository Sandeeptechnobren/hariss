<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\CustomerTypeRequest;
use App\Http\Resources\V1\Settings\Web\CustomerTypeResource;
use App\Services\V1\Settings\Web\CustomerTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(
 *     schema="CustomerType",
 *     type="object",
 *     required={"code", "name", "status"},
 *     @OA\Property(property="name", type="string", example="Retail"),
 *     @OA\Property(property="status", type="integer", enum={0,1}, example=0, description="0=Active, 1=Inactive"),
 * )
 */
class CustomerTypeController extends Controller
{
    protected $customerTypeService;

    public function __construct(CustomerTypeService $service)
    {
        $this->customerTypeService = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/settings/customer-type/list",
     *     summary="Get all customer types with pagination and filters",
     *     tags={"Customer Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="name", in="query", description="Filter by name", @OA\Schema(type="string")),
     *     @OA\Parameter(name="code", in="query", description="Filter by code", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", description="Filter by status (0=Active, 1=Inactive)", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=10)),
     *     @OA\Response(
     *         response=200,
     *         description="List of customer types with pagination",
     *         @OA\JsonContent(type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CustomerType")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=45),
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'customer_type_name',
            'customer_type_code',
            'status',
            'dropdown'
        ]);

        if ($request->has('dropdown')) {
            $filters['dropdown'] = filter_var(
                $request->dropdown,
                FILTER_VALIDATE_BOOLEAN
            );
        }

        $perPage = $request->get('per_page', 10);

        $data = $this->customerTypeService->getAll($filters, $perPage);

        if (!empty($filters['dropdown']) && $filters['dropdown'] === true) {
            return response()->json([
                'success' => true,
                'code'    => 200,
                'message' => 'Customer types fetched for dropdown',
                'data'    => $data
            ], 200);
        }

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'Customer types retrieved successfully',
            'data'    => $data->items(),
            'pagination' => [
                'page'         => $data->currentPage(),
                'limit'        => $data->perPage(),
                'totalPages'   => $data->lastPage(),
                'totalRecords' => $data->total(),
            ]
        ], 200);
    }

    // public function index(Request $request): JsonResponse
    // {
    //     $filters = $request->only(['name', 'code', 'status']);
    //     $perPage = $request->get('per_page', 10);
        
    //     $data = $this->customerTypeService->getAll($filters, $perPage);
    //     // dd($data);
    //     return response()->json([
    //         'success' => true,
    //         'code'=>200,
    //         'message' => 'Customer categories retrieved successfully',
    //         'data' => $data->items(),
    //         'pagination' => [
    //             'page' => $data->currentPage(),
    //             'limit' => $data->perPage(),
    //             'totalPages' => $data->lastPage(),
    //             'totalRecords' => $data->total(),
    //         ]
    //     ], 200);
    // }

    /**
     * @OA\Get(
     *     path="/api/settings/customer-type/{id}",
     *     summary="Get customer type by ID",
     *     tags={"Customer Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Customer type details", @OA\JsonContent(ref="#/components/schemas/CustomerType")),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show($id): JsonResponse
    {
        $data = $this->customerTypeService->getById($id);
        return response()->json(['success' => true, 'code' => 200, 'data' => new CustomerTypeResource($data)]);
    }

    /**
     * @OA\Post(
     *     path="/api/settings/customer-type/create",
     *     summary="Create a new customer type",
     *     tags={"Customer Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/CustomerType")),
     *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/CustomerType"))
     * )
     */
    public function store(CustomerTypeRequest $request): JsonResponse
    {
        $data = $this->customerTypeService->create($request->validated(), Auth::id());
        return response()->json(['success' => true, 'code' => 200, 'data' => new CustomerTypeResource($data)], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/settings/customer-type/{id}",
     *     summary="Update customer type",
     *     tags={"Customer Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/CustomerType")),
     *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/CustomerType"))
     * )
     */
    public function update(CustomerTypeRequest $request, $id): JsonResponse
    {
        $data = $this->customerTypeService->update($id, $request->validated(), Auth::id());
        return response()->json(['success' => true, 'code' => 200, 'data' => new CustomerTypeResource($data)]);
    }

    /**
     * @OA\Delete(
     *     path="/api/settings/customer-type/{id}",
     *     summary="Delete customer type",
     *     tags={"Customer Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Deleted")
     * )
     */
    public function destroy($id): JsonResponse
    {
        $this->customerTypeService->delete($id);
        return response()->json(['success' => true, 'code' => 200, 'message' => 'Customer Type deleted successfully']);
    }
}
