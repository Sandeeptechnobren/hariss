<?php

namespace App\Http\Controllers\V1\Merchendisher\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Merchendisher\Web\SurveyRequest;
use App\Http\Resources\V1\Merchendisher\Web\SurveyResource;
use App\Http\Resources\V1\Merchendisher\Web\SurveyShowResource;
use App\Models\Survey;
use App\Services\V1\Merchendisher\Web\SurveyService;
use Illuminate\Http\JsonResponse;
use App\Imports\SurveyImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request; 
use Maatwebsite\Excel\Excel as ExcelFormat;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

/**
 * @OA\Tag(name="Surveys", description="API Endpoints for Surveys")
 */
class SurveyController extends Controller
{
    protected SurveyService $service;

    public function __construct(SurveyService $service)
    {
        $this->service = $service;
    }
/**
 * @OA\Get(
 *     path="/api/merchendisher/survey/list",
 *     tags={"Surveys"},
 *     summary="List all surveys",
 *     description="Fetches a list of all surveys with their details.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Surveys fetched successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Surveys fetched successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="survey_code", type="string", example="S-68D71512B4230"),
 *                     @OA\Property(property="uuid", type="string", format="uuid", example="87e675ff-e936-43ba-8df6-6799cb9b913a"),
 *                     @OA\Property(property="survey_name", type="string", example="Employee Engagement Survey"),
 *                     @OA\Property(property="start_date", type="string", format="date", example="2025-11-01"),
 *                     @OA\Property(property="end_date", type="string", format="date", example="2025-11-30"),
 *                     @OA\Property(property="status", type="string", example="active"),
 *                     @OA\Property(property="status_value", type="string", example="1"),
 *                     @OA\Property(property="created_user", type="integer", example=5),
 *                     @OA\Property(property="updated_user", type="integer", example=5),
 *                     @OA\Property(property="deleted_user", type="integer", nullable=true, example=null),
 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-26T22:34:58.000000Z"),
 *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-26T22:34:58.000000Z"),
 *                     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *                 )
 *             )
 *         )
 *     )
 * )
 */
public function index(): JsonResponse
{
    $surveys = $this->service->list();

    $message = $surveys->isEmpty() 
        ? 'Surveys not found' 
        : 'Surveys retrieved successfully';

    return response()->json([
        'success' => true,
        'message' => $message,
        'data' => SurveyResource::collection($surveys),
        'pagination' => [
            'total' => $surveys->total(),
            'per_page' => $surveys->perPage(),
            'current_page' => $surveys->currentPage(),
            'last_page' => $surveys->lastPage(),
            'from' => $surveys->firstItem(),
            'to' => $surveys->lastItem(),
        ],
    ]);
}
/**
 * @OA\Post(
 *     path="/api/merchendisher/survey/add",
 *     tags={"Surveys"},
 *     summary="Create a new survey",
 *     description="Creates a new survey and returns survey details",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             required={"survey_name","start_date","end_date","status"},
 *
 *             @OA\Property(property="survey_name", type="string", example="Employee Engagement Survey"),
 *             @OA\Property(property="survey_type", type="integer", example=1),
 *
 *             @OA\Property(
 *                 property="merchandisher_id",
 *                 type="array",
 *                 @OA\Items(type="integer"),
 *                 example="[1,2,3]"
 *             ),
 *
 *             @OA\Property(
 *                 property="customer_id",
 *                 type="array",
 *                 @OA\Items(type="integer"),
 *                 example="[10,12]"
 *             ),
 *
 *             @OA\Property(
 *                 property="asset_id",
 *                 type="array",
 *                 @OA\Items(type="integer"),
 *                 example="[7,8,9]"
 *             ),
 *
 *             @OA\Property(property="start_date", type="string", example="2025-11-01"),
 *             @OA\Property(property="end_date", type="string", example="2025-11-30"),
 *
 *             @OA\Property(property="status", type="string", example="active"),
 *             @OA\Property(property="survey_code", type="string", example="S-68D71512B4230")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Survey created successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Survey created successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="survey_code", type="string", example="S-68D71512B4230"),
 *                 @OA\Property(property="uuid", type="string", example="87e675ff-e936-43ba-8df6-6799cb9b913a"),
 *                 @OA\Property(property="survey_name", type="string", example="Employee Engagement Survey"),
 *                 @OA\Property(property="survey_type", type="integer", example=1),
 *
 *                 @OA\Property(property="merchandisher_id", type="string", example="1,2,3"),
 *                 @OA\Property(property="customer_id", type="string", example="10,12"),
 *                 @OA\Property(property="asset_id", type="string", example="7,8,9"),
 *
 *                 @OA\Property(property="start_date", type="string", example="2025-11-01"),
 *                 @OA\Property(property="end_date", type="string", example="2025-11-30"),
 *                 @OA\Property(property="status", type="string", example="active"),
 *                 @OA\Property(property="status_value", type="string", example="1"),
 *
 *                 @OA\Property(property="created_at", type="string", example="2025-09-26 22:34:58"),
 *                 @OA\Property(property="updated_at", type="string", example="2025-09-26 22:34:58")
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=422,
 *         description="Validation error"
 *     )
 * )
 */

public function store(SurveyRequest $request): JsonResponse
{
    $survey = $this->service->create($request->validated());
    return response()->json([
        'success' => true,
        'message' => 'Survey created successfully',
        'data'    => new SurveyResource($survey),
    ]);
}
/**
 * @OA\Get(
 *     path="/api/merchendisher/survey/{uuid}",
 *     tags={"Surveys"},
 *     summary="Get a single survey",
 *     description="Fetches details of a specific survey by its ID.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="uuid",
 *         in="path",
 *         description="UUID of the survey to fetch",
 *         required=true,
 *         @OA\Schema(type="string", example="87e675ff-e936-43ba-8df6-6799cb9b913a")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Survey fetched successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Survey fetched successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="survey_code", type="string", example="S-68D71512B4230"),
 *                 @OA\Property(property="uuid", type="string", format="uuid", example="87e675ff-e936-43ba-8df6-6799cb9b913a"),
 *                 @OA\Property(property="survey_name", type="string", example="Employee Engagement Survey"),
 *                 @OA\Property(property="start_date", type="string", format="date", example="2025-11-01"),
 *                 @OA\Property(property="end_date", type="string", format="date", example="2025-11-30"),
 *                 @OA\Property(property="status", type="string", example="active"),
 *                 @OA\Property(property="status_value", type="string", example="1"),
 *                 @OA\Property(property="created_user", type="integer", example=5),
 *                 @OA\Property(property="updated_user", type="integer", example=5),
 *                 @OA\Property(property="deleted_user", type="integer", nullable=true, example=null),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-26T22:34:58.000000Z"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-26T22:34:58.000000Z"),
 *                 @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Survey not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Survey not found")
 *         )
 *     )
 * )
 */
    public function show(string $uuid): JsonResponse
    {
        $survey = Survey::where('uuid', $uuid)->with('questions')->first();

        if (!$survey) {
            return response()->json([
                'success' => false,
                'message' => 'Survey not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Survey fetched successfully',
            'data' => new SurveyShowResource($survey),
        ]);
    }
/**
 * @OA\Put(
 *     path="/api/merchendisher/survey/{id}",
 *     tags={"Surveys"},
 *     summary="Update a survey",
 *     description="Updates a survey by its ID and returns the updated survey details.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID of the survey to update",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             required={"survey_name","start_date","end_date","status"},
 *             @OA\Property(property="survey_name", type="string", example="Employee Engagement Survey Updated"),
 *             @OA\Property(property="start_date", type="string", format="date", example="2025-11-01"),
 *             @OA\Property(property="end_date", type="string", format="date", example="2025-11-30"),
 *             @OA\Property(property="status", type="string", enum={"active","inactive"}, example="active")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Survey updated successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Survey updated successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="survey_code", type="string", example="S-68D71512B4230"),
 *                 @OA\Property(property="uuid", type="string", format="uuid", example="87e675ff-e936-43ba-8df6-6799cb9b913a"),
 *                 @OA\Property(property="survey_name", type="string", example="Employee Engagement Survey Updated"),
 *                 @OA\Property(property="start_date", type="string", format="date", example="2025-11-01"),
 *                 @OA\Property(property="end_date", type="string", format="date", example="2025-11-30"),
 *                 @OA\Property(property="status", type="string", example="active"),
 *                 @OA\Property(property="status_value", type="string", example="1"),
 *                 @OA\Property(property="created_user", type="integer", example=5),
 *                 @OA\Property(property="updated_user", type="integer", example=5),
 *                 @OA\Property(property="deleted_user", type="integer", nullable=true, example=null),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-26T22:34:58.000000Z"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-26T22:34:58.000000Z"),
 *                 @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Survey not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Survey not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 additionalProperties=@OA\Property(type="array", @OA\Items(type="string"))
 *             )
 *         )
 *     )
 * )
 */
public function update(SurveyRequest $request, $id): JsonResponse
{
    $survey = $this->service->update($id, $request->validated());

    return response()->json([
        'success' => true,
        'message' => 'Survey updated successfully',
        'data'    => new SurveyResource($survey),
    ]);
}

/**
 * @OA\Delete(
 *     path="/api/merchendisher/survey/{id}",
 *     tags={"Surveys"},
 *     summary="Delete a survey",
 *     description="Deletes a survey by its ID.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID of the survey to delete",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Survey deleted successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Survey deleted successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Survey not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Survey not found")
 *         )
 *     )
 * )
 */
public function destroy(int $id): JsonResponse
{
    $survey = Survey::find($id);

    if (!$survey) {
        return response()->json([
            'success' => false,
            'message' => 'Survey not found',
        ], 404);
    }

    $survey->delete();

    return response()->json([
        'success' => true,
        'message' => 'Survey deleted successfully',
    ]);
}
/**
     * @OA\Get(
     *     path="/api/merchendisher/survey/global-search",
     *     summary="Global search for surveys",
     *     description="Search surveys by code, name, status, dates, or created/updated/deleted users",
     *     tags={"Surveys"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term for surveys",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with paginated surveys",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Surveys retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=21),
     *                     @OA\Property(property="survey_code", type="string", example="sur8547"),
     *                     @OA\Property(property="uuid", type="string", example="0b240036-40b5-408c-9bc9-fa6b55bd06a6"),
     *                     @OA\Property(property="survey_name", type="string", example="west data Data"),
     *                     @OA\Property(property="start_date", type="string", format="date", example="2025-09-01"),
     *                     @OA\Property(property="end_date", type="string", format="date", example="2025-09-30"),
     *                     @OA\Property(property="status", type="string", example="active"),
     *                     @OA\Property(property="created_user", type="object",
     *                         @OA\Property(property="id", type="integer", example=4),
     *                         @OA\Property(property="name", type="string", example="John Doe")
     *                     ),
     *                     @OA\Property(property="updated_user", type="object",
     *                         @OA\Property(property="id", type="integer", example=4),
     *                         @OA\Property(property="name", type="string", example="John Doe")
     *                     ),
     *                     @OA\Property(property="deleted_user", type="object",
     *                         @OA\Property(property="id", type="integer", example=null),
     *                         @OA\Property(property="name", type="string", example=null)
     *                     ),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-29T05:27:43.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-29T05:28:09.000000Z"),
     *                     @OA\Property(property="deleted_at", type="string", format="date-time", example=null)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=3),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=1),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="to", type="integer", example=3)
     *             )
     *         )
     *     )
     * )
     */
  public function globalSearch(Request $request): JsonResponse
    {
        $searchTerm = $request->input('search');
        $perPage = $request->input('per_page', 50);

        $surveys = $this->service->globalSearch($searchTerm, $perPage);

        $message = $surveys->isEmpty() 
            ? 'Surveys not found' 
            : 'Surveys retrieved successfully';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => SurveyResource::collection($surveys),
            'pagination' => [
                'total' => $surveys->total(),
                'per_page' => $surveys->perPage(),
                'current_page' => $surveys->currentPage(),
                'last_page' => $surveys->lastPage(),
                'from' => $surveys->firstItem(),
                'to' => $surveys->lastItem(),
            ],
        ]);
    }
/**
 * @OA\Post(
 *     path="/web/merchendisher_web/survey/importsurvey",
 *     summary="Import surveys from a file (CSV, XLSX, XLS)",
 *     tags={"Surveys"},
 *     security={{"bearerAuth":{}}},
 *     operationId="importSurveys",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"file"},
 *                 @OA\Property(
 *                     property="file",
 *                     type="string",
 *                     format="binary",
 *                     description="The file to upload (CSV, XLSX, XLS)"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Surveys imported successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Surveys imported successfully.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error (missing or invalid file)",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The file field is required."),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 @OA\Property(
 *                     property="file",
 *                     type="array",
 *                     @OA\Items(type="string", example="The file must be a file of type: csv, xlsx, xls.")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error during import",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Error message from exception.")
 *         )
 *     )
 * )
 */
     public function import(Request $request)
    {
        $request->validate([
        'file' => 'required|file|mimes:csv,xlsx,xls',
    ]);

    try {
        Excel::import(new SurveyImport, $request->file('file'));
        return response()->json(['message' => 'Surveys imported successfully.']);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
    }

/**
 * @OA\Get(
 *     path="/api/merchendisher/survey/survey-export",
 *     summary="Export survey data in CSV or XLSX format",
 *     description="Download all survey records optionally filtered by date range (start_date to end_date). Returns a downloadable file.",
 *     tags={"Surveys"},
 *     @OA\Parameter(
 *         name="format",
 *         in="query",
 *         required=true,
 *         description="Export file format (csv or xlsx)",
 *         @OA\Schema(type="string", enum={"csv","xlsx"}, example="csv")
 *     ),
 *     @OA\Parameter(
 *         name="valid_from",
 *         in="query",
 *         required=false,
 *         description="Filter surveys starting from this date",
 *         @OA\Schema(type="string", format="date", example="2025-01-01")
 *     ),
 *     @OA\Parameter(
 *         name="valid_to",
 *         in="query",
 *         required=false,
 *         description="Filter surveys ending at this date",
 *         @OA\Schema(type="string", format="date", example="2025-12-31")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="File downloaded successfully",
 *         @OA\MediaType(
 *             mediaType="application/octet-stream",
 *             @OA\Schema(type="string", format="binary")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="No data found for the given date range",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="message", type="string", example="No data found for the given date range.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="The format field is required.")
 *         )
 *     ),
 *     security={{"bearerAuth": {}}}
 * )
 */

    public function export(Request $request)
    {
        $request->validate([
            'format'     => 'required|in:csv,xlsx',
            'valid_from' => 'nullable|date',
            'valid_to'   => 'nullable|date|after_or_equal:valid_from',
        ]);
        $surveys = $this->service->getFiltered(
            $request->valid_from,
            $request->valid_to
        );

        if ($surveys->isEmpty()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No data found for the given date range.'
            ], 404);
        }

        $data = $surveys->map(function ($item) {
        $surveyTypeMap = [
                1 => 'Consumer',
                2 => 'Sensory',
                3 => 'Asset',
            ];
            return [
                'survey_name'  => $item->survey_name ?? 'N/A',
                'survey_code'  => $item->survey_code ?? 'N/A',
                'start_date'    => $item->start_date ?? 'N/A',
                'end_date'      => $item->end_date,
                'survey_type'  => $surveyTypeMap[$item->survey_type] ?? 'N/A',
                'merchandiser' => $item->merchandishers->pluck('name')->implode(', ') ?: 'N/A',
                'customer'     => $item->customers->pluck('business_name')->implode(', ') ?: 'N/A',
                'asset'        => $item->assets->pluck('serial_number')->implode(', ') ?: 'N/A',
                'Status'      => $item->status == 1 ? 'Active' : 'Inactive',
            ];
        });
       $fileName = 'survey_list_' . now()->format('Y_m_d_H_i_s');
        $path = 'survey_export/';
        if ($request->format === 'csv') {
            $fileName .= '.csv';
            $fullPath = $path . $fileName;
            Storage::disk('public')->put($fullPath, '');
            $handle = fopen(storage_path('app/public/' . $fullPath), 'w');
            fputcsv($handle, array_keys($data->first()));
            foreach ($data as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        } else {
            $fileName .= '.xlsx';
            $fullPath = $path . $fileName;
            Excel::store(
                new class($data) implements 
                    \Maatwebsite\Excel\Concerns\FromCollection,
                    \Maatwebsite\Excel\Concerns\WithHeadings {
                    private $data;
                    public function __construct($data) {
                        $this->data = $data;
                    }
                    public function collection() {
                        return $this->data;
                    }
                    public function headings(): array {
                        return array_keys($this->data->first());
                    }
                },
                $fullPath,
                'public',
                ExcelFormat::XLSX
            );
        }
        return response()->json([
        'success' => true,
        'file_name' => $fileName,
        'file_url' => asset('storage/survey_export/' . $fileName),
    ]);
    }
}