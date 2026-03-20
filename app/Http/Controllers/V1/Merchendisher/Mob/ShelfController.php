<?php

namespace App\Http\Controllers\V1\Merchendisher\Mob;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Merchendisher\Mob\DamageRequest;
use App\Http\Requests\V1\Merchendisher\Mob\ExpiryRequest;
use App\Http\Requests\V1\Merchendisher\Mob\ViewStockPostRequest;
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
 *     path="/mob/merchendisher_mob/shelf/damage-create",
 *     tags={"Shelf Mob"},
 *     summary="Create damage stock entry",
 *     description="Creates a new damage stock record",
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={
 *                 "date",
 *                 "merchandisher_id",
 *                 "customer_id",
 *                 "item_id",
 *                 "shelf_id"
 *             },
 *             @OA\Property(property="date", type="string", format="date", example="2025-01-10"),
 *             @OA\Property(property="merchandisher_id", type="integer", example=1),
 *             @OA\Property(property="customer_id", type="integer", example=5),
 *             @OA\Property(property="item_id", type="integer", example=20),
 *             @OA\Property(property="damage_qty", type="integer", example=2),
 *             @OA\Property(property="expiry_qty", type="integer", example=1),
 *             @OA\Property(property="salable_qty", type="integer", example=10),
 *             @OA\Property(property="shelf_id", type="integer", example=3)
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Damage stock created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Damage stock created successfully"),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=422,
 *         description="Validation error"
 *     )
 * )
 */
public function damagestore(DamageRequest $request): JsonResponse
{
    try {
        $data = $request->validated();
        $damageStock = $this->Service->create($data);

        return response()->json([
            'status'  => true,
            'message' => 'Damage stock created successfully',
            'data'    => $damageStock
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
 *     path="/mob/merchendisher_mob/shelf/expiry-create",
 *     tags={"Shelf Mob"},
 *     summary="Create expiry stock entry",
 *     description="Creates a new expiry stock record",
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={
 *                 "date",
 *                 "merchandisher_id",
 *                 "customer_id",
 *                 "item_id",
 *                 "shelf_id"
 *             },
 *             @OA\Property(property="date", type="string", format="date", example="2025-01-10"),
 *             @OA\Property(property="merchandisher_id", type="integer", example=1),
 *             @OA\Property(property="customer_id", type="integer", example=5),
 *             @OA\Property(property="item_id", type="integer", example=20),
 *             @OA\Property(property="qty", type="numeric", example=2),
 *             @OA\Property(property="expiry_date", type="string", format="date", example="2025-01-10"),
 *             @OA\Property(property="shelf_id", type="integer", example=3)
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Damage stock created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Damage stock created successfully"),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=422,
 *         description="Validation error"
 *     )
 * )
 */
public function expirystore(ExpiryRequest $request): JsonResponse
{
    try {
        $data = $request->validated();
        $expiryStock = $this->Service->expirycreate($data);

        return response()->json([
            'status'  => true,
            'message' => 'Expiry stock created successfully',
            'data'    => $expiryStock
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
 *     path="/mob/merchendisher_mob/shelf/view-stock",
 *     tags={"Shelf Mob"},
 *     summary="Create view stock entry",
 *     description="Creates a new view stock record",
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={
 *                 "date",
 *                 "merchandisher_id",
 *                 "customer_id",
 *                 "item_id",
 *                 "shelf_id"
 *             },
 *             @OA\Property(property="date", type="string", format="date", example="2025-01-10"),
 *             @OA\Property(property="merchandisher_id", type="integer", example=1),
 *             @OA\Property(property="customer_id", type="integer", example=5),
 *             @OA\Property(property="item_id", type="integer", example=20),
 *            @OA\Property(property="capacity", type="numeric", example=12),
 *             @OA\Property(property="good_salable", type="numeric", example=2),
 *             @OA\Property(property="out_of_stock", type="boolean", example=0),
 *             @OA\Property(property="shelf_id", type="integer", example=3)
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Damage stock created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Damage stock created successfully"),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=422,
 *         description="Validation error"
 *     )
 * )
 */
public function viewstock(ViewStockPostRequest $request): JsonResponse
{
    try {
        $data = $request->validated();
        $viewStock = $this->Service->viewstock($data);

        return response()->json([
            'status'  => true,
            'message' => 'View stock created successfully',
            'data'    => $viewStock
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