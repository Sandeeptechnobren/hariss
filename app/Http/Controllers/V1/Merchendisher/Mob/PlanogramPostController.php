<?php

namespace App\Http\Controllers\V1\Merchendisher\Mob;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Merchendisher\Mob\PlanogramPostRequest;
use App\Http\Resources\V1\Merchendisher\Mob\PlanogramPostResource;
use App\Services\V1\Merchendisher\Mob\PlanogramPostService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PlanogramPostController extends Controller
{
      protected $service;

    public function __construct(PlanogramPostService $service)
    {
        $this->service = $service;
    }
  /**
 * @OA\Post(
 *     path="/mob/merchendisher_mob/planogram-post/create",
 *     summary="Create a new Planogram Post",
 *     security={{"bearerAuth":{}}},
 *     tags={"PlanogramPosts"},
 *     operationId="createPlanogramPost",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={
 *                     "planogram_id",
 *                     "merchendisher_id",
 *                     "date",
 *                     "customer_id",
 *                     "shelf_id"
 *                 },
 *                 @OA\Property(
 *                     property="planogram_id",
 *                     type="integer",
 *                     example=101,
 *                     description="ID of the related planogram"
 *                 ),
 *                 @OA\Property(
 *                     property="merchendisher_id",
 *                     type="integer",
 *                     example=202,
 *                     description="ID of the merchandiser"
 *                 ),
 *                 @OA\Property(
 *                     property="date",
 *                     type="string",
 *                     format="date",
 *                     example="2025-09-29",
 *                     description="Date of the planogram post"
 *                 ),
 *                 @OA\Property(
 *                     property="customer_id",
 *                     type="integer",
 *                     example=303,
 *                     description="ID of the customer"
 *                 ),
 *                 @OA\Property(
 *                     property="shelf_id",
 *                     type="integer",
 *                     example=404,
 *                     description="ID of the shelf"
 *                 ),
 *                 @OA\Property(
 *                     property="before_image",
 *                     type="string",
 *                     format="binary",
 *                     description="(Optional) Before image of the shelf"
 *                 ),
 *                 @OA\Property(
 *                     property="after_image",
 *                     type="string",
 *                     format="binary",
 *                     description="(Optional) After image of the shelf"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Planogram post created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="code", type="integer", example=201),
 *             @OA\Property(property="message", type="string", example="Planogram post created successfully"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="planogram_id", type="integer", example=101),
 *                 @OA\Property(property="merchendisher_id", type="integer", example=202),
 *                 @OA\Property(property="customer_id", type="integer", example=303),
 *                 @OA\Property(property="shelf_id", type="integer", example=404),
 *                 @OA\Property(property="date", type="string", format="date", example="2025-09-29"),
 *                 @OA\Property(property="before_image_url", type="string", example="https://example.com/uploads/before.jpg"),
 *                 @OA\Property(property="after_image_url", type="string", example="https://example.com/uploads/after.jpg")
 *             )
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
 *                     "planogram_id": {"The planogram id field is required."},
 *                     "date": {"The date field must be a valid date."}
 *                 }
 *             )
 *         )
 *     )
 * )
 */



    public function create(PlanogramPostRequest $request)
    {
        $data = $request->validated();
        if ($request->hasFile('before_image')) {
            $data['before_image'] = $request->file('before_image');
        }

        if ($request->hasFile('after_image')) {
            $data['after_image'] = $request->file('after_image');
        }
        $planogramPost = $this->service->store($data);

        return new PlanogramPostResource($planogramPost);
    }

 /**
 * @OA\Get(
 *     path="/api/merchendisher/planogram-post/list/{planogram_uuid}",
 *     summary="Get Planogram Posts by Planogram UUID",
 *     tags={"PlanogramPosts"},
 *     security={{"bearerAuth":{}}},
 *     operationId="getPlanogramPostsByUuid",
 *
 *     @OA\Parameter(
 *         name="planogram_uuid",
 *         in="path",
 *         required=true,
 *         description="Planogram UUID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         required=false,
 *         description="Items per page (pagination)",
 *         @OA\Schema(type="integer", default=10)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Planogram posts retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Planogram posts retrieved successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="planogram_id", type="integer", example=101),
 *                     @OA\Property(property="merchendisher_id", type="integer", example=202),
 *                     @OA\Property(property="customer_id", type="integer", example=303),
 *                     @OA\Property(property="shelf_id", type="integer", example=404),
 *                     @OA\Property(property="date", type="string", format="date", example="2025-09-29"),
 *                     @OA\Property(
 *                         property="before_image_url",
 *                         type="string",
 *                         example="https://example.com/uploads/before.jpg"
 *                     ),
 *                     @OA\Property(
 *                         property="after_image_url",
 *                         type="string",
 *                         example="https://example.com/uploads/after.jpg"
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
 *         response=404,
 *         description="Planogram not found"
 *     ),
 *
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
 *         )
 *     )
 * )
 */
public function index(Request $request, string $planogramUuid): JsonResponse
    {
        $perPage = $request->get('per_page', 50);

        $planogramPosts = $this->service->getByPlanogramUuid($planogramUuid, $perPage);

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Planogram posts retrieved successfully',
            'data' => PlanogramPostResource::collection($planogramPosts),
            'pagination' => [
                'current_page' => $planogramPosts->currentPage(),
                'last_page'    => $planogramPosts->lastPage(),
                'per_page'     => $planogramPosts->perPage(),
                'total'        => $planogramPosts->total(),
            ]
        ]);
    }
/**
 * @OA\Get(
 *     path="/mob/merchendisher_mob/planogramlist",
 *     summary="Download planogram IDs for the authenticated user",
 *     tags={"PlanogramPosts"},
 *     operationId="downloadPlanogramIds",
 *     security={{"bearerAuth":{}}},
 *     
 *     @OA\Response(
 *         response=200,
 *         description="Planogram data file generated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Planogram data file generated successfully."),
 *             @OA\Property(property="url", type="string", example="https://example.com/storage/planograms/user_123_planogram_ids.csv")
 *         )
 *     ),
 *     
 *     @OA\Response(
 *         response=404,
 *         description="No planogram IDs found for the current user.",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="No planogram IDs found for the current user.")
 *         )
 *     ),
 *     
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
 *         )
 *     )
 * )
 */
  public function downloadPlanogramIds(PlanogramPostService $service): JsonResponse
{
    $userId = auth()->id();

    $downloadUrl = $service->generatePlanogramIdsFileForUser($userId);

    if (!$downloadUrl) {
        return response()->json([
            'message' => 'No planogram IDs found for the current user.'
        ], 404);
    }

    return response()->json([
        'message' => 'Planogram data file generated successfully.',
        'url' => $downloadUrl
    ]);
}
/**
 * @OA\Get(
 *     path="/api/merchendisher/planogram-post/exportfile",
 *     summary="Export planogram post data to CSV or Excel",
 *     description="Exports planogram post data including related names (planogram, merchendisher, customer, shelf). Optionally filter results by date range and choose export format.",
 *     operationId="exportPlanogramPosts",
 *     tags={"PlanogramPosts"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(
 *         name="start_date",
 *         in="query",
 *         description="Start date for filtering (format: Y-m-d)",
 *         required=false,
 *         @OA\Schema(type="string", format="date", example="2025-08-01")
 *     ),
 *     @OA\Parameter(
 *         name="end_date",
 *         in="query",
 *         description="End date for filtering (format: Y-m-d). Must be after or equal to start_date.",
 *         required=false,
 *         @OA\Schema(type="string", format="date", example="2025-08-31")
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
 *         description="File successfully exported",
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
 *                 @OA\Property(
 *                     property="start_date",
 *                     type="array",
 *                     @OA\Items(type="string", example="The start date must be a valid date.")
 *                 ),
 *                 @OA\Property(
 *                     property="end_date",
 *                     type="array",
 *                     @OA\Items(type="string", example="The end date must be a valid date and after or equal to start date.")
 *                 ),
 *                 @OA\Property(
 *                     property="format",
 *                     type="array",
 *                     @OA\Items(type="string", example="The selected format is invalid.")
 *                 )
 *             )
 *         )
 *     )
 * )
 */
  public function export(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'format' => 'nullable|in:csv,excel',
        ]);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $format = $request->input('format', 'csv');

        return $this->service->exportPlanogramPosts($startDate, $endDate, $format);
    }
}
