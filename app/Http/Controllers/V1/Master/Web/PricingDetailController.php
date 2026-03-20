<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\PricingDetailRequest;
use App\Http\Requests\V1\MasterRequests\Web\PricingDetailUpdateRequest;
use App\Http\Resources\V1\Master\Web\PricingDetailResource;
use App\Http\Resources\V1\Master\Web\PricingHeaderResource;
use App\Services\V1\MasterServices\Web\PricingDetailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Helpers\LogHelper;
use App\Models\PricingHeader;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Schema(
 *     schema="PricingDetail",
 *     type="object",
 *     required={"header_id","item_id","created_user"},
 *     @OA\Property(property="name", type="string", example="Pricing 1"),
 *     @OA\Property(property="header_id", type="integer", example=1),
 *     @OA\Property(property="item_id", type="integer", example=1),
 *     @OA\Property(property="buom_ctn_price", type="number", format="float", example=150.50),
 *     @OA\Property(property="auom_pc_price", type="number", format="float", example=15.75),
 *     @OA\Property(property="status", type="integer", example=1),
 * )
 */
class PricingDetailController extends Controller
{
    private PricingDetailService $service;

    public function __construct(PricingDetailService $service)
    {
        $this->service = $service;
    }

    // /**
    //  * @OA\Get(
    //  *     path="/api/master/pricing-details/list",
    //  *     tags={"PricingDetail"},
    //  *     summary="Get paginated list of pricing details",
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Parameter(name="header_id", in="query", @OA\Schema(type="integer")),
    //  *     @OA\Parameter(name="item_id", in="query", @OA\Schema(type="integer")),
    //  *     @OA\Response(response=200, description="Pricing Details fetched successfully")
    //  * )
    //  */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('limit', 10);
        $filters = $request->only(['header_id', 'item_id']);
        $details = $this->service->all($filters, $perPage);

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => PricingDetailResource::collection($details->items()),
            'pagination' => [
                'page' => $details->currentPage(),
                'limit' => $details->perPage(),
                'totalPages' => $details->lastPage(),
                'totalRecords' => $details->total(),
            ]
        ]);
    }

    // /**
    //  * @OA\Post(
    //  *     path="/api/master/pricing-details/add",
    //  *     tags={"PricingDetail"},
    //  *     summary="Create a new pricing detail",
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/PricingDetail")),
    //  *     @OA\Response(response=200, description="Pricing Detail created successfully")
    //  * )
    //  */
    // public function store(PricingDetailRequest $request): JsonResponse
    // {
    //     $detail = $this->service->create($request->validated());

    //     return response()->json([
    //         'status' => 'success',
    //         'code' => 200,
    //         'data' => new PricingDetailResource($detail)
    //     ], 201);
    // }

    /**
     * @OA\Post(
     *     path="/api/master/pricing-details/add",
     *     tags={"PricingDetail"},
     *     summary="Update an existing Pricing Header with its Details",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="updated Winter Discount"),
     *             @OA\Property(property="description", type="array", @OA\Items(type="string"), example={}),
     *             @OA\Property(property="start_date", type="string", format="date", example="2025-10-15"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2025-12-31"),
     *             @OA\Property(property="apply_on", type="integer", example=1),
     *             @OA\Property(property="warehouse_id", type="string", example="100"),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(property="company_id", type="string", example="110"),
     *             @OA\Property(property="region_id", type="string", example="1"),
     *             @OA\Property(property="area_id", type="string", example="1"),
     *             @OA\Property(property="route_id", type="string", example="50"),
     *             @OA\Property(property="item_category_id", type="string", example="3"),
     *             @OA\Property(property="item_id", type="string", example="77,78"),
     *             @OA\Property(property="customer_id", type="string", example="60"),
     *             @OA\Property(property="customer_category_id", type="string", example="1"),
     *             @OA\Property(property="outlet_channel_id", type="string", example="36"),
     *             @OA\Property(
     *                 property="details",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="Item A"),
     *                     @OA\Property(property="item_id", type="integer", example=77),
     *                     @OA\Property(property="buom_ctn_price", type="number", format="float", example=400),
     *                     @OA\Property(property="auom_pc_price", type="number", format="float", example=40),
     *                     @OA\Property(property="status", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pricing header updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Pricing header updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Invalid input data"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function store(PricingDetailRequest $request): JsonResponse
    {
        // dd($request);
        $pricing = $this->service->create($request->validated());
        if ($pricing) {
            LogHelper::store(
                '12',
                '30',
                'add',
                null,
                $pricing->getAttributes(),
                auth()->id()
            );
        }
        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Pricing plan created successfully',
            'data'    => $pricing
        ], 200);
    }
    // public function store(PricingDetailRequest $request): JsonResponse
    // {
    //     $pricing = $this->service->create1($request->validated());

    //     return response()->json([
    //         'status' => 'success',
    //         'code' => 200,
    //         'message' => 'Pricing plan created successfully',
    //         'data' => $pricing
    //     ], 200);
    // }



    // /**
    //  * @OA\Get(
    //  *     path="/api/master/pricing-details/{uuid}",
    //  *     tags={"PricingDetail"},
    //  *     summary="Get single pricing detail by UUID",
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
    //  *     @OA\Response(response=200, description="Pricing Detail fetched successfully"),
    //  *     @OA\Response(response=404, description="Pricing Detail not found")
    //  * )
    //  */
    // public function show(string $uuid): JsonResponse
    // {
    //     $detail = $this->service->findByUuid($uuid);

    //     if (!$detail) {
    //         return response()->json([
    //             'status' => 'error',
    //             'code' => 404,
    //             'message' => 'Pricing Detail not found'
    //         ], 404);
    //     }

    //     return response()->json([
    //         'status' => 'success',
    //         'code' => 200,
    //         'data' => new PricingDetailResource($detail)
    //     ]);
    // }

    /**
     * @OA\Put(
     *     path="/api/master/pricing-details/update/{uuid}",
     *     tags={"PricingDetail"},
     *     summary="Update a Pricing Header with its Details by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="UUID of the pricing header to update",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="updated Winter Discount"),
     *             @OA\Property(property="description", type="array", @OA\Items(type="string"), example={}),
     *             @OA\Property(property="start_date", type="string", format="date", example="2025-10-15"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2025-12-31"),
     *             @OA\Property(property="apply_on", type="integer", example=1),
     *             @OA\Property(property="warehouse_id", type="string", example="100"),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(property="company_id", type="string", example="110"),
     *             @OA\Property(property="region_id", type="string", example="1"),
     *             @OA\Property(property="area_id", type="string", example="1"),
     *             @OA\Property(property="route_id", type="string", example="50"),
     *             @OA\Property(property="item_category_id", type="string", example="3"),
     *             @OA\Property(property="item_id", type="string", example="77,78"),
     *             @OA\Property(property="customer_id", type="string", example="60"),
     *             @OA\Property(property="customer_category_id", type="string", example="1"),
     *             @OA\Property(property="outlet_channel_id", type="string", example="36"),
     *             @OA\Property(
     *                 property="details",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="Item A"),
     *                     @OA\Property(property="item_id", type="integer", example=77),
     *                     @OA\Property(property="buom_ctn_price", type="number", format="float", example=400),
     *                     @OA\Property(property="auom_pc_price", type="number", format="float", example=40),
     *                     @OA\Property(property="status", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pricing updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Pricing header updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Invalid input data"),
     *     @OA\Response(response=404, description="Pricing Header not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function update(PricingDetailUpdateRequest $request, string $uuid): JsonResponse
    {
        
        $oldHeader = PricingHeader::where('uuid', $uuid)->first();
        $previousData = $oldHeader ? $oldHeader->getOriginal() : null;
      

        try {
            $validatedData = $request->validated();
            //   dd($validatedData);
            // Call service function
            $header = $this->service->updateByUuid($uuid, $validatedData);

            if ($header && $previousData) {
                LogHelper::store(
                    '12',
                    '30',
                    'update',
                    $previousData,
                    $header->getAttributes(),
                    auth()->id()
                );
            }

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Pricing header updated successfully',
                'data' => new PricingHeaderResource($header)
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => $e->getMessage()
            ], 404);
        }
    }


    // /**
    //  * @OA\Delete(
    //  *     path="/api/master/pricing-details/{uuid}",
    //  *     tags={"PricingDetail"},
    //  *     summary="Soft delete pricing detail by UUID",
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
    //  *     @OA\Response(response=200, description="Pricing Detail deleted successfully"),
    //  *     @OA\Response(response=404, description="Pricing Detail not found")
    //  * )
    //  */
    // public function destroy(string $uuid): JsonResponse
    // {
    //     try {
    //         $this->service->deleteByUuid($uuid);
    //         return response()->json([
    //             'status' => 'success',
    //             'code' => 200,
    //             'message' => 'Pricing Detail deleted successfully'
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'code' => 404,
    //             'message' => $e->getMessage()
    //         ], 404);
    //     }
    // }

    // /**
    //  * @OA\Get(
    //  *     path="/api/master/pricing-details/generate-code",
    //  *     tags={"PricingDetail"},
    //  *     summary="Generate unique OSA code",
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Response(response=200, description="Generated code successfully")
    //  * )
    //  */
    // public function generateCode(): JsonResponse
    // {
    //     $code = $this->service->generateOsaCode();

    //     return response()->json([
    //         'status' => 'success',
    //         'code' => 200,
    //         'data' => ['osa_code' => $code]
    //     ]);
    // }

    /**
     * @OA\Get(
     *     path="/api/master/pricing-details/global_search",
     *     tags={"PricingDetail"},
     *     summary="Global search pricing detail with pagination",
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
     *         description="Areas fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="PricingDetail fetched successfully"),
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
            $perPage    = (int) $request->get('per_page', 10);
            $searchTerm = $request->get('query', null);
            $paginator = $this->service->globalSearch(
                $perPage,
                $searchTerm
            );

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Pricing list fetched successfully',
                // 'data'    => PricingHeaderResource::collection($paginator->items()),
                'data'    => $paginator->items(),
                'pagination' => [
                    'page'          => $paginator->currentPage(),
                    'limit'         => $paginator->perPage(),
                    'totalPages'    => $paginator->lastPage(),
                    'totalRecords'  => $paginator->total(),
                ],
            ], 200);
        } catch (\Throwable $e) {
// dd($e);
            Log::error('[Pricing Global Search Failed]', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Unable to fetch pricing list. Please try again later.',
                'data'    => null,
            ], 500);
        }
    }
}
