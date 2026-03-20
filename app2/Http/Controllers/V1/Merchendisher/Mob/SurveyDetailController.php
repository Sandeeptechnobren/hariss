<?php

namespace App\Http\Controllers\V1\Merchendisher\Mob;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Merchendisher\Mob\SurveyDetailRequest;
use App\Http\Resources\V1\Merchendisher\Mob\SurveyDetailResource;
use App\Services\V1\Merchendisher\Mob\SurveyDetailService;
use App\Http\Requests\V1\Merchendisher\Mob\SurveyDetailListRequest;
use App\Http\Resources\V1\Merchendisher\Mob\SurveyDetailListResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request; 
use App\Exports\SurveyDetailsExport;
use Maatwebsite\Excel\Facades\Excel;

class SurveyDetailController extends Controller
{
    protected SurveyDetailService $service;

    public function __construct(SurveyDetailService $service)
    {
        $this->service = $service;
    }
/**
 * @OA\Post(
 *     path="/mob/merchendisher_mob/survey-detail/add",
 *     tags={"Survey Details"},
 *     summary="Create a new survey detail",
 *     description="Adds a new survey detail entry for a specific survey question.",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"header_id","question_id","answer"},
 *             type="object",
 *             @OA\Property(property="header_id", type="integer", example=4),
 *             @OA\Property(property="question_id", type="integer", example=4),
 *             @OA\Property(property="answer", type="string", example="This is the answer to the survey question.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Survey detail created successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="code", type="integer", example=201),
 *             @OA\Property(property="message", type="string", example="Survey detail created"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=2),
 *                 @OA\Property(property="uuid", type="string", example="dc8a6089-2974-4f68-b4e9-ea16ea42dd1b"),
 *                 @OA\Property(property="header_id", type="integer", example=4),
 *                 @OA\Property(property="question_id", type="integer", example=4),
 *                 @OA\Property(property="answer", type="string", example="This is the answer to the survey question."),
 *                 @OA\Property(property="created_user", type="integer", example=5),
 *                 @OA\Property(property="updated_user", type="integer", example=5),
 *                 @OA\Property(property="deleted_user", type="integer", example=null),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-28T00:32:51.000000Z"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-28T00:32:51.000000Z")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="code", type="integer", example=422),
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
    public function store(SurveyDetailRequest $request): JsonResponse
    {
        $detail = $this->service->create($request->validated());

        return $this->success(new SurveyDetailResource($detail), 'Survey detail created', 201);
    }
/**
 * @OA\Get(
 *     path="/mob/merchendisher_mob/survey-detail/details/{header_id}",
 *     operationId="getSurveyDetails",
 *     tags={"Survey Details"},
 *     summary="Get paginated survey details by header ID",
 *     description="Returns a paginated list of survey details for the given survey header.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="header_id",
 *         in="path",
 *         required=true,
 *         description="ID of the survey header",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         required=false,
 *         description="Number of items per page (optional, default 10)",
 *         @OA\Schema(type="integer", default=10)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Survey details retrieved successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Survey details retrieved successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="header_id", type="integer", example=4),
 *                     @OA\Property(property="question_id", type="integer", example=2),
 *                     @OA\Property(property="answer", type="string", example="Yes"),
 *                     @OA\Property(
 *                         property="question",
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=2),
 *                         @OA\Property(property="question", type="string", example="Did you like the product?")
 *                     )
 *                 )
 *             ),
 *             @OA\Property(
 *                 property="pagination",
 *                 type="object",
 *                 @OA\Property(property="current_page", type="integer", example=1),
 *                 @OA\Property(property="last_page", type="integer", example=5),
 *                 @OA\Property(property="per_page", type="integer", example=10),
 *                 @OA\Property(property="total", type="integer", example=50)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid header ID or parameters",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="code", type="integer", example=400),
 *             @OA\Property(property="message", type="string", example="Header ID is required."),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 @OA\Property(
 *                     property="header_id",
 *                     type="array",
 *                     @OA\Items(type="string", example="Header ID is required.")
 *                 )
 *             )
 *         )
 *     )
 * )
 */
    public function getList(SurveyDetailListRequest $request): JsonResponse
    {
        $data = $request->validated();

        $details = $this->service->getSurveyDetails($data);

        return $this->success(
            SurveyDetailListResource::collection($details['data']),
            'Survey details retrieved successfully',
            200,
            $details['pagination'] ?? null
        );
    }

    /**
     * Custom success response
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
 *    path="/mob/merchendisher_mob/survey-detail/global-search",
 *     summary="Global Search Survey Details",
 *     description="Performs a global search on survey details by header_id, question_id, question, answer, id, created_user, updated_user, deleted_user, firstname, or lastname.",
 *     tags={"Survey Details"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         required=false,
 *         description="Search term (can match id, header_id, question_id, answer, firstname, lastname, etc.)",
 *         @OA\Schema(type="string", example="john")
 *     ),
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         required=false,
 *         description="Number of results per page",
 *         @OA\Schema(type="integer", default=10, example=20)
 *     ),
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         required=false,
 *         description="Page number for pagination",
 *         @OA\Schema(type="integer", default=1, example=2)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Survey details retrieved successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Survey details retrieved successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="header", type="object",
 *                         @OA\Property(property="id", type="integer", example=10),
 *                     ),
 *                     @OA\Property(property="question", type="object",
 *                         @OA\Property(property="id", type="integer", example=5),
 *                         @OA\Property(property="question_text", type="string", example="What is your favorite color?")
 *                     ),
 *                     @OA\Property(property="answer", type="string", example="Blue"),
 *                     @OA\Property(property="created_user", type="object",
 *                         @OA\Property(property="id", type="integer", example=2),
 *                         @OA\Property(property="firstname", type="string", example="John"),
 *                         @OA\Property(property="lastname", type="string", example="Doe"),
 *                         @OA\Property(property="username", type="string", example="johndoe")
 *                     ),
 *                     @OA\Property(property="updated_user", type="object",
 *                         @OA\Property(property="id", type="integer", example=3),
 *                         @OA\Property(property="firstname", type="string", example="Jane"),
 *                         @OA\Property(property="lastname", type="string", example="Smith"),
 *                         @OA\Property(property="username", type="string", example="janesmith")
 *                     ),
 *                     @OA\Property(property="deleted_user", type="object",
 *                         @OA\Property(property="id", type="integer", example=4),
 *                         @OA\Property(property="firstname", type="string", example="Mark"),
 *                         @OA\Property(property="lastname", type="string", example="Brown"),
 *                         @OA\Property(property="username", type="string", example="markbrown")
 *                     )
 *                 )
 *             ),
 *             @OA\Property(
 *                 property="pagination",
 *                 type="object",
 *                 @OA\Property(property="current_page", type="integer", example=1),
 *                 @OA\Property(property="last_page", type="integer", example=5),
 *                 @OA\Property(property="per_page", type="integer", example=10),
 *                 @OA\Property(property="total", type="integer", example=50)
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=400,
 *         description="Bad request"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error"
 *     )
 * )
 */
  public function globalSearch(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $searchTerm = $request->get('search');

            $details = $this->service->globalSearch($perPage, $searchTerm);
 
            $message = $details->isEmpty()
            ? 'Survey details not found'
            : 'Survey details retrieved successfully';

            return response()->json([
                "status" => "success",
                "code" => 200,
                "message" => $message,
                "data" => SurveyDetailListResource::collection($details->items()),
                "pagination" => [
                    "current_page" => $details->currentPage(),
                    "last_page" => $details->lastPage(),
                    "per_page" => $details->perPage(),
                    "total" => $details->total(),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "code" => 500,
                "message" => $e->getMessage(),
                "data" => null
            ], 500);
        }
    }

    /**
 * @OA\Get(
 *     path="/mob/merchendisher_mob/survey-detail/export-excel/{header_id}",
 *     summary="Export Survey Details to Excel",
 *     description="Download survey details in Excel format by Header ID",
 *     tags={"Survey Details"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="header_id",
 *         in="path",
 *         required=true,
 *         description="Header ID of survey",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Excel file download",
 *         @OA\MediaType(
 *             mediaType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
 *             @OA\Schema(type="string", format="binary")
 *         )
 *     ),
 *     @OA\Response(response=404, description="Survey not found")
 * )
 */

public function exportExcel($header_id)
{
    $fileName = 'survey_details_header_'.$header_id.'_'.now()->format('Y_m_d_H_i_s') . '.xlsx';

    return Excel::download(new SurveyDetailsExport($header_id), $fileName);
}
}