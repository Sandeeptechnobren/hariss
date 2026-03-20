<?php

namespace App\Http\Controllers\V1\Master\Web;
use App\Http\Controllers\Controller;

use App\Services\V1\MasterServices\Web\DiscountService;
use App\Http\Requests\V1\MasterRequests\Web\StoreDiscountRequest;
use App\Http\Requests\V1\MasterRequests\Web\UpdateDiscountRequest;
use App\Http\Resources\V1\Master\Web\DiscountResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Exception;
    /**
     * @OA\Schema(
     *   schema="Discount",
     *   type="object",
     *   @OA\Property(property="uuid", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *   @OA\Property(property="item_id", type="integer", example=1),
     *   @OA\Property(property="category_id", type="integer", example=2),
     *   @OA\Property(property="customer_id", type="integer", example=3),
     *   @OA\Property(property="customer_channel_id", type="integer", example=1),
     *   @OA\Property(property="discount_type", type="string", enum={"PERCENTAGE","FIXED","BOGO"}, example="PERCENTAGE"),
     *   @OA\Property(property="discount_value", type="number", format="float", example=10.50),
     *   @OA\Property(property="min_quantity", type="integer", example=2),
     *   @OA\Property(property="min_order_value", type="number", format="float", example=100.00),
     *   @OA\Property(property="start_date", type="string", format="date", example="2025-01-01"),
     *   @OA\Property(property="end_date", type="string", format="date", example="2025-12-31"),
     *   @OA\Property(property="status", type="integer", example=1),
     *   @OA\Property(property="creator", type="string", example="Admin User"),
     *   @OA\Property(property="updater", type="string", example="Editor User"),
     *   @OA\Property(property="deleter", type="string", example=null),
     *   @OA\Property(property="created_at", type="string", example="2025-09-29 10:00:00"),
     *   @OA\Property(property="updated_at", type="string", example="2025-09-29 12:00:00"),
     *   @OA\Property(property="deleted_at", type="string", example=null)
     * )
     *
     * @OA\Schema(
     *   schema="StoreDiscountRequest",
     *   type="object",
     *   required={"discount_type","discount_value","start_date","end_date"},
     *   @OA\Property(property="item_id", type="integer", example=1),
     *   @OA\Property(property="category_id", type="integer", example=2),
     *   @OA\Property(property="customer_id", type="integer", example=3),
     *   @OA\Property(property="customer_channel_id", type="integer", example=1),
     *   @OA\Property(property="discount_type", type="integer", example=3),
     *   @OA\Property(property="discount_value", type="number", format="float"),
     *   @OA\Property(property="min_quantity", type="integer", example=0),
     *   @OA\Property(property="min_order_value", type="number", format="float", example=0.00),
     *   @OA\Property(property="start_date", type="string", format="date"),
     *   @OA\Property(property="end_date", type="string", format="date"),
     *   @OA\Property(property="status", type="integer", example=1)
     * )
     *
     * @OA\Schema(
     *   schema="UpdateDiscountRequest",
     *   type="object",
     *   @OA\Property(property="item_id", type="integer", example=1),
     *   @OA\Property(property="category_id", type="integer", example=2),
     *   @OA\Property(property="customer_id", type="integer", example=3),
     *   @OA\Property(property="customer_channel_id", type="integer", example=1),
     *   @OA\Property(property="discount_type", type="string", enum={"PERCENTAGE","FIXED","BOGO"}),
     *   @OA\Property(property="discount_value", type="number", format="float"),
     *   @OA\Property(property="min_quantity", type="integer", example=0),
     *   @OA\Property(property="min_order_value", type="number", format="float", example=0.00),
     *   @OA\Property(property="start_date", type="string", format="date"),
     *   @OA\Property(property="end_date", type="string", format="date"),
     *   @OA\Property(property="status", type="integer", example=0)
     * )
     */


class DiscountController extends Controller
{
    use ApiResponse;

    protected $discountService;

    public function __construct(DiscountService $discountService)
    {
        $this->discountService = $discountService;
    }


    /**
     * @OA\Get(
     *     path="/api/master/discount/list",
     *     tags={"Discount"},
     *     summary="Get all discounts with pagination",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of discounts retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Discounts retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Discount"))
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $discounts = $this->discountService->getAll();
        return $this->success(
            DiscountResource::collection($discounts),
            'Discounts retrieved successfully',
            200,
            [
                'current_page' => $discounts->currentPage(),
                'per_page'     => $discounts->perPage(),
                'total'        => $discounts->total(),
            ],
        );
    }

    /**
     * @OA\Get(
     *     path="/api/master/discount/discount/{uuid}",
     *     tags={"Discount"},
     *     summary="Get a discount by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="Discount UUID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Discount retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Discount")
     *     ),
     *     @OA\Response(response=404, description="Discount not found")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        $discount = $this->discountService->getByUuid($uuid);

        if (!$discount) {
            return $this->fail('Discount not found', 404);
        }

        return $this->success(new DiscountResource($discount), 'Discount retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/api/master/discount/create",
     *     tags={"Discount"},
     *     summary="Create a new discount",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StoreDiscountRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Discount created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Discount")
     *     ),
     *     @OA\Response(response=500, description="Error creating discount")
     * )
     */
    public function store(StoreDiscountRequest $request)
    {
        try {
            $discount = $this->discountService->create($request->validated());
            return $this->success(new DiscountResource($discount), 'Discount created successfully', 201);
        } catch (Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/master/discount/update/{uuid}",
     *     tags={"Discount"},
     *     summary="Update an existing discount",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="Discount UUID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateDiscountRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Discount updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Discount")
     *     ),
     *     @OA\Response(response=404, description="Discount not found"),
     *     @OA\Response(response=500, description="Error updating discount")
     * )
     */
    public function update(UpdateDiscountRequest $request, string $uuid): JsonResponse
    {
        try {
            $discount = $this->discountService->update($uuid, $request->validated());
            return $this->success(new DiscountResource($discount), 'Discount updated successfully');
        } catch (Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/master/discount/delete/{uuid}",
     *     tags={"Discount"},
     *     summary="Delete a discount by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="Discount UUID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=200, description="Discount deleted successfully"),
     *     @OA\Response(response=404, description="Discount not found"),
     *     @OA\Response(response=500, description="Error deleting discount")
     * )
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->discountService->delete($uuid);
            return $this->success(null, 'Discount deleted successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
/**
 * @OA\Get(
 *     path="/api/master/discount/global_search",
 *     tags={"Discount"},
 *     summary="Global search for discounts",
 *     security={{"bearerAuth":{}}}, 
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="Search keyword across discount fields",
 *         required=false,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         description="Number of records per page",
 *         required=false,
 *         @OA\Schema(type="integer", example=10)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Discounts fetched successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Discounts fetched successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer"),
 *                     @OA\Property(property="uuid", type="string", format="uuid"),
 *                     @OA\Property(property="discount_type", type="string", example="PERCENTAGE"),
 *                     @OA\Property(property="discount_value", type="number", format="float", example="10.00"),
 *                     @OA\Property(property="min_quantity", type="integer", example=2),
 *                     @OA\Property(property="min_order_value", type="number", format="float", example="100.00"),
 *                     @OA\Property(property="start_date", type="string", format="date"),
 *                     @OA\Property(property="end_date", type="string", format="date"),
 *                     @OA\Property(property="status", type="integer", example=1),
 *                     @OA\Property(
 *                         property="created_by",
 *                         type="object",
 *                         @OA\Property(property="id", type="integer"),
 *                         @OA\Property(property="firstname", type="string"),
 *                         @OA\Property(property="lastname", type="string"),
 *                         @OA\Property(property="username", type="string")
 *                     ),
 *                     @OA\Property(
 *                         property="updated_by",
 *                         type="object",
 *                         @OA\Property(property="id", type="integer"),
 *                         @OA\Property(property="firstname", type="string"),
 *                         @OA\Property(property="lastname", type="string"),
 *                         @OA\Property(property="username", type="string")
 *                     )
 *                 )
 *             ),
 *             @OA\Property(
 *                 property="pagination",
 *                 type="object",
 *                 @OA\Property(property="page", type="integer", example=1),
 *                 @OA\Property(property="limit", type="integer", example=10),
 *                 @OA\Property(property="totalPages", type="integer", example=1),
 *                 @OA\Property(property="totalRecords", type="integer", example=5)
 *             )
 *         )
 *     ),
 *     @OA\Response(response=404, description="No discounts found"),
 *     @OA\Response(response=500, description="Error fetching discounts")
 * )
 */


public function global_search(Request $request)
    {
        
        try {
            $perPage = $request->get('per_page', 10);
            $searchTerm = $request->get('search');
            $discounts = $this->discountService->globalSearch($perPage, $searchTerm);
            return response()->json([
                "status" => "success",
                "code" => 200,
                "message" => "Routes fetched successfully",
                "data" => $discounts->items(),
                "pagination" => [
                    "page" => $discounts->currentPage(),
                    "limit" => $discounts->perPage(),
                    "totalPages" => $discounts->lastPage(),
                    "totalRecords" => $discounts->total(),
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
