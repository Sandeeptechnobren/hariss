<?php

namespace App\Http\Controllers\V1\Merchendisher\Mob;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Merchendisher\Mob\SurveyHeaderRequest;
use App\Http\Resources\V1\Merchendisher\Mob\SurveyHeaderResource;
use App\Services\V1\Merchendisher\Mob\SurveyHeaderService;
use Illuminate\Http\JsonResponse;
/**
 * @OA\Tag(
 *     name="SurveyHeaders",
 *     description="API Endpoints of Survey Headers"
 * )
 */
class SurveyHeaderController extends Controller
{
    protected SurveyHeaderService $service;

    public function __construct(SurveyHeaderService $service)
    {
        $this->service = $service;
    }

    /**
     * Standard success response
     */
    protected function success(
        mixed $data = null,
        string $message = 'Success',
        int $code = 200,
        array $pagination = null
    ): JsonResponse {
        $response = [
            'status'  => 'success',
            'code'    => $code,
            'message' => $message,
            'data'    => $data,
        ];

        if ($pagination) {
            $response['pagination'] = $pagination;
        }

        return response()->json($response, $code);
    }
/**
 * @OA\Get(
 *     path="/mob/merchendisher_mob/survey-header/list",
 *     tags={"Survey Headers"},
 *     summary="Get all survey headers",
 *     description="Retrieve a paginated list of survey headers including merchandiser and survey details.",
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
 *         description="Survey headers retrieved successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Survey headers retrieved successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(
 *                         property="merchandiser",
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=5)
 *                     ),
 *                     @OA\Property(
 *                         property="survey",
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=3)
 *                     ),
 *                     @OA\Property(property="date", type="string", format="date", example="2025-09-27"),
 *                     @OA\Property(property="answerer_name", type="string", example="John Doe"),
 *                     @OA\Property(property="address", type="string", example="123 Main St"),
 *                     @OA\Property(property="phone", type="string", example="1234567890"),
 *                     @OA\Property(property="uuid", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
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
 *                 @OA\Property(property="total", type="integer", example=50),
 *                 @OA\Property(property="per_page", type="integer", example=10),
 *                 @OA\Property(property="current_page", type="integer", example=1),
 *                 @OA\Property(property="last_page", type="integer", example=5)
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
        $headers = $this->service->all();
        return $this->success(
            SurveyHeaderResource::collection($headers),
            'Survey headers retrieved successfully',
            200,
            [
                'total' => $headers->total(),
                'per_page' => $headers->perPage(),
                'current_page' => $headers->currentPage(),
                'last_page' => $headers->lastPage(),
            ]
        );
    }
/**
 * @OA\Get(
 *     path="/mob/merchendisher_mob/survey-header/{id}",
 *     tags={"Survey Headers"},
 *     summary="Get a single survey header",
 *     description="Retrieve a specific survey header by its ID, including merchandiser and survey details.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID of the survey header to retrieve",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Survey header retrieved successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Survey header retrieved successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(
 *                     property="merchandiser",
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=5)
 *                 ),
 *                 @OA\Property(
 *                     property="survey",
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=3)
 *                 ),
 *                 @OA\Property(property="date", type="string", format="date", example="2025-09-27"),
 *                 @OA\Property(property="answerer_name", type="string", example="John Doe"),
 *                 @OA\Property(property="address", type="string", example="123 Main St"),
 *                 @OA\Property(property="phone", type="string", example="1234567890"),
 *                 @OA\Property(property="uuid", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
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
 *         description="Survey header not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="code", type="integer", example=404),
 *             @OA\Property(property="message", type="string", example="Survey header not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     )
 * )
 */
    public function show($id): JsonResponse
    {
        $header = $this->service->getById($id);
        return $this->success(new SurveyHeaderResource($header));
    }

/**
 * @OA\Post(
 *     path="/mob/merchendisher_mob/survey-header/add",
 *     tags={"Survey Headers"},
 *     summary="Create a new survey header",
 *     description="Adds a new survey header entry with merchandiser and survey details.",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"merchandiser_id","survey_id","date","answerer_name"},
 *             type="object",
 *             @OA\Property(property="merchandiser_id", type="integer", example=5),
 *             @OA\Property(property="survey_id", type="integer", example=3),
 *             @OA\Property(property="date", type="string", format="date", example="2025-09-27"),
 *             @OA\Property(property="answerer_name", type="string", example="John Doe"),
 *             @OA\Property(property="address", type="string", example="123 Main St"),
 *             @OA\Property(property="phone", type="string", example="1234567890")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Survey header created successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Survey header created successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(
 *                     property="merchandiser",
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=5)
 *                 ),
 *                 @OA\Property(
 *                     property="survey",
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=3)
 *                 ),
 *                 @OA\Property(property="date", type="string", format="date", example="2025-09-27"),
 *                 @OA\Property(property="answerer_name", type="string", example="John Doe"),
 *                 @OA\Property(property="address", type="string", example="123 Main St"),
 *                 @OA\Property(property="phone", type="string", example="1234567890"),
 *                 @OA\Property(property="uuid", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
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
     public function store(SurveyHeaderRequest $request): JsonResponse
    {
        $header = $this->service->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Survey header created successfully',
            'data' => new SurveyHeaderResource($header)
        ], 201);
    }

/**
 * @OA\Put(
 *     path="/mob/merchendisher_mob/survey-header/{id}",
 *     tags={"Survey Headers"},
 *     summary="Update a survey header",
 *     description="Update the details of an existing survey header by its ID.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID of the survey header to update",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"merchandiser_id","survey_id","date","answerer_name"},
 *             type="object",
 *             @OA\Property(property="merchandiser_id", type="integer", example=5),
 *             @OA\Property(property="survey_id", type="integer", example=3),
 *             @OA\Property(property="date", type="string", format="date", example="2025-09-27"),
 *             @OA\Property(property="answerer_name", type="string", example="John Doe"),
 *             @OA\Property(property="address", type="string", example="123 Main St"),
 *             @OA\Property(property="phone", type="string", example="1234567890")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Survey header updated successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Survey header updated"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(
 *                     property="merchandiser",
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=5)
 *                 ),
 *                 @OA\Property(
 *                     property="survey",
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=3)
 *                 ),
 *                 @OA\Property(property="date", type="string", format="date", example="2025-09-27"),
 *                 @OA\Property(property="answerer_name", type="string", example="John Doe"),
 *                 @OA\Property(property="address", type="string", example="123 Main St"),
 *                 @OA\Property(property="phone", type="string", example="1234567890"),
 *                 @OA\Property(property="uuid", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
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
 *         response=404,
 *         description="Survey header not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Survey header not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     )
 * )
 */
    public function update(SurveyHeaderRequest $request, $id): JsonResponse
    {
        $header = $this->service->update($id, $request->validated());
        return $this->success(new SurveyHeaderResource($header), 'Survey header updated');
    }
/**
 * @OA\Delete(
 *     path="/ mob/merchendisher_mob/survey-header/{id}",
 *     tags={"Survey Headers"},
 *     summary="Delete a survey header",
 *     description="Deletes a specific survey header by its ID.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID of the survey header to delete",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Survey header deleted successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Survey header deleted"),
 *             @OA\Property(property="data", type="string", example=null)
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Survey header not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="code", type="integer", example=404),
 *             @OA\Property(property="message", type="string", example="Survey header not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     )
 * )
 */

    public function destroy($id): JsonResponse
    {
        $this->service->delete($id);
        return $this->success(null, 'Survey header deleted');
    }
/**
 * @OA\Get(
 *     path="/mob/merchendisher_mob/survey-header/Survey-list",
 *     summary="Export survey data for the authenticated merchandiser",
 *     tags={"Survey Headers"},
 *     operationId="getSurveyIdsFile",
 *     security={{"bearerAuth":{}}},
 * 
 *     @OA\Response(
 *         response=200,
 *         description="Survey data file generated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Survey data file generated successfully."),
 *             @OA\Property(property="file_url", type="string", example="https://example.com/storage/surveys/user_123_survey_ids.csv")
 *         )
 *     ),
 * 
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
 *         )
 *     ),
 * 
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="An error occurred while generating the file.")
 *         )
 *     )
 * )
 */
    public function getSurveyIdsFile(SurveyHeaderService $service): JsonResponse
{
    $fileUrl = $service->exportSurveyDataForAuthenticatedMerchandiser();

    return response()->json([
        'status' => true,
        'message' => 'Survey data file generated successfully.',
        'file_url' => asset($fileUrl),
    ]);
}
}