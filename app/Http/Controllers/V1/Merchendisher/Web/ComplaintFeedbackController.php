<?php

namespace App\Http\Controllers\V1\Merchendisher\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Merchendisher\Web\ComplaintFeedbackRequest;
use App\Http\Resources\V1\Merchendisher\Web\ComplaintFeedbackResource;
use App\Services\V1\Merchendisher\Web\ComplaintFeedbackService;
use App\Models\ComplaintFeedback;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use App\Exports\ComplaintFeedbackExport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;

/**
     * @OA\Schema(
     *     schema="ComplaintFeedbackResource",
     *     type="object",
     *     title="ComplaintFeedbackResource",
     *     description="Complaint Feedback Resource representation",
     *     @OA\Property(property="id", type="integer", example=123),
     *     @OA\Property(property="uuid", type="string", example="a1b2c3d4-e5f6-7g8h-9i0j-k1l2m3n4o5p6"),
     *     @OA\Property(property="feedback_title", type="string", example="Product quality issue"),
     *     
     *     @OA\Property(
     *         property="merchendiser",
     *         type="object",
     *         nullable=true,
     *         @OA\Property(property="id", type="integer", example=1),
     *         @OA\Property(property="name", type="string", example="John Doe")
     *     ),
     *     
     *     @OA\Property(
     *         property="item",
     *         type="object",
     *         nullable=true,
     *         @OA\Property(property="id", type="integer", example=10),
     *         @OA\Property(property="item_code", type="string", example="ITM-12345"),
     *         @OA\Property(property="item_name", type="string", example="Blue T-Shirt")
     *     ),
     *     
     *     @OA\Property(property="type", type="string", example="complaint"),
     *     @OA\Property(property="description", type="string", example="The item was defective on arrival."),
     *     @OA\Property(property="created_by", type="string", example="admin"),
     *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-08T12:00:00Z")
     * )
     */
class ComplaintFeedbackController extends Controller
{
    protected ComplaintFeedbackService $service;

    public function __construct(ComplaintFeedbackService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/merchendisher/complaint-feedback/show/{uuid}",
     *     summary="Get a ComplaintFeedback by UUID",
     *     description="Retrieve a single ComplaintFeedback resource by its UUID",
     *     operationId="getComplaintFeedbackByUuid",
     *     tags={"ComplaintFeedback"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         description="UUID of the ComplaintFeedback",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="ComplaintFeedback retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="ComplaintFeedback retrieved successfully"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/ComplaintFeedbackResource"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No ComplaintFeedback found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="No ComplaintFeedback found"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="data", type="null", nullable=true)
     *         )
     *     )
     * )
     */
        public function show($uuid)
    {
         $complaintfeedback = $this->service->getByUuid($uuid);

    if (!$complaintfeedback) {
        return response()->json([
            'message' => 'No ComplaintFeedback found',
            'code' => 200,
            'data' => null,
        ]);
    }

    return response()->json([
        'message' => 'ComplaintFeedback retrieved successfully',
        'code' => 200,
        'data' => new ComplaintFeedbackResource($complaintfeedback),
       ]);
    }
 /**
 * @OA\Get(
 *     path="/api/merchendisher/complaint-feedback/list",
 *     summary="Get paginated list of ComplaintFeedback",
 *     description="Returns a paginated list of ComplaintFeedback resources for the authenticated user",
 *     operationId="getComplaintFeedbackList",
 *     tags={"ComplaintFeedback"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="Search by customer complaint, merchandiser name, item name, etc",
 *         required=false,
 *         @OA\Schema(type="string", example="Amit")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="ComplaintFeedback fetched successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="ComplaintFeedback fetched successfully"),
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 title="PaginatedComplaintFeedback",
 *                 description="Paginated list of ComplaintFeedbackResource",
 *                 @OA\Property(property="current_page", type="integer", example=1),
 *                 @OA\Property(
 *                     property="data",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=123),
 *                         @OA\Property(property="uuid", type="string", example="a1b2c3d4-e5f6-7g8h-9i0j-k1l2m3n4o5p6"),
 *                         @OA\Property(property="feedback_title", type="string", example="Product quality issue"),
 *                         @OA\Property(
 *                             property="merchendiser",
 *                             type="object",
 *                             nullable=true,
 *                             @OA\Property(property="id", type="integer", example=1),
 *                             @OA\Property(property="name", type="string", example="John Doe")
 *                         ),
 *                         @OA\Property(
 *                             property="item",
 *                             type="object",
 *                             nullable=true,
 *                             @OA\Property(property="id", type="integer", example=10),
 *                             @OA\Property(property="item_code", type="string", example="ITM-12345"),
 *                             @OA\Property(property="item_name", type="string", example="Blue T-Shirt")
 *                         ),
 *                         @OA\Property(property="type", type="string", example="complaint"),
 *                         @OA\Property(property="description", type="string", example="The item was defective on arrival."),
 *                         @OA\Property(property="created_by", type="string", example="admin"),
 *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-08T12:00:00Z")
 *                     )
 *                 ),
 *                 @OA\Property(property="first_page_url", type="string", example="http://yourapp.test/api/complaintfeedback?page=1"),
 *                 @OA\Property(property="from", type="integer", example=1),
 *                 @OA\Property(property="last_page", type="integer", example=10),
 *                 @OA\Property(property="last_page_url", type="string", example="http://yourapp.test/api/complaintfeedback?page=10"),
 *                 @OA\Property(property="next_page_url", type="string", nullable=true, example="http://yourapp.test/api/complaintfeedback?page=2"),
 *                 @OA\Property(property="path", type="string", example="http://yourapp.test/api/complaintfeedback"),
 *                 @OA\Property(property="per_page", type="integer", example=10),
 *                 @OA\Property(property="prev_page_url", type="string", nullable=true, example=null),
 *                 @OA\Property(property="to", type="integer", example=10),
 *                 @OA\Property(property="total", type="integer", example=100)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     )
 * )
 */
     public function index()
    {
        $complaintfeedback = $this->service->getAll();
        return ResponseHelper::paginatedResponse(
        'ComplaintFeedback fetched successfully',
        ComplaintFeedbackResource::class,
        $complaintfeedback
      );
    }
    
 /**
 * @OA\Post(
 *     path="/api/merchendisher/complaint-feedback/create",
 *     summary="Create a new complaint (form-data)",
 *     tags={"ComplaintFeedback"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *                 required={
 *                     "complaint_title",
 *                     "item_id",
 *                     "merchendiser_id",
 *                     "complaint",
 *                     "image[]"
 *                 },
 *                 @OA\Property(property="complaint_title", type="string", example="Damaged packaging"),
 *                 @OA\Property(property="complaint_code", type="string", example="CMP20231010"),
 *                 @OA\Property(property="item_id", type="integer", example=5, description="Must exist in items table"),
 *                 @OA\Property(property="merchendiser_id", type="integer", example=3, description="Must exist in salesmen table"),
 *                 @OA\Property(property="customer_id", type="integer", example=8, description="Must exist in tbl_company_customer table"),
 *                 @OA\Property(property="type", type="string", example="suggestion"),
 *                 @OA\Property(property="complaint", type="string", example="The product packaging was torn when received."),
 *                 
 *                 @OA\Property(
 *                     property="image[]",
 *                     type="array",
 *                     description="Exactly 2 image files",
 *                     minItems=2,
 *                     maxItems=2,
 *                     @OA\Items(
 *                         type="string",
 *                         format="binary"
 *                     )
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Complaint created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="id", type="integer", example=10),
 *             @OA\Property(property="complaint_title", type="string", example="Damaged packaging"),
 *             @OA\Property(property="item_id", type="integer", example=5),
 *             @OA\Property(property="merchendiser_id", type="integer", example=3),
 *             @OA\Property(property="type", type="string", example="Packaging"),
 *             @OA\Property(property="complaint", type="string", example="The product packaging was torn when received."),
 *             @OA\Property(property="uuid", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
 *             @OA\Property(property="complaint_code", type="string", example="CMP20231010"),
 *             @OA\Property(
 *                 property="image",
 *                 type="array",
 *                 @OA\Items(type="string", format="uri", example="/storage/planogram_images/n5VvOIsR3OXuCdthYd4dXqGDWFkHidWsYJ9zrU9i.jpg")
 *             ),
 *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-10T10:00:00Z"),
 *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-10-10T10:00:00Z")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Validation failed for field 'item_id'")
 *         )
 *     )
 * )
 */

     public function store(ComplaintFeedbackRequest $request)
    {
        $complaint = $this->service->createComplaint($request->validated());

        return new ComplaintFeedbackResource($complaint);
    }


    /**
 * @OA\Get(
 *     path="/api/merchendisher/complaint-feedback/exportfile", 
 *     summary="Export complaint feedbacks filtered by date range",
 *     description="Export complaint feedback data filtered by optional date range. Date format is day-month-year (d-m-Y). The response is a downloadable CSV or Excel file.",
 *     operationId="exportComplaintFeedbacks",
 *     tags={"ComplaintFeedback"},
 *     security={{"bearerAuth":{}}},
 * 
 *     @OA\Parameter(
 *         name="start_date",
 *         in="query",
 *         description="Start date for filtering (format: d-m-Y, e.g. 09-08-2025)",
 *         required=false,
 *         @OA\Schema(type="string", format="date", example="09-08-2025")
 *     ),
 *     @OA\Parameter(
 *         name="end_date",
 *         in="query",
 *         description="End date for filtering (format: d-m-Y, e.g. 11-08-2025)",
 *         required=false,
 *         @OA\Schema(type="string", format="date", example="11-08-2025")
 *     ),
 *     @OA\Parameter(
 *         name="format",
 *         in="query",
 *         description="Export file format, either 'csv' or 'xlsx'",
 *         required=true,
 *         @OA\Schema(type="string", enum={"csv", "xlsx"})
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Successful file download",
 *         content={
 *             @OA\MediaType(
 *                 mediaType="application/octet-stream",
 *                 @OA\Schema(type="string", format="binary")
 *             )
 *         }
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error - invalid or missing parameters",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 example={
 *                     "start_date": {"The start date does not match the format d-m-Y."},
 *                     "format": {"The selected format is invalid."}
 *                 }
 *             )
 *         )
 *     )
 * )
 */
       public function export(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'format' => 'required|in:csv,xlsx',
        ]);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $format = $request->input('format'); 

      $data = $this->service->exportFeedbacks($startDate, $endDate);

        $export = new ComplaintFeedbackExport($data);
        $fileName = 'complaint_feedbacks_' . now()->format('Ymd_His') . '.' . $format;

     if (ob_get_length()) {
        ob_end_clean();
    }
        return Excel::download($export, $fileName, $format === 'csv' ? ExcelFormat::CSV : ExcelFormat::XLSX);
    }
}