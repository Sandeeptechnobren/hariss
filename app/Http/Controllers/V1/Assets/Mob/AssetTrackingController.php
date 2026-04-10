<?php
namespace App\Http\Controllers\V1\Assets\Mob;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Assets\Mob\AssetTrackingRequest;
use App\Services\V1\Assets\Mob\AssetTrackingService;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA; 
use App\Models\User;
use Illuminate\Support\Facades\Storage;
class AssetTrackingController extends Controller
{
       protected $assetService;

    public function __construct(AssetTrackingService $assetService)
    {
        $this->assetService = $assetService;
    }
/**
 * @OA\Post(
 *     path="/mob/technician_mob/asset-tracking/create",
 *     summary="Create Asset Tracking",
 *     description="Create a new asset tracking record with outlet and fridge details",
 *     operationId="createAssetTracking",
 *     tags={"Assets Tracking"},
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *
 *                 @OA\Property(property="osa_code", type="string", example="OSA0001"),
 *                 @OA\Property(property="route_id", type="integer", example=12),
 *                 @OA\Property(property="salesman_id", type="integer", example=101),
 *                 @OA\Property(property="customer_id", type="integer", example=5),
 *
 *                 @OA\Property(property="serial_no", type="string", example="FRG123456"),
 *
 *                 @OA\Property(property="fridge_scan_tracking", type="boolean", example=true),
 *                 @OA\Property(property="have_fridge", type="string", example="yes"),
 *
 *                 @OA\Property(property="image", type="string", format="binary"),
 *
 *                 @OA\Property(property="latitude", type="number", format="float", example=25.317645),
 *                 @OA\Property(property="longitude", type="number", format="float", example=82.973915),
 *
 *                 @OA\Property(property="outlet_name", type="string", example="Sharma General Store"),
 *                 @OA\Property(property="outlet_location", type="string", example="Sigra, Varanasi"),
 *                 @OA\Property(property="outlet_contact", type="string", example="9876543210"),
 *
 *                 @OA\Property(property="outlet_photo", type="string", format="binary"),
 *
 *                 @OA\Property(property="outlet_asm_id", type="integer", example=25),
 *
 *                 @OA\Property(property="last_visit_time", type="string", format="date-time", example="2026-03-06 14:30:00"),
 *
 *                 @OA\Property(property="inform_asm", type="boolean", example=false),
 *
 *                 @OA\Property(property="cooller_condition", type="string", example="Good"),
 *                 @OA\Property(property="complaint_type", type="string", example="Cooling issue"),
 *
 *                 @OA\Property(property="comments", type="string", example="Fridge working but door seal loose")
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Asset tracking created successfully"
 *     ),
 *
 *     @OA\Response(
 *         response=422,
 *         description="Validation error"
 *     )
 * )
 */
public function store(AssetTrackingRequest $request)
{
    try {
        $asset = $this->assetService->store($request);
        return response()->json([
            'status' => true,
            'message' => 'Asset created successfully',
            'data' => $asset
        ], 201);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to create asset',
            'error' => $e->getMessage()
        ], 500);
    }
}
  /**
 * @OA\Post(
 *     path="/mob/technician_mob/asset-tracking/asm-details",
 *     tags={"Assets Tracking"},
 *     summary="Get ASM details dropdown based on warehouse",
 *     description="Fetch ASM details based on warehouse_id, dump response into a text file, store it, and return file path",
 *     operationId="getAsmDetails",
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"warehouse_id"},
 *             @OA\Property(
 *                 property="warehouse_id",
 *                 type="integer",
 *                 example=5
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="ASM details file generated successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(
 *                 property="file_path",
 *                 type="string",
 *                 example="storage/asm_details/asm_details_20260102_153000.txt"
 *             ),
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="ASM details file generated successfully"
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=404,
 *         description="Warehouse not found"
 *     ),
 *
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error"
 *     )
 * )
 */
public function asmdetails(Request $request)
{
    try {
        $warehouseId = $request->warehouse_id;
        $warehouse = Warehouse::select('id', 'area_id')
            ->where('id', $warehouseId)
            ->first();
        if (!$warehouse) {
            return response()->json([
                'status'  => false,
                'message' => 'Warehouse not found'
            ], 404);
        }
        $models = User::query()
            ->select('id', 'name')
            ->where('status', 1)
            ->where('role', 91)
            ->whereJsonContains('area', $warehouse->area_id)
            ->orderBy('id', 'asc')
            ->get();
        $textData = json_encode([
            'status' => true,
            'data'   => $models
        ], JSON_PRETTY_PRINT);
        $fileName = 'asm_details_' . now()->format('Ymd_His') . '.txt';
        Storage::disk('public')->put('asm_details/' . $fileName, $textData);
        return response()->json([
            'status'    => true,
            'file_path' => 'storage/asm_details/' . $fileName
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status'  => false,
            'message' => $e->getMessage()
        ], 500);
    }
}
}