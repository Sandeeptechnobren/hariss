<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\PromotionHeaderRequest;
use App\Http\Requests\V1\MasterRequests\Web\PromotioUpdateRequest;
use App\Http\Resources\V1\Master\Web\PromotionHeaderResource;
use App\Services\V1\MasterServices\Web\PromotionHeaderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PromotionHeaderController extends Controller
{
      protected $service;

    public function __construct(PromotionHeaderService $service)
    {
        $this->service = $service;
    }
    /**
 * @OA\Get(
 *     path="/api/master/promotion-headers/list",
 *     tags={"Promotion Headers"},
 *     summary="Get list of promotion headers with pagination and filters",
 *     security={{"bearerAuth":{}}},
 *     description="Returns paginated list of promotion headers filtered by ID, name, and status.",
 * 
 *     @OA\Parameter(
 *         name="id",
 *         in="query",
 *         description="Filter by ID",
 *         required=false,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Parameter(
 *         name="promtion_name",
 *         in="query",
 *         description="Filter by promotion name",
 *         required=false,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="status",
 *         in="query",
 *         description="Filter by status (0 or 1)",
 *         required=false,
 *         @OA\Schema(type="string", enum={"0", "1"})
 *     ),
 *     @OA\Parameter(
 *         name="limit",
 *         in="query",
 *         description="Number of items per page",
 *         required=false,
 *         @OA\Schema(type="integer", default=10)
 *     ),
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Page number",
 *         required=false,
 *         @OA\Schema(type="integer", default=1)
 *     ),
 * 
 *     @OA\Response(
 *         response=200,
 *         description="Promotion headers retrieved successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Promotion headers retrieved successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="promtion_name", type="string", example="Buy One Get One"),
 *                     @OA\Property(property="status", type="string", example="1"),
 *                     @OA\Property(property="from_date", type="string", example="2025-10-01"),
 *                     @OA\Property(property="to_date", type="string", example="2025-10-31"),
 *                     @OA\Property(property="description", type="string", example="October promo"),
 *                     @OA\Property(property="created_at", type="string", format="date-time"),
 *                     @OA\Property(property="updated_at", type="string", format="date-time")
 *                 )
 *             ),
 *             @OA\Property(
 *                 property="pagination",
 *                 type="object",
 *                 @OA\Property(property="page", type="integer", example=1),
 *                 @OA\Property(property="limit", type="integer", example=10),
 *                 @OA\Property(property="totalPages", type="integer", example=5),
 *                 @OA\Property(property="totalRecords", type="integer", example=50)
 *             )
 *         )
 *     ),
 * 
 *     @OA\Response(
 *         response=401,
 *         description="No data or something went wrong",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="code", type="integer", example=401),
 *             @OA\Property(property="message", type="string", example="No promotion headers found")
 *         )
 *     )
 * )
 */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['id', 'promtion_name', 'status', 'limit']);

            $promotionHeaders = $this->service->list($filters);

            if ($promotionHeaders->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'code' => 401,
                    'message' => 'No promotion headers found'
                ], 401);
            }
            $promotionHeaders->load('promotionDetails');
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Promotion headers retrieved successfully',
                'data' => PromotionHeaderResource::collection($promotionHeaders),
                'pagination' => [
                    'page' => $promotionHeaders->currentPage(),
                    'limit' => $promotionHeaders->perPage(),
                    'totalPages' => $promotionHeaders->lastPage(),
                    'totalRecords' => $promotionHeaders->total(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 401,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 401);
        }
    }
/**
 * @OA\Post(
 *     path="/api/master/promotion-headers/create",
 *     tags={"Promotion Headers"},
 *     summary="Create a new promotion header",
 *     security={{"bearerAuth":{}}},
 *     description="Creates a new promotion header and returns the created data",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             required={"promotion_name", "from_date", "to_date", "promotion_details"},
 *             @OA\Property(property="promotion_name", type="string", example="Spring Sale"),
 *             @OA\Property(property="key_combination", type="string", example="ABC123"),
 *             @OA\Property(property="description", type="string", example="Discounts on selected items"),
 *             @OA\Property(property="from_date", type="string", format="date", example="2025-05-01"),
 *             @OA\Property(property="to_date", type="string", format="date", example="2025-06-01"),
 *             @OA\Property(property="status", type="integer", example=1),
 *             @OA\Property(property="warehouse_ids", type="integer", example=5),
 *             @OA\Property(property="manager_ids", type="integer", example=3),
 *             @OA\Property(property="projects_id", type="integer", example=2),
 *             @OA\Property(property="included_customer_id", type="integer", example=10),
 *             @OA\Property(property="excluded_customer_ids", type="integer", example=12),
 *             @OA\Property(property="assignment_uom", type="integer", example=1),
 *             @OA\Property(property="qualification_uom", type="integer", example=2),
 *             @OA\Property(property="outlet_channel_id", type="string", example="online"),
 *             @OA\Property(property="customer_category_id", type="integer", example=1),
 *             @OA\Property(property="bought_item_ids", type="string", example="101,102"),
 *             @OA\Property(property="bonus_item_ids", type="string", example="201,202"),
 *             @OA\Property(property="promotion_details", type="array", 
 *                 @OA\Items(
 *                     type="object",
 *                     required={"lower_qty", "upper_qty", "free_qty"},
 *                     @OA\Property(property="lower_qty", type="integer", example=5),
 *                     @OA\Property(property="upper_qty", type="integer", example=10),
 *                     @OA\Property(property="free_qty", type="integer", example=1)
 *                 )
 *             ),
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Promotion Header created successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="code", type="integer", example=201),
 *             @OA\Property(property="message", type="string", example="Promotion Header created successfully"),
 *             @OA\Property(property="data", type="object", 
 *                 @OA\Property(property="id", type="integer", example=12),
 *                 @OA\Property(property="promotion_name", type="string", example="Spring Sale"),
 *                 @OA\Property(property="status", type="integer", example=1),
 *                 @OA\Property(property="promotion_details", type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="lower_qty", type="integer", example=5),
 *                         @OA\Property(property="upper_qty", type="integer", example=10),
 *                         @OA\Property(property="free_qty", type="integer", example=1)
 *                     )
 *                 )
 *             )
 *         )
 *     )
 * )
 */
 public function store(PromotionHeaderRequest $request): JsonResponse
{
    try {
        $promotionHeader = $this->service->create($request->validated());
        $promotionHeader->load('promotionDetails');
        return response()->json([
            'status' => 'success',
            'code' => 201,
            'message' => 'Promotion Header and Details created successfully',
            'data' => new PromotionHeaderResource($promotionHeader)
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'code' => 401,
            'message' => 'Something went wrong',
            'error' => $e->getMessage()
        ], 401);
    }
}

   /**
 * @OA\Get(
 *     path="/api/master/promotion-headers/show/{id}",
 *     tags={"Promotion Headers"},
 *     summary="Get a single promotion header",
 *     security={{"bearerAuth":{}}},
 *     description="Fetches a specific promotion header by its ID",
 *
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Promotion header ID",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Promotion header retrieved successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Promotion header retrieved successfully"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="promtion_name", type="string", example="Buy One Get One"),
 *                 @OA\Property(property="status", type="string", example="1"),
 *                 @OA\Property(property="from_date", type="string", format="date", example="2025-09-01"),
 *                 @OA\Property(property="to_date", type="string", format="date", example="2025-09-30"),
 *                 @OA\Property(property="description", type="string", example="Limited time promotion"),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-29T10:00:00Z"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-29T10:00:00Z")
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=401,
 *         description="Promotion header not found or something went wrong",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="code", type="integer", example=401),
 *             @OA\Property(property="message", type="string", example="Promotion header not found")
 *         )
 *     )
 * )
 */


        public function show(int $id): JsonResponse
    {
        try {
            $promotionHeader = $this->service->show($id);

            if (!$promotionHeader) {
                return response()->json([
                    'status' => 'success',
                    'code' => 401,
                    'message' => 'Promotion header not found'
                ], 401);
            }
            $promotionHeader->load('promotionDetails');
            return response()->json([
                'status' => 'successs',
                'message' => 'Promotion header retrieved successfully',
                'data' => new PromotionHeaderResource($promotionHeader)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'success',
                'code' => 401,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 401);
        }
    }

    /**
 * @OA\Put(
 *     path="/api/master/promotion-headers/update/{id}",
 *     tags={"Promotion Headers"},
 *     summary="Update a promotion header",
 *     security={{"bearerAuth":{}}},
 *     description="Updates an existing promotion header by ID",
 *
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Promotion header ID",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="promtion_name", type="string", example="Updated Promo Name"),
 *             @OA\Property(property="description", type="string", example="Updated description"),
 *             @OA\Property(property="key_combination", type="string", example="UPDATED-KEY"),
 *             @OA\Property(property="from_date", type="string", format="date", example="2025-10-01"),
 *             @OA\Property(property="to_date", type="string", format="date", example="2025-10-15"),
 *             @OA\Property(property="status", type="string", example="1"),
 *             @OA\Property(property="warehouse_ids", type="integer", example=2),
 *             @OA\Property(property="manager_ids", type="integer", example=4),
 *             @OA\Property(property="projects_id", type="integer", example=6),
 *             @OA\Property(property="included_customer_id", type="integer", example=7),
 *             @OA\Property(property="excluded_customer_ids", type="integer", example=8),
 *             @OA\Property(property="assignment_uom", type="integer", example=1),
 *             @OA\Property(property="qualification_uom", type="integer", example=1),
 *             @OA\Property(property="outlet_channel_id", type="string", example="CHANNEL-001"),
 *             @OA\Property(property="customer_category_id", type="string", example="CATEGORY-A"),
 *             @OA\Property(property="bought_item_ids", type="string", example="101,102"),
 *             @OA\Property(property="bonus_item_ids", type="string", example="201"),
 *             @OA\Property(property="updated_user", type="integer", example=3)
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Promotion header updated successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Promotion header updated successfully"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="promtion_name", type="string", example="Updated Promo Name"),
 *                 @OA\Property(property="status", type="string", example="1"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-10-01T12:00:00Z")
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=401,
 *         description="Something went wrong",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="code", type="integer", example=401),
 *             @OA\Property(property="message", type="string", example="Something went wrong")
 *         )
 *     )
 * )
 */
     public function update(PromotioUpdateRequest $request, int $id): JsonResponse
    {
        $promotionHeader = $this->service->update($id, $request->validated());
        $promotionHeader->load('promotionDetails');
        return response()->json([
            'status' => true,
            'message' => 'Promotion header updated successfully',
            'data' => new PromotionHeaderResource($promotionHeader)
        ]);
    }

    /**
 * @OA\Delete(
 *     path="/api/master/promotion-headers/delete/{id}",
 *     tags={"Promotion Headers"},
 *     summary="Delete a promotion header",
 *     security={{"bearerAuth":{}}},
 *     description="Deletes a promotion header by its ID",
 *
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the promotion header to delete",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Promotion header deleted successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Promotion header deleted successfully")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=401,
 *         description="Something went wrong or ID not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="code", type="integer", example=401),
 *             @OA\Property(property="message", type="string", example="Something went wrong")
 *         )
 *     )
 * )
 */


     public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json([
            'status' => true,
            'message' => 'Promotion header deleted successfully'
        ]);
    }
}
