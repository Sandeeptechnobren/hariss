<?php
   
namespace App\Http\Controllers\V1\Merchendisher\Web;

use App\Http\Controllers\Controller;;
use App\Http\Resources\V1\Merchendisher\Web\AssetTrackingResource;
use App\Services\V1\Merchendisher\Web\AssetTrackingService;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;

class AssetTrackingController extends Controller
{
    protected $service;

    public function __construct(AssetTrackingService $service)
    {
        $this->service = $service;
    }

/**
 * @OA\Get(
 *     path="/web/merchendisher_web/asset-tracking/show/{uuid}",
 *     summary="Retrieve an asset by UUID",
 *     description="Fetch the details of a specific asset based on the provided UUID.",
 *     operationId="getAssetTrackingByUuid",
 *     tags={"AssetTracking"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="uuid",
 *         in="path",
 *         required=true,
 *         description="The UUID of the asset to retrieve",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Asset data retrieved successfully.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Asset retrieved successfully"),
 *             @OA\Property(property="code", type="integer", example=200),         
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Asset not found.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="No Asset found"),
 *             @OA\Property(property="code", type="integer", example=404),
 *             @OA\Property(property="data", type="null")
 *         )
 *     )
 * )
 */
    public function show($uuid)
    {
     $assettracking = $this->service->getByUuid($uuid);

    if (!$assettracking) {
        return response()->json([
            'message' => 'No Asset found',
            'code' => 200,
            'data' => null,
        ]);
    }

    return response()->json([
        'message' => 'Asset retrieved successfully',
        'code' => 200,
        'data' => new AssetTrackingResource($assettracking),
       ]);
    }
/**
 * @OA\Get(
 *     path="/web/merchendisher_web/asset-tracking/list",
 *     summary="Retrieve all assets",
 *     description="Fetch all assets in a paginated format.",
 *     operationId="getAllAssetTracking",
 *     tags={"AssetTracking"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Asset fetched successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Asset fetched successfully"),
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="current_page", type="integer", example=1),
 *                 @OA\Property(property="per_page", type="integer", example=15),
 *                 @OA\Property(property="total", type="integer", example=100),
 *                 @OA\Property(property="last_page", type="integer", example=7),
 *                 @OA\Property(
 *                     property="data",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="asset_code", type="string", example="ASST001"),
 *                         @OA\Property(property="image", type="string", example=""),
 *                         @OA\Property(property="title", type="string", example="XYZ"),
 *                         @OA\Property(property="description", type="string", example="efrwoefblgnelgndf.nb.nbdgjbpjrtgrtgnrtjhprj"),
 *                         @OA\Property(property="from_date", type="string", format="date", example="2025-10-02"),
 *                         @OA\Property(property="to_date", type="string", format="date", example="2025-10-24"),
 *                         @OA\Property(property="model_name", type="string", example="pijpj"),
 *                         @OA\Property(property="barcode", type="string", example="ipo[pk[k"),
 *                         @OA\Property(property="category", type="string", example="ojpojpjp"),
 *                         @OA\Property(property="location", type="string", example="ygiohpojoh"),
 *                         @OA\Property(property="area", type="string", example="ygohoiihohjhu"),
 *                         @OA\Property(property="worker", type="string", example="oijp"),
 *                         @OA\Property(property="additional_worker", type="string", example="ihpj"),
 *                         @OA\Property(property="team", type="string", example="8y"),
 *                         @OA\Property(property="vendors", type="string", example="jlnl"),
 *                         @OA\Property(property="customer_id", type="integer", example=5),
 *                         @OA\Property(property="purchase_date", type="string", format="date", example="2025-10-02"),
 *                         @OA\Property(property="placed_in_service", type="string", format="date", example="2025-08-02"),
 *                         @OA\Property(property="purchase_price", type="string", example="700.00"),
 *                         @OA\Property(property="warranty_expiration", type="string", format="date", example="2025-10-28"),
 *                         @OA\Property(property="residual_price", type="string", example="76.00"),
 *                         @OA\Property(property="useful_life", type="integer", example=565),
 *                         @OA\Property(property="additional_information", type="string", example="ihpjpjpjpjpj"),
 *                         @OA\Property(property="uuid", type="string", example="d8ee1db0-2c70-4502-8018-38839d393742"),
 *                         @OA\Property(property="created_by", type="integer", example=5)
 *                     )
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Bad request",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Invalid request"),
 *             @OA\Property(property="code", type="integer", example=400),
 *             @OA\Property(property="data", type="null")
 *         )
 *     )
 * )
 */

     public function index()
    {
        $assettracking = $this->service->getAll();
        return ResponseHelper::paginatedResponse(
        'Asset fetched successfully',
        AssetTrackingResource::class,
        $assettracking
      );
    }
}