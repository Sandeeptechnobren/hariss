<?php

namespace App\Http\Controllers\V1\B2C_App\Master;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Master\Web\PromotionHeaderResource;
use Illuminate\Http\JsonResponse;
use App\Services\V1\B2C_App\Master\PromotionService;

class PromotionController extends Controller
{
    protected PromotionService $service;

    public function __construct(PromotionService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        try {

            $promotionHeaders = $this->service->promotionListWithImage();

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Promotion list retrieved successfully',
                'data'    => PromotionHeaderResource::collection($promotionHeaders),

                'pagination' => [
                    'page'         => $promotionHeaders->currentPage(),
                    'limit'        => $promotionHeaders->perPage(),
                    'totalPages'   => $promotionHeaders->lastPage(),
                    'totalRecords' => $promotionHeaders->total(),
                ]

            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/master/promotion-headers/list",
     *     tags={"Promotions"},
     *     summary="List promotions",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="promtion_name", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Promotion list",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="code", type="integer"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(type="object")
     *             ),
     *             @OA\Property(property="pagination", type="object")
     *         )
     *     )
     * )
     */
    // public function index(Request $request): JsonResponse
    // {
    //     try {
    //         $filters = $request->only(['id', 'promtion_name', 'status', 'limit']);
    //         $promotionHeaders = $this->service->list($filters);

    //         return response()->json([
    //             'status'  => 'success',
    //             'code'    => 200,
    //             'message' => 'Promotion headers retrieved successfully',
    //             'data'    => PromotionHeaderResource::collection($promotionHeaders),
    //             'pagination' => [
    //                 'page'         => $promotionHeaders->currentPage(),
    //                 'limit'        => $promotionHeaders->perPage(),
    //                 'totalPages'   => $promotionHeaders->lastPage(),
    //                 'totalRecords' => $promotionHeaders->total(),
    //             ]
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'code'    => 500,
    //             'message' => 'Something went wrong',
    //             'error'   => $e->getMessage()
    //         ], 500);
    //     }
    // }
}
