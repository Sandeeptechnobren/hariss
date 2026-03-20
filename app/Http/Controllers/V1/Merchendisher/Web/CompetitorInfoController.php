<?php

namespace App\Http\Controllers\V1\Merchendisher\Web;

use App\Http\Controllers\Controller;
use App\Models\CompetitorInfo;
use App\Services\V1\Merchendisher\Web\CompetitorInfoService;
use App\Http\Resources\V1\Merchendisher\Web\CompetitorInfoResource;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use App\Http\Requests\V1\Merchendisher\Web\CompetitorInfoRequest;
use Illuminate\Http\JsonResponse;
use App\Exports\CompetitorInfoExport;
use Maatwebsite\Excel\Facades\Excel;

class CompetitorInfoController extends Controller
{
    protected $service;

    public function __construct(CompetitorInfoService $service)
    {
        $this->service = $service;
    }
    /**
     * @OA\Get(
     *     path="/api/merchendisher/competitor-info/show/{uuid}",
     *     summary="Get competitor info by UUID",
     *     description="Retrieve a single competitor's information using UUID.",
     *     operationId="getCompetitorInfoByUuid",
     *     tags={"Competitor Info"},
     * security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         description="UUID of the competitor",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Competitor retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Competitor retrieved successfully"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="uuid", type="string", format="uuid", example="7a5e8d44-1234-abc9-9999-exampleuuid"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-01 14:22:33"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-10-04 11:07:09")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Competitor not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Competitor not found")
     *         )
     *     )
     * )
     */
        public function show($uuid)
    {
         $competitorinfo = $this->service->getByUuid($uuid);

    if (!$competitorinfo) {
        return response()->json([
            'message' => 'No competitors found',
            'code' => 200,
            'data' => null,
        ]);
    }

    return response()->json([
        'message' => 'Competitor retrieved successfully',
        'code' => 200,
        'data' => new CompetitorInfoResource($competitorinfo),
       ]);
    }
    /**
     * @OA\Get(
     *     path="/api/merchendisher/competitor-info/list",
     *     summary="Get list of competitor infos",
     *     description="Returns a paginated list of competitors",
     *     operationId="getCompetitorInfos",
     *     tags={"Competitor Info"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by company_name,uuid, merchandiser name, item name, etc",
     *         required=false,
     *         @OA\Schema(type="string", example="Amit")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Competitor fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Competitor fetched successfully"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=50),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="uuid", type="string", format="uuid", example="7a5e8d44-1234-abc9-9999-exampleuuid"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-01 14:22:33"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-10-04 11:07:09")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
            public function index()
    {
        $competitorinfo = $this->service->getAll();
        return ResponseHelper::paginatedResponse(
        'Competitor fetched successfully',
        CompetitorInfoResource::class,
        $competitorinfo
      );
    }
/**
 * @OA\Post(
 *     path="/mob/merchendisher_mob/compititer/create",
 *     summary="Store a new Competitor Info",
 *     tags={"Competitor Info"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"company_name","brand","merchendiser_id","item_name","price","image1","image2","promotion","notes"},
 *                 @OA\Property(property="company_name", type="string", example="Nestlé"),
 *                 @OA\Property(property="brand", type="string", example="Milo"),
 *                 @OA\Property(property="merchendiser_id", type="integer", example=75),
 *                 @OA\Property(property="item_name", type="string", example="Milo 3-in-1"),
 *                 @OA\Property(property="price", type="number", format="float", example=12.50),
 *                 @OA\Property(property="promotion", type="string", example="Buy 1 Get 1 Free"),
 *                 @OA\Property(property="notes", type="string", example="Promo valid until next week"),
 *                 @OA\Property(property="image1", type="string", format="binary", description="Primary image file"),
 *                 @OA\Property(property="image2", type="string", format="binary", description="Secondary image file"),
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Competitor info created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Competitor info created successfully."),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="company_name", type="string", example="Nestlé"),
 *                 @OA\Property(property="brand", type="string", example="Milo"),
 *                 @OA\Property(property="merchendiser_id", type="integer", example=75),
 *                 @OA\Property(property="item_name", type="string", example="Milo 3-in-1"),
 *                 @OA\Property(property="price", type="number", format="float", example=12.50),
 *                 @OA\Property(property="promotion", type="string", example="Buy 1 Get 1 Free"),
 *                 @OA\Property(property="notes", type="string", example="Promo valid until next week"),
 *                 @OA\Property(property="image1", type="string", example="competitor_images/sample1.jpg"),
 *                 @OA\Property(property="image2", type="string", example="competitor_images/sample2.jpg"),
 *                 @OA\Property(property="created_by", type="integer", example=5),
 *                 @OA\Property(property="updated_by", type="integer", example=5),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-10T20:17:08Z"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-10-10T20:17:08Z")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation or business rule failed",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The selected merchandiser is invalid or unauthorized.")
 *         )
 *     )
 * )
 */
    public function store(CompetitorInfoRequest $request, CompetitorInfoService $service): JsonResponse
{
    $result = $service->store($request);

    if (!$result['success']) {
        return response()->json([
            'message' => $result['message']
        ], 422); 
    }

    return response()->json([
        'message' => $result['message'],
        'data' => $result['data']
    ], 201);
}
/**
 * @OA\Get(
 *     path="/api/merchendisher/competitor-info/exportfile",
 *     summary="Export competitor info data filtered by optional date range",
 *     description="Exports competitor info data joined with merchandiser name, filtered optionally by created_at date range. Supports CSV and Excel formats.",
 *     operationId="exportCompetitorInfo",
 *     tags={"Competitor Info"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="start_date",
 *         in="query",
 *         description="Start date filter (optional) in YYYY-MM-DD format",
 *         required=false,
 *         @OA\Schema(type="string", format="date", example="2025-08-09")
 *     ),
 *     @OA\Parameter(
 *         name="end_date",
 *         in="query",
 *         description="End date filter (optional) in YYYY-MM-DD format; must be equal or after start_date",
 *         required=false,
 *         @OA\Schema(type="string", format="date", example="2025-09-10")
 *     ),
 *     @OA\Parameter(
 *         name="format",
 *         in="query",
 *         description="Export file format (required). Allowed values: csv, xlsx",
 *         required=true,
 *         @OA\Schema(type="string", enum={"csv", "xlsx"}, example="csv")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="File download response",
 *         @OA\MediaType(
 *             mediaType="application/octet-stream",
 *             example="Binary file download"
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 example={
 *                     "start_date": {"The start date is not a valid date."},
 *                     "format": {"The selected format is invalid."}
 *                 }
 *             )
 *         )
 *     ),
 * )
 */
public function export(Request $request)
{
    $request->validate([
        'start_date' => 'nullable|date',
        'end_date'   => 'nullable|date|after_or_equal:start_date',
        'format'     => 'required|in:csv,xlsx',
    ]);

    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');
    $format = $request->input('format');

    $data = $this->service->getFilteredData($startDate, $endDate);

    $fileName = "competitor_infos";

    if ($startDate && $endDate) {
        $fileName .= "_{$startDate}_to_{$endDate}";
    }

    $fileName .= "." . $format;

    return Excel::download(new CompetitorInfoExport($data), $fileName);
}
}