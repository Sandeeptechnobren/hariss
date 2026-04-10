<?php

namespace App\Http\Controllers\V1\Merchendisher\Mob;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Merchendisher\Mob\ShelfStoreRequest;
use App\Services\V1\Merchendisher\Mob\ShelfService;
use App\Http\Resources\V1\Merchendisher\Mob\ShelveResource;
use App\Http\Resources\V1\Merchendisher\Mob\PlanogramResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ShelfController extends Controller
{
    protected ShelfService $Service;

    public function __construct(ShelfService $Service)
    {
        $this->Service = $Service;
    }
/**
 * @OA\Post(
 *     path="/mob/merchendisher_mob/shelf/store_all",
 *     tags={"Shelf Mob"},
 *     summary="Store damage, expiry and view stock together",
 *     description="Creates damage, expiry and view stock in single API (bulk supported)",
 *     operationId="storeShelfData",
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *
 *             @OA\Property(
 *                 property="damage",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     required={"merchandisher_id","customer_id","item_id","shelf_id"},
 *                     @OA\Property(property="date", type="string", format="date", example="2025-01-10"),
 *                     @OA\Property(property="merchandisher_id", type="integer", example=1),
 *                     @OA\Property(property="customer_id", type="integer", example=5),
 *                     @OA\Property(property="item_id", type="integer", example=20),
 *                     @OA\Property(property="damage_qty", type="integer", example=2),
 *                     @OA\Property(property="expiry_qty", type="integer", example=1),
 *                     @OA\Property(property="salable_qty", type="integer", example=10),
 *                     @OA\Property(property="shelf_id", type="integer", example=3)
 *                 )
 *             ),
 *
 *             @OA\Property(
 *                 property="expiry",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     required={"merchandisher_id","customer_id","item_id","shelf_id"},
 *                     @OA\Property(property="date", type="string", format="date", example="2025-01-10"),
 *                     @OA\Property(property="merchandisher_id", type="integer", example=1),
 *                     @OA\Property(property="customer_id", type="integer", example=5),
 *                     @OA\Property(property="item_id", type="integer", example=20),
 *                     @OA\Property(property="qty", type="integer", example=2),
 *                     @OA\Property(property="expiry_date", type="string", format="date", example="2025-02-01"),
 *                     @OA\Property(property="shelf_id", type="integer", example=3)
 *                 )
 *             ),
 *
 *             @OA\Property(
 *                 property="view_stock",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     required={"merchandisher_id","customer_id","item_id","shelf_id"},
 *                     @OA\Property(property="date", type="string", format="date", example="2025-01-10"),
 *                     @OA\Property(property="merchandisher_id", type="integer", example=1),
 *                     @OA\Property(property="customer_id", type="integer", example=5),
 *                     @OA\Property(property="item_id", type="integer", example=20),
 *                     @OA\Property(property="capacity", type="integer", example=12),
 *                     @OA\Property(property="good_salable", type="integer", example=5),
 *                     @OA\Property(property="out_of_stock", type="boolean", example=false),
 *                     @OA\Property(property="shelf_id", type="integer", example=3)
 *                 )
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Stock created successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Stock created successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 example={
 *                     "damage": {
 *                         {"id": 1, "item_id": 20},
 *                         {"id": 2, "item_id": 21}
 *                     },
 *                     "expiry": {
 *                         {"id": 3, "item_id": 20}
 *                     },
 *                     "view_stock": {
 *                         {"id": 4, "item_id": 20}
 *                     }
 *                 }
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=422,
 *         description="Validation error"
 *     ),
 *
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error"
 *     )
 * )
 */
public function storeAll(ShelfStoreRequest $request): JsonResponse
{
    try {
        $data = $request->validated();

        $result = $this->Service->storeAll($data);

        return response()->json([
            'status'  => true,
            'message' => 'Stock created successfully',
            'data'    => $result
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => false,
            'message' => $e->getMessage()
        ], 500);
    }
}
/**
 * @OA\Post(
 *     path="/mob/merchendisher_mob/shelf/list",
 *     tags={"Shelf Mob"},
 *     summary="Get shelves by merchandiser ID",
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="merchandiser_id", type="integer", example=204)
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Success"
 *     )
 * )
 */
public function index(Request $request)
{
    $merchandiserId = $request->input('merchandiser_id');
    $result = $this->Service->getShelfDataByMerchandiser($merchandiserId);
    $response = [
        'shelves' => ShelveResource::collection($result['shelves']),
        'planograms' => PlanogramResource::collection($result['planograms']),
    ];

    $textContent = json_encode($response, JSON_PRETTY_PRINT);
    $fileName = 'merchandiser_' . $merchandiserId . '_' . now()->format('Ymd_His') . '.txt';
    $filePath = "shelf_details/{$fileName}";
    Storage::disk('public')->put($filePath, $textContent);
    return response()->json([
        'status' => true,
        'message' => 'Data fetched successfully',
        'file_url' => "storage/{$filePath}",
        // 'data' => $response
    ]);
}
}