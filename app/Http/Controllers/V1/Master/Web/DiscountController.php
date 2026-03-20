<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use App\Services\V1\MasterServices\Web\DiscountService;
use App\Http\Requests\V1\MasterRequests\Web\DiscountHeaderRequest;
use App\Http\Requests\V1\MasterRequests\Web\DiscountHeaderUpdateRequest;
use App\Http\Resources\V1\Master\Web\DiscountHeaderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Exception;
use App\Helpers\LogHelper;
use App\Models\DiscountHeader;

/**
 * @OA\Tag(
 *     name="Discount",
 *     description="Discount management APIs"
 * )
 */
class DiscountController extends Controller
{
    use ApiResponse;

    protected DiscountService $discountService;

    public function __construct(DiscountService $discountService)
    {
        $this->discountService = $discountService;
    }

    /**
     * @OA\Get(
     *     path="/api/master/discount/list",
     *     tags={"Discount"},
     *     summary="Get all discounts",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Records per page",
     *         @OA\Schema(type="integer", example=20)
     *     ),
     *     @OA\Response(response=200, description="Discounts fetched successfully"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $discounts = $this->discountService->getAll(
            $request->get('per_page', 50)
        );

        return $this->success(
            DiscountHeaderResource::collection($discounts),
            'Discounts retrieved successfully',
            200,
            [
                'page'         => $discounts->currentPage(),
                'limit'        => $discounts->perPage(),
                'totalPages'   => $discounts->lastPage(),
                'totalRecords' => $discounts->total(),
            ]
        );
    }

    /**
     * @OA\Get(
     *     path="/api/master/discount/{uuid}",
     *     tags={"Discount"},
     *     summary="Get discount by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="Discount UUID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=200, description="Discount retrieved successfully"),
     *     @OA\Response(response=404, description="Discount not found")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $discount = $this->discountService->getByUuid($uuid);

            return $this->success(
                new DiscountHeaderResource($discount),
                'Discount retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->fail($e->getMessage(), 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/master/discount/create",
     *     tags={"Discount"},
     *     summary="Create discount",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="discount_name", type="string", example="New Year Offer"),
     *             @OA\Property(property="discount_apply_on", type="string", example="ITEM"),
     *             @OA\Property(property="discount_type", type="string", example="PERCENTAGE"),
     *             @OA\Property(property="from_date", type="string", format="date", example="2025-01-01"),
     *             @OA\Property(property="to_date", type="string", format="date", example="2025-12-31"),
     *             @OA\Property(
     *                 property="details",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="item_id", type="string", example="ITEM001"),
     *                     @OA\Property(property="category_id", type="string", example="CAT01"),
     *                     @OA\Property(property="uom", type="string", example="PCS"),
     *                     @OA\Property(property="percentage", type="number", example=10),
     *                     @OA\Property(property="amount", type="number", example=0)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Discount created successfully"),
     *     @OA\Response(response=500, description="Error creating discount")
     * )
     */
    public function store(DiscountHeaderRequest $request): JsonResponse
    {
        try {
            $discount = $this->discountService->create($request->validated());

             if ($discount) {
            LogHelper::store(
                '12',                   
                '32',                      
                'add',                    
                null,                    
                $discount->getAttributes(),  
                auth()->id()                      
            );
        }

            return $this->success(
                new DiscountHeaderResource($discount),
                'Discount created successfully',
                201
            );
        } catch (Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/master/discount/update/{uuid}",
     *     tags={"Discount"},
     *     summary="Update discount",
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
     *         @OA\JsonContent(
     *             @OA\Property(property="discount_name", type="string"),
     *             @OA\Property(property="discount_apply_on", type="string"),
     *             @OA\Property(property="discount_type", type="string"),
     *             @OA\Property(property="from_date", type="string", format="date"),
     *             @OA\Property(property="to_date", type="string", format="date"),
     *             @OA\Property(
     *                 property="details",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="item_id", type="string"),
     *                     @OA\Property(property="category_id", type="string"),
     *                     @OA\Property(property="uom", type="string"),
     *                     @OA\Property(property="percentage", type="number"),
     *                     @OA\Property(property="amount", type="number")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Discount updated successfully"),
     *     @OA\Response(response=404, description="Discount not found")
     * )
     */
    public function update(DiscountHeaderUpdateRequest $request, string $uuid): JsonResponse
    {
        $oldDiscount = DiscountHeader::where('uuid', $uuid)->first();
        $previousData = $oldDiscount ? $oldDiscount->getOriginal() : null;
        try {
            $discount = $this->discountService->update($uuid, $request->validated());
            if ($discount && $previousData) {
            LogHelper::store(
                '12',                        
                '32',                        
                'update',                          
                $previousData,                    
                $discount->getAttributes(),       
                auth()->id()                    
            );
        }

            return $this->success(
                new DiscountHeaderResource($discount),
                'Discount updated successfully'
            );
        } catch (Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    // /**
    //  * @OA\Delete(
    //  *     path="/api/master/discount/delete/{uuid}",
    //  *     tags={"Discount"},
    //  *     summary="Delete discount",
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Parameter(
    //  *         name="uuid",
    //  *         in="path",
    //  *         required=true,
    //  *         description="Discount UUID",
    //  *         @OA\Schema(type="string", format="uuid")
    //  *     ),
    //  *     @OA\Response(response=200, description="Discount deleted successfully"),
    //  *     @OA\Response(response=404, description="Discount not found")
    //  * )
    //  */
    // public function destroy(string $uuid): JsonResponse
    // {
    //     try {
    //         $this->discountService->delete($uuid);
    //         return $this->success(null, 'Discount deleted successfully');
    //     } catch (Exception $e) {
    //         return $this->fail($e->getMessage(), 500);
    //     }
    // }

    /**
     * @OA\Get(
     *     path="/api/master/discount/global-search",
     *     tags={"Discount"},
     *     summary="Global search discounts",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search keyword",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(response=200, description="Search result")
     * )
     */
    public function globalSearch(Request $request): JsonResponse
    {
        // dd($request);
        $perPage    = (int) $request->get('per_page', 10);
        $searchTerm = $request->get('query');

        $data = $this->discountService->globalSearch($perPage, $searchTerm);

        return response()->json([
            'status' => 'success',
            'data'   => $data->items(),
            'pagination' => [
                'page'         => $data->currentPage(),
                'per_page'    => $data->perPage(),
                'total'       => $data->total(),
                'total_pages' => $data->lastPage(),
            ]
        ]);
    }
    // public function globalSearch(Request $request): JsonResponse
    // {
    //     try {
    //         $discounts = $this->discountService->globalSearch(
    //             $request->get('per_page', 10),
    //             $request->get('search')
    //         );

    //         return response()->json([
    //             'status' => 'success',
    //             'code'   => 200,
    //             'message' => 'Discounts fetched successfully',
    //             'data'   => DiscountHeaderResource::collection($discounts),
    //             'pagination' => [
    //                 'page'         => $discounts->currentPage(),
    //                 'limit'        => $discounts->perPage(),
    //                 'totalPages'   => $discounts->lastPage(),
    //                 'totalRecords' => $discounts->total(),
    //             ]
    //         ]);
    //     } catch (Exception $e) {
    //         return $this->fail($e->getMessage(), 500);
    //     }
    // }


}
