<?php

namespace App\Http\Controllers\V1\Merchendisher\Web;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Merchendisher\Web\SurveyQuestionRequest;
use App\Http\Resources\V1\Merchendisher\Web\SurveyQuestionResource;
use App\Models\SurveyQuestion;
use App\Services\V1\Merchendisher\Web\SurveyQuestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SurveyQuestionController extends Controller
{
    protected $service;

    public function __construct(SurveyQuestionService $service)
    {
        $this->service = $service;
    }
/**
 * @OA\Get(
 *     path="/api/merchendisher/survey-questions/list",
 *     tags={"Survey Questions"},
 *     summary="Get all survey questions",
 *     description="Fetches a paginated list of all survey questions with full details.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Page number for pagination",
 *         required=false,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful response",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Survey questions retrieved successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="uuid", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
 *                     @OA\Property(property="survey_question_code", type="string", example="SQ-001"),
 *                     @OA\Property(property="survey_id", type="integer", example=2),
 *                     @OA\Property(property="question", type="string", example="What is your favorite color?"),
 *                     @OA\Property(property="question_type", type="string", example="text"),
 *                     @OA\Property(property="question_based_selected", type="string", example="yes,no"),
 *                     @OA\Property(property="created_user", type="integer", example=1),
 *                     @OA\Property(property="updated_user", type="integer", example=2),
 *                     @OA\Property(property="deleted_user", type="integer", example=null),
 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-27T04:00:00Z"),
 *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-27T04:00:00Z"),
 *                     @OA\Property(property="deleted_at", type="string", format="date-time", example=null)
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
 *         description="Unauthorized"
 *     )
 * )
 */
  public function index(): JsonResponse
{
    $questions = $this->service->index(); 

    return response()->json([
        'success' => true,
        'message' => 'Survey questions retrieved successfully',
        'data' => SurveyQuestionResource::collection($questions),
        'meta' => [
            'current_page' => $questions->currentPage(),
            'last_page' => $questions->lastPage(),
            'per_page' => $questions->perPage(),
            'total' => $questions->total(),
        ],
    ], 200);
}
/**
 * @OA\Post(
 *     path="/api/merchendisher/survey-questions/add",
 *     tags={"Survey Questions"},
 *     summary="Create a new survey question",
 *     description="Adds a new survey question with all details.",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="survey_id", type="integer", example=2),
 *             @OA\Property(property="question", type="string", example="What is your favorite color?"),
 *             @OA\Property(property="question_type", type="string", example="check box,radio button,textbox,selectbox,comment box"),
 *             @OA\Property(property="question_based_selected", type="string", example="yes,no")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Survey question created successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Survey question created successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="uuid", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
 *                 @OA\Property(property="survey_question_code", type="string", example="SQ-001"),
 *                 @OA\Property(property="survey_id", type="integer", example=2),
 *                 @OA\Property(property="question", type="string", example="What is your favorite color?"),
 *                 @OA\Property(property="question_type", type="string", example="text"),
 *                 @OA\Property(property="question_based_selected", type="string", example="yes,no"),
 *                 @OA\Property(property="created_user", type="integer", example=1),
 *                 @OA\Property(property="updated_user", type="integer", example=2),
 *                 @OA\Property(property="deleted_user", type="integer", example=null),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-27T04:00:00Z"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-27T04:00:00Z"),
 *                 @OA\Property(property="deleted_at", type="string", format="date-time", example=null)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Validation failed"),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     )
 * )
 */
public function store(SurveyQuestionRequest $request): JsonResponse
    {
        $questions = $this->service->createMultiple($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Survey questions created successfully',
            'data' => SurveyQuestionResource::collection($questions),
        ], 201);
    }
/**
 * @OA\Get(
 *     path="/api/merchendisher/survey-questions/{id}",
 *     tags={"Survey Questions"},
 *     summary="Get a single survey question",
 *     description="Retrieve a specific survey question by its ID.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID of the survey question to retrieve",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Survey question retrieved successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Survey question retrieved successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="uuid", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
 *                 @OA\Property(property="survey_question_code", type="string", example="SQ-001"),
 *                 @OA\Property(property="survey_id", type="integer", example=2),
 *                 @OA\Property(property="question", type="string", example="What is your favorite color?"),
 *                 @OA\Property(property="question_type", type="string", example="text"),
 *                 @OA\Property(property="question_based_selected", type="string", example="yes,no"),
 *                 @OA\Property(property="created_user", type="integer", example=1),
 *                 @OA\Property(property="updated_user", type="integer", example=2),
 *                 @OA\Property(property="deleted_user", type="integer", example=null),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-27T04:00:00Z"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-27T04:00:00Z"),
 *                 @OA\Property(property="deleted_at", type="string", format="date-time", example=null)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Survey question not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Survey question not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     )
 * )
 */
   public function show(int $id): JsonResponse
{
    try {
        $question = $this->service->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Survey question retrieved successfully',
            'data' => new SurveyQuestionResource($question),
        ], 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Survey question not found',
        ], 404);
    }
}
/**
 * @OA\Put(
 *     path="/api/merchendisher/survey-questions/{id}",
 *     tags={"Survey Questions"},
 *     summary="Update a survey question",
 *     description="Update the details of an existing survey question by its ID.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID of the survey question to update",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="survey_id", type="integer", example=2),
 *             @OA\Property(property="question", type="string", example="What is your favorite color?"),
 *             @OA\Property(property="question_type", type="string", example="text"),
 *             @OA\Property(property="question_based_selected", type="string", example="yes,no")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Survey question updated successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Survey question updated successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="uuid", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
 *                 @OA\Property(property="survey_question_code", type="string", example="SQ-001"),
 *                 @OA\Property(property="survey_id", type="integer", example=2),
 *                 @OA\Property(property="question", type="string", example="What is your favorite color?"),
 *                 @OA\Property(property="question_type", type="string", example="text"),
 *                 @OA\Property(property="question_based_selected", type="string", example="yes,no"),
 *                 @OA\Property(property="created_user", type="integer", example=1),
 *                 @OA\Property(property="updated_user", type="integer", example=2),
 *                 @OA\Property(property="deleted_user", type="integer", example=null),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-27T04:00:00Z"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-27T04:00:00Z"),
 *                 @OA\Property(property="deleted_at", type="string", format="date-time", example=null)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Survey question not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Survey question not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Validation failed"),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     )
 * )
 */
    public function update(SurveyQuestionRequest $request, int $id): JsonResponse
    {
        $question = SurveyQuestion::find($id);

        if (!$question) {
            return response()->json([
                'success' => false,
                'message' => 'Survey question not found',
            ], 404);
        }

        $updatedQuestion = $this->service->update($question, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Survey question updated successfully',
            'data' => new SurveyQuestionResource($updatedQuestion),
        ], 200);
    }
/**
 * @OA\Delete(
 *     path="/api/merchendisher/survey-questions/{id}",
 *     tags={"Survey Questions"},
 *     summary="Delete a survey question",
 *     description="Deletes a specific survey question by its ID.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID of the survey question to delete",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Survey question deleted successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Survey question deleted successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Survey question not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Survey question not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     )
 * )
 */
  
    public function destroy(int $id): JsonResponse
    {
        $question = SurveyQuestion::find($id);

        if (!$question) {
            return response()->json([
                'success' => false,
                'message' => 'Survey question not found',
            ], 404);
        }

        $this->service->delete($question);

        return response()->json([
            'success' => true,
            'message' => 'Survey question deleted successfully',
        ], 200);
    }
    /**
     * @OA\Get(
     *     path="/api/merchendisher/survey-questions/global-search",
     *     summary="Global search for survey questions",
     *     description="Search survey questions by code, question, type, selected options, or created/updated/deleted users",
     *     tags={"Survey Questions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term for survey questions and user fields",
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
     *         description="Successful response with paginated survey questions",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Survey questions retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="uuid", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                     @OA\Property(property="survey_question_code", type="string", example="SQ-001"),
     *                     @OA\Property(property="survey_id", type="integer", example=2),
     *                     @OA\Property(property="question", type="string", example="What is your favorite color?"),
     *                     @OA\Property(property="question_type", type="string", example="text"),
     *                     @OA\Property(property="question_based_selected", type="string", example="yes,no"),
     *                     @OA\Property(property="created_user", type="object",
     *                         @OA\Property(property="id", type="integer", example=4),
     *                         @OA\Property(property="firstname", type="string", example="John"),
     *                         @OA\Property(property="lastname", type="string", example="Doe"),
     *                         @OA\Property(property="username", type="string", example="johndoe")
     *                     ),
     *                     @OA\Property(property="updated_user", type="object",
     *                         @OA\Property(property="id", type="integer", example=5),
     *                         @OA\Property(property="firstname", type="string", example="Jane"),
     *                         @OA\Property(property="lastname", type="string", example="Smith"),
     *                         @OA\Property(property="username", type="string", example="janesmith")
     *                     ),
     *                     @OA\Property(property="deleted_user", type="object",
     *                         @OA\Property(property="id", type="integer", example=null),
     *                         @OA\Property(property="firstname", type="string", example=null),
     *                         @OA\Property(property="lastname", type="string", example=null),
     *                         @OA\Property(property="username", type="string", example=null)
     *                     ),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-27T04:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-27T04:00:00Z"),
     *                     @OA\Property(property="deleted_at", type="string", format="date-time", example=null)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=50),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="to", type="integer", example=10)
     *             )
     *         )
     *     )
     * )
     */ 
        public function globalSearch(Request $request): JsonResponse
    {
        $searchTerm = $request->input('search');
        $perPage    = $request->input('per_page', 10);

        $questions = $this->service->globalSearch($searchTerm, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Survey questions retrieved successfully',
            'data'    => SurveyQuestionResource::collection($questions),
            'meta'    => [
                'current_page' => $questions->currentPage(),
                'last_page'    => $questions->lastPage(),
                'per_page'     => $questions->perPage(),
                'total'        => $questions->total(),
            ],
        ]);
    }
/**
 * @OA\Get(
 *     path="/web/merchendisher_web/survey-questions/get/{survey_id}",
 *     summary="Get survey questions by survey ID",
 *     tags={"Survey Questions"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="survey_id",
 *         in="path",
 *         description="ID of the survey",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful response",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="question", type="string", example="What is your name?")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Survey not found or no questions"
 *     )
 * )
 */
      public function getBySurveyId($survey_id): JsonResponse
    {
        $questions = $this->service->getQuestionsBySurveyId($survey_id);

        return response()->json([
            'success' => true,
            'message' => 'Survey questions retrieved successfully',
            'data' => $questions,
        ]);
    }

    /**
 * @OA\Post(
 *     path="/web/merchendisher_web/survey-questions/bluckupload",
 *     summary="Import survey questions from Excel or CSV file",
 *     description="Imports survey questions with validation. Accepts .csv, .xlsx, .xls files. Automatically generates UUID and question code if not provided.",
 *     operationId="importSurveyQuestions",
 *     tags={"Survey Questions"},
 *     security={{"bearerAuth":{}}}, 
 *
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
 *                     description="The CSV or Excel file containing survey questions"
 *                 )
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Import completed with success and failure results",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Survey questions import completed."),
 *             @OA\Property(
 *                 property="success",
 *                 type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="row", type="integer", example=3),
 *                     @OA\Property(property="message", type="string", example="Inserted successfully"),
 *                     @OA\Property(
 *                         property="data",
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=61),
 *                         @OA\Property(property="uuid", type="string", example="e3c1d210-f726-48b6-b3fc-75a99939bc7c"),
 *                         @OA\Property(property="survey_id", type="integer", example=106),
 *                         @OA\Property(property="survey_question_code", type="string", example="QW4JJU2IT"),
 *                         @OA\Property(property="question", type="string", example="What is alternative data?"),
 *                         @OA\Property(property="question_type", type="string", example="comment box"),
 *                         @OA\Property(property="question_based_selected", type="string", example="yes,no,why")
 *                     )
 *                 )
 *             ),
 *             @OA\Property(
 *                 property="failed",
 *                 type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="row", type="integer", example=4),
 *                     @OA\Property(property="error", type="string", example="Survey ID 999 not found or is soft-deleted.")
 *                 )
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=422,
 *         description="Validation failed",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 @OA\Property(
 *                     property="file",
 *                     type="array",
 *                     @OA\Items(type="string", example="The file field is required.")
 *                 )
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=500,
 *         description="Import failed due to a server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Import failed: SQLSTATE[22001]...")
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
            $results = $this->service->importSurveyQuestions($request->file('file'));

            return response()->json([
                'message' => 'Survey questions import completed.',
                'success' => $results['success'],
                'failed'  => $results['failed'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

/**
 * @OA\Get(
 *     path="/web/merchendisher_web/survey-questions/exportfile",
  *     summary="Export survey questions as CSV or XLSX",
 *     tags={"Survey Questions"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="format",
 *         in="query",
 *         required=true,
 *         description="Export format (csv or xlsx)",
 *         @OA\Schema(
 *             type="string",
 *             enum={"csv", "xlsx"}
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Exported file (CSV or XLSX)",
 *         content={
 *             @OA\MediaType(
 *                 mediaType="application/octet-stream",
 *                 @OA\Schema(type="string", format="binary")
 *             )
 *         }
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Bad request",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="message", type="string", example="No survey questions found.")
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
 *                 additionalProperties=@OA\Property(
 *                     type="array",
 *                     @OA\Items(type="string")
 *                 )
 *             )
 *         )
 *     )
 * )
 */


      public function export(Request $request)
    {
        $request->validate([
            'format'     => 'required|in:csv,xlsx',
        ]);

        try {
            return $this->service->exportSurveyQuestions(
                $request->format,
                $request->valid_from,
                $request->valid_to
            );
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}