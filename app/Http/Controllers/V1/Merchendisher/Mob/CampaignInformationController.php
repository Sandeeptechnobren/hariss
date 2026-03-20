<?php

namespace App\Http\Controllers\V1\Merchendisher\Mob;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Merchendisher\Mob\CampaignInformationRequest;
use App\Http\Resources\V1\Merchendisher\Mob\CampaignInformationResource;
use App\Models\CampaignInformation;
use App\Services\V1\Merchendisher\Mob\CampaignInformationService;
use Illuminate\Support\Str;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;

class CampaignInformationController extends Controller
{
     protected $service;

    public function __construct(CampaignInformationService $service)
    {
        $this->service = $service;
    }

/**
 * @OA\Post(
 *     path="/mob/merchendisher_mob/campaign-info/create",
 *     summary="Create a new campaign information entry",
 *     tags={"Campaign Information"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"merchandiser_id", "customer_id", "feedback", "image_1"},
 *                 @OA\Property(property="merchandiser_id", type="integer", example=101),
 *                 @OA\Property(property="customer_id", type="integer", example=205),
 *                 @OA\Property(property="feedback", type="string", example="Customer responded positively."),
 *                 @OA\Property(property="image_1", type="string", format="binary", description=" (jpg, jpeg, png, max 2MB)"),
 *                 @OA\Property(property="image_2", type="string", format="binary", description=" (jpg, jpeg, png, max 2MB)"),
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Campaign information created successfully",
 *         @OA\JsonContent(
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation failed",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 example={
 *                     "image_1": {"The image 1 must be a file of type: jpg, jpeg, png."}
 *                 }
 *             )
 *         )
 *     )
 * )
 */

    public function store(CampaignInformationRequest $request)
    {
        $campaign = $this->service->store($request->validated());
        return new CampaignInformationResource($campaign);
    }
/**
 * @OA\Get(
 *     path="/api/merchendisher/campagin-info/list",
 *     summary="Get paginated list of campaign information",
 *     tags={"Campaign Information"},
 *     security={{"bearerAuth":{}}},
 *    @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="Search by code,uuid, merchandiser name, customer name, etc",
 *         required=false,
 *         @OA\Schema(type="string", example="Amit")
 *     ),
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         required=false,
 *         description="Page number for pagination",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         required=false,
 *         description="Number of items per page",
 *         @OA\Schema(type="integer", example=10)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Paginated campaign information list",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="CampaignInformation fetched successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="merchandiser_id", type="integer", example=101),
 *                     @OA\Property(property="customer_id", type="integer", example=205),
 *                     @OA\Property(property="feedback", type="string", example="Customer responded positively."),
 *                     @OA\Property(property="date_time", type="string", format="date-time", example="2025-10-10T14:00:00Z"),
 *                     @OA\Property(property="image_1", type="string", example="campaign_images/image1.jpg"),
 *                     @OA\Property(property="image_2", type="string", example="campaign_images/image2.jpg"),
 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-10T15:00:00Z"),
 *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-10-10T15:00:00Z")
 *                 )
 *             ),
 *             @OA\Property(
 *                 property="meta",
 *                 type="object",
 *                 @OA\Property(property="current_page", type="integer", example=1),
 *                 @OA\Property(property="last_page", type="integer", example=5),
 *                 @OA\Property(property="per_page", type="integer", example=10),
 *                 @OA\Property(property="total", type="integer", example=50)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
 *         )
 *     )
 * )
 */

         public function index(Request $request)
    {
        $campaign = $this->service->getAll($request);
        return ResponseHelper::paginatedResponse(
        'CampaignInformation fetched successfully',
        CampaignInformationResource::class,
        $campaign
      );
    }

/**
 * @OA\Get(
 *     path="/api/merchendisher/campagin-info/exportfile",
 *     summary="Export campaign information to CSV or Excel",
 *     description="Exports the campaign information, optionally filtered by date range, and returns a downloadable file in CSV or Excel format.",
 *     operationId="exportCampaignInformation",
 *     tags={"Campaign Information"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="start_date",
 *         in="query",
 *         description="Start date for filtering (format: Y-m-d)",
 *         required=false,
 *         @OA\Schema(type="string", format="date", example="2025-08-09")
 *     ),
 *     @OA\Parameter(
 *         name="end_date",
 *         in="query",
 *         description="End date for filtering (format: Y-m-d)",
 *         required=false,
 *         @OA\Schema(type="string", format="date", example="2025-08-11")
 *     ),
 *     @OA\Parameter(
 *         name="format",
 *         in="query",
 *         description="Export format: csv or xlsx",
 *         required=false,
 *         @OA\Schema(type="string", enum={"csv", "xlsx"}, default="csv")
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="File download response",
 *         content={
 *             @OA\MediaType(
 *                 mediaType="application/octet-stream",
 *                 @OA\Schema(type="string", format="binary")
 *             )
 *         }
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 example={"start_date": {"The start date must be a valid date."}}
 *             )
 *         )
 *     )
 * )
 */

    public function export(Request $request)
{
    $request->validate([
        'start_date' => 'nullable|date_format:Y-m-d',
        'end_date'   => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
        'format'     => 'nullable|in:csv,xlsx',
    ]);

    $startDate = $request->input('start_date');
    $endDate   = $request->input('end_date');
    $format    = $request->input('format', 'csv'); // default to CSV

    return $this->service->export($startDate, $endDate, $format);
}
}