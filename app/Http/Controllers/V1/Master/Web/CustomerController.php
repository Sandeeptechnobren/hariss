<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\StoreCustomerRequest;
use App\Http\Requests\V1\MasterRequests\Web\UpdateCustomerRequest;
use App\Http\Resources\V1\Master\Web\CustomerResource;
use App\Services\V1\MasterServices\Web\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="Customer",
 *     type="object",
 *     required={"owner_name", "region_id", "area_id", "salesman_id", "fridge_id", "vat_no"},
 *     @OA\Property(property="name", type="string", example="ABC Store"),
 *     @OA\Property(property="owner_name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", example="john@example.com"),
 *     @OA\Property(property="phone_1", type="string", example="9876543210"),
 *     @OA\Property(property="phone_2", type="string", example="0123456789"),
 *     @OA\Property(property="language", type="string", example="English"),
 *     @OA\Property(property="buyertype", type="integer", example=1, description="0 => B2B, 1 => B2C"),
 *     @OA\Property(property="route_id", type="integer", example=46),
 *     @OA\Property(property="customer_category", type="integer", example=1),
 *     @OA\Property(property="customer_sub_category", type="integer", example=1),
 *     @OA\Property(property="outlet_channel_id", type="integer", example=36),
 *     @OA\Property(property="region_id", type="integer", example=1),
 *     @OA\Property(property="area_id", type="integer", example=1),
 *     @OA\Property(property="salesman_id", type="integer", example=99),
 *     @OA\Property(property="fridge_id", type="integer", example=1),
 *     @OA\Property(property="vat_no", type="string", example="VAT123456"),
 *     @OA\Property(property="status", type="integer", example=1, description="0 => Inactive, 1 => Active")
 * )
 */
class CustomerController extends Controller
{
    protected CustomerService $service;

    public function __construct(CustomerService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/web/master_web/customers/list",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get all customers with filters & pagination",
     *     @OA\Parameter(name="osa_code", in="query", description="Filter by customer code", required=false, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="List of customers",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Customers fetched successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Customer")),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="page", type="integer", example=1),
     *                 @OA\Property(property="limit", type="integer", example=10),
     *                 @OA\Property(property="totalPages", type="integer", example=5),
     *                 @OA\Property(property="totalRecords", type="integer", example=50)
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        try {
            $customers = $this->service->list();
            return ResponseHelper::paginatedResponse(
                'Customers fetched successfully',
                CustomerResource::class,
                $customers
            );
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/web/master_web/customers/{uuid}",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get a single customer by UUID",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Customer details", @OA\JsonContent(ref="#/components/schemas/Customer")),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $customer = $this->service->findByUuid($uuid);
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Customer fetched successfully',
                'data' => new CustomerResource($customer)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['status' => 'fail', 'code' => 404, 'message' => $e->getMessage(), 'data' => null], 404);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/web/master_web/customers/add_customer",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     summary="Create a new customer",
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Customer")),
     *     @OA\Response(response=201, description="Customer created successfully"),
     *     @OA\Response(response=500, description="Failed to create customer")
     * )
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        try {
            $customer = $this->service->create($request->validated());
            return response()->json([
                'status' => 'success',
                'code' => 201,
                'message' => 'Customer created successfully',
                'data' => new CustomerResource($customer)
            ], 201);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/web/master_web/customers/{uuid}",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     summary="Update a customer by UUID",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Customer")),
     *     @OA\Response(response=200, description="Customer updated successfully"),
     *     @OA\Response(response=404, description="Customer not found"),
     *     @OA\Response(response=500, description="Failed to update customer")
     * )
     */
    public function update(UpdateCustomerRequest $request, string $uuid): JsonResponse
    {
        try {
            $customer = $this->service->update($uuid, $request->validated());
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Customer updated successfully',
                'data' => new CustomerResource($customer)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['status' => 'fail', 'code' => 404, 'message' => $e->getMessage(), 'data' => null], 404);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/web/master_web/customers/{uuid}",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     summary="Delete a customer by UUID",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Customer deleted successfully"),
     *     @OA\Response(response=404, description="Customer not found"),
     *     @OA\Response(response=500, description="Failed to delete customer")
     * )
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            $deleted = $this->service->delete($uuid);
            if (!$deleted) {
                return response()->json(['status' => 'fail', 'code' => 404, 'message' => 'Customer not found', 'data' => null], 404);
            }
            return response()->json(['status' => 'success', 'code' => 200, 'message' => 'Customer deleted successfully', 'data' => null]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/web/master_web/customers/generate-code",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     summary="Generate unique customer code",
     *     @OA\Response(response=200, description="Unique customer code generated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Unique customer code generated successfully"),
     *             @OA\Property(property="data", type="object", @OA\Property(property="osa_code", type="string", example="C001"))
     *         )
     *     )
     * )
     */
    public function generateCode(): JsonResponse
    {
        try {
            $code = $this->service->generateCode();
            return response()->json(['status' => 'success', 'code' => 200, 'message' => 'Unique customer code generated successfully', 'data' => ['osa_code' => $code]]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/web/master_web/customers/global_search",
     *     tags={"Customers"},
     *     summary="Global search Customers with pagination",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer"),
     *         description="Number of records per page (default: 10)"
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         description="Search keyword for areas"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customers fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Customers fetched successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items()),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="page", type="integer", example=1),
     *                 @OA\Property(property="limit", type="integer", example=10),
     *                 @OA\Property(property="totalPages", type="integer", example=5),
     *                 @OA\Property(property="totalRecords", type="integer", example=50),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to search areas"
     *     )
     * )
     */
    public function global_search(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 10);
            $searchTerm = $request->get('search');
            $customers = $this->service->globalSearch($perPage, $searchTerm);

            return response()->json([
                "status" => "success",
                "code" => 200,
                "message" => "Customers fetched successfully",
                "data" => $customers->items(),
                "pagination" => [
                    "page" => $customers->currentPage(),
                    "limit" => $customers->perPage(),
                    "totalPages" => $customers->lastPage(),
                    "totalRecords" => $customers->total(),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "code" => 500,
                "message" => $e->getMessage(),
                "data" => null
            ], 500);
        }
    }
}