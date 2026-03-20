<?php

namespace App\Http\Controllers\V1\Merchendisher\Web;

use App\Http\Controllers\Controller;
use App\Models\Planogram;
use App\Models\Salesman;
use App\Models\CompanyCustomer;
use App\Http\Requests\V1\Merchendisher\Web\PlanogramRequest;
use App\Http\Requests\V1\Merchendisher\Web\PlanogramUpdateRequest;
use App\Http\Resources\V1\Merchendisher\Web\PlanogramResource;
use App\Services\V1\Merchendisher\Web\PlanogramService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Storage;
use App\Exports\PlanogramExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Illuminate\Support\Facades\Response;

class PlanogramController extends Controller
{
     protected $service;

    public function __construct(PlanogramService $service)
    {
        $this->service = $service;
    }
 
/**
 * @OA\Get(
 *     path="/api/merchendisher/planogram/list",
 *     summary="Get all planograms (with optional global search)",
 *     tags={"Planograms"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="Search by planogram name or other related fields",
 *         required=false,
 *         @OA\Schema(type="string",)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="List of planograms",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Planogram retrieved successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="name", type="string", example="Planogram A"),
 *                     @OA\Property(property="created_at", type="string", example="2023-09-25T12:34:56Z"),
 *                     @OA\Property(property="updated_at", type="string", example="2023-09-25T12:34:56Z")
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
 *     )
 * )
 */

public function index()
    {
        $planograms = $this->service->getAll();
        return ResponseHelper::paginatedResponse(
            'Planogram retrieved successfully',
            PlanogramResource::class,
            $planograms
        );
    }

    
    /**
     * @OA\Get(
     *     path="/api/merchendisher/planogram/show/{uuid}",
     *     summary="Get a specific planogram",
     *     tags={"Planograms"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="Planogram uuid",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Planogram found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="New Planogram"),
     *             @OA\Property(property="valid_from", type="date", example="2025-09-26"),
     *             @OA\Property(property="valid_to", type="date", example="2025-10-01"),
     *             @OA\Property(property="status", type="int", example=1),
     *             @OA\Property(property="created_at", type="string"),
     *             @OA\Property(property="updated_at", type="string")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Planogram not found")
     * )
     */
        public function show($uuid)
    {
        $planogram = $this->service->getByuuid($uuid);

        if (!$planogram) {
            return response()->json(['message' => 'Planogram not found'], 404);
        }

        return new PlanogramResource($planogram);
    }
/**
 * @OA\Post(
 *     path="/api/merchendisher/planogram/create",
 *     summary="Create a new planogram",
 *     tags={"Planograms"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *                 required={"name", "valid_from", "valid_to", "merchendisher_id", "customer_id"},
 *
 *                 @OA\Property(
 *                     property="name",
 *                     type="string",
 *                     example="Planogram A"
 *                 ),
 *                 @OA\Property(
 *                     property="valid_from",
 *                     type="string",
 *                     format="date",
 *                     example="2025-11-01"
 *                 ),
 *                 @OA\Property(
 *                     property="valid_to",
 *                     type="string",
 *                     format="date",
 *                     example="2025-12-01"
 *                 ),
 *
 *                 @OA\Property(
 *                     property="merchendisher_id",
 *                     type="array",
 *                     description="List of merchendisher IDs",
 *                     @OA\Items(type="integer", example=3)
 *                 ),
 *                 @OA\Property(
 *                     property="customer_id",
 *                     type="array",
 *                     description="List of customer IDs",
 *                     @OA\Items(type="integer", example=5)
 *                 ),
 *
 *                 @OA\Property(
 *                     property="images[]",
 *                     type="array",
 *                     description="Multiple images",
 *                     @OA\Items(type="string", format="binary")
 *                 )
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Planogram created successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="name", type="string", example="Planogram A"),
 *             @OA\Property(property="valid_from", type="string", format="date", example="2025-11-01"),
 *             @OA\Property(property="valid_to", type="string", format="date", example="2025-12-01"),
 *             @OA\Property(
 *                 property="merchendisher_id",
 *                 type="string",
 *                 description="Comma-separated merchendisher IDs",
 *                 example="3,4,5"
 *             ),
 *             @OA\Property(
 *                 property="customer_id",
 *                 type="string",
 *                 description="Comma-separated customer IDs",
 *                 example="5,6"
 *             ),
 *             @OA\Property(
 *                 property="images",
 *                 type="array",
 *                 description="List of image URLs",
 *                 @OA\Items(type="string", example="/storage/planogram_images/example.jpg")
 *             ),
 *             @OA\Property(property="created_at", type="string", example="2025-01-10 12:00:00"),
 *             @OA\Property(property="updated_at", type="string", example="2025-01-10 12:00:00")
 *         )
 *     ),
 *
 *     @OA\Response(response=400, description="Bad Request"),
 *     @OA\Response(response=500, description="Internal Server Error")
 * )
 */

     public function store(PlanogramRequest $request): JsonResponse
    {
        try {
            $planogram = $this->service->store($request->validated());

            return response()->json([
                'message' => 'Planogram saved successfully.',
                'planogram' => new PlanogramResource($planogram),
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to store planogram',
                'details' => $e->getMessage()
            ], 500);
        }
    }

   /**
 * @OA\Post(
 *     path="/api/merchendisher/planogram/update/{uuid}",
 *     summary="Update a planogram",
 *     tags={"Planograms"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(
 *         name="uuid",
 *         in="path",
 *         required=true,
 *         description="Planogram UUID",
 *         @OA\Schema(type="string", example="4f43a74a-89ba-410c-b3f8-4f905f0e8dfd")
 *     ),
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *
 *                 @OA\Property(
 *                     property="name",
 *                     type="string",
 *                     example="Updated Planogram A"
 *                 ),
 *
 *                 @OA\Property(
 *                     property="code",
 *                     type="string",
 *                     example="PLN-035"
 *                 ),
 *
 *                 @OA\Property(
 *                     property="valid_from",
 *                     type="string",
 *                     format="date",
 *                     example="2025-09-26"
 *                 ),
 *
 *                 @OA\Property(
 *                     property="valid_to",
 *                     type="string",
 *                     format="date",
 *                     example="2025-10-01"
 *                 ),
 *
 *                 @OA\Property(
 *                     property="merchendisher_id",
 *                     type="array",
 *                     description="List of merchandisher IDs",
 *                     @OA\Items(type="integer", example=3)
 *                 ),
 *
 *                 @OA\Property(
 *                     property="customer_id",
 *                     type="array",
 *                     description="List of customer IDs",
 *                     @OA\Items(type="integer", example=5)
 *                 ),
 *
 *                 @OA\Property(
 *                     property="images[]",
 *                     type="array",
 *                     description="Multiple planogram images (append or replace based on logic)",
 *                     @OA\Items(type="string", format="binary")
 *                 )
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Planogram updated successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=188),
 *             @OA\Property(property="uuid", type="string", example="4f43a74a-89ba-410c-b3f8-4f905f0e8dfd"),
 *             @OA\Property(property="name", type="string", example="Updated Planogram A"),
 *             @OA\Property(property="valid_from", type="string", format="date", example="2025-09-26"),
 *             @OA\Property(property="valid_to", type="string", format="date", example="2025-10-01"),
 *             @OA\Property(
 *                 property="merchendisher_id",
 *                 type="string",
 *                 description="Comma-separated merchandisher IDs",
 *                 example="3,4,5"
 *             ),
 *             @OA\Property(
 *                 property="customer_id",
 *                 type="string",
 *                 description="Comma-separated customer IDs",
 *                 example="5,6"
 *             ),
 *             @OA\Property(
 *                 property="images",
 *                 type="array",
 *                 @OA\Items(type="string", example="/storage/planogram_images/example.jpg")
 *             ),
 *             @OA\Property(property="created_at", type="string", example="2025-12-16 06:41:05"),
 *             @OA\Property(property="updated_at", type="string", example="2025-12-16 07:10:00")
 *         )
 *     ),
 *
 *     @OA\Response(response=404, description="Planogram not found"),
 *     @OA\Response(response=422, description="Validation error")
 * )
 */

public function update(PlanogramUpdateRequest $request, string $uuid): JsonResponse
{
    try {
        $planogram = Planogram::where('uuid', $uuid)->firstOrFail();
        $validatedData = $request->validated();
        $updatedPlanogram = $this->service->update($planogram, $validatedData);
        return response()->json([
            'message' => 'Planogram updated successfully.',
            'planogram' => new PlanogramResource($updatedPlanogram),
        ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'error' => 'Planogram not found',
        ], 404);
    } catch (\Throwable $e) {
        return response()->json([
            'error' => 'Failed to update planogram',
            'details' => $e->getMessage(),
        ], 500);
    }
}
     /**
     * @OA\Delete(
     *     path="/api/merchendisher/planogram/delete/{uuid}",
     *     summary="Delete a planogram",
     *     tags={"Planograms"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="Planogram uuid",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Planogram deleted successfully"),
     *     @OA\Response(response=404, description="Planogram not found")
     * )
     */
    public function destroy($uuid)
    {
        $planogram = $this->service->getByuuid($uuid);

        if (!$planogram) {
            return response()->json(['message' => 'Planogram not found'], 404);
        }

        $this->service->delete($planogram);

        return response()->json(['message' => 'Planogram deleted successfully']);
    }

   /**
 * @OA\Post(
 *     path="/api/merchendisher/planogram/bulk-upload",
 *     summary="Bulk upload planogram records via CSV or XLSX file",
 *     description="Upload a CSV or XLSX file to import planogram data in bulk.",
 *     tags={"Planograms"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *                 required={"file"},
 *                 @OA\Property(
 *                     property="file",
 *                     description="CSV or Excel file to upload",
 *                     type="string",
 *                     format="binary"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Bulk upload completed successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Planogram bulk upload completed successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=206,
 *         description="Partial success – some rows failed validation",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="partial_success"),
 *             @OA\Property(property="message", type="string", example="Some rows failed validation"),
 *             @OA\Property(
 *                 property="errors",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="row", type="integer", example=3),
 *                     @OA\Property(property="errors", type="object")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error during import",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="message", type="string", example="Error during import: Invalid format")
 *         )
 *     ),
 *     security={{"bearerAuth": {}}}
 * )
 */

     public function bulkUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls',
        ]);

        try {
            $rows = Excel::toCollection(null, $request->file('file'))->first();
            $errors = $this->service->bulkUpload($rows);

            if (count($errors) > 0) {
                return response()->json([
                    'status' => 'partial_success',
                    'message' => 'Some rows failed validation',
                    'errors' => $errors
                ], 206);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Planogram bulk upload completed successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error during import: ' . $e->getMessage()
            ], 500);
        }
    }

 /**
 * @OA\Get(
 *     path="/api/merchendisher/planogram/export",
 *     summary="Export planogram data and get download URL",
 *     description="Generates CSV or XLSX export file, stores it on the server, and returns a public download URL.",
 *     tags={"Planograms"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(
 *         name="format",
 *         in="query",
 *         required=true,
 *         description="File format to export",
 *         @OA\Schema(type="string", enum={"csv","xlsx"}, example="xlsx")
 *     ),
 *     @OA\Parameter(
 *         name="valid_from",
 *         in="query",
 *         required=false,
 *         description="Start date for filtering records (YYYY-MM-DD)",
 *         @OA\Schema(type="string", format="date", example="2025-01-01")
 *     ),
 *     @OA\Parameter(
 *         name="valid_to",
 *         in="query",
 *         required=false,
 *         description="End date for filtering records (YYYY-MM-DD)",
 *         @OA\Schema(type="string", format="date", example="2025-12-31")
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Export file generated successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Export file generated successfully"),
 *             @OA\Property(
 *                 property="download_url",
 *                 type="string",
 *                 format="url",
 *                 example="https://your-domain.com/storage/exports/planogram_list_2025_01_16_12_30_55.xlsx"
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=404,
 *         description="No data found for the given date range",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="message", type="string", example="No data found for the given date range.")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="The format field is required.")
 *         )
 *     )
 * )
 */

     public function export(Request $request)
{
    $request->validate([
        'format'     => 'required|in:csv,xlsx',
        'valid_from' => 'nullable|date',
        'valid_to'   => 'nullable|date|after_or_equal:valid_from',
    ]);
    $planograms = Planogram::all();
    if ($planograms->isEmpty()) {
        return response()->json([
            'status'  => 'error',
            'message' => 'No data found'
        ], 404);
    }
    $data = $planograms->map(function ($item) {
        $merchIds = is_array($item->merchendisher_id)
            ? $item->merchendisher_id
            : explode(',', $item->merchendisher_id);
        $custIds = is_array($item->customer_id)
            ? $item->customer_id
            : explode(',', $item->customer_id);
        $merchNames = Salesman::whereIn('id', $merchIds)
            ->pluck('name')->implode(', ');
        $customerNames = CompanyCustomer::whereIn('id', $custIds)
            ->pluck('business_name')->implode(', ');
        $imageList = $item->images
            ? collect(explode(',', $item->images))->implode(' | ')
            : 'N/A';
        return [
            'Code'           => $item->code,
            'Name'           => $item->name,
            'Valid From'     => $item->valid_from,
            'Valid To'       => $item->valid_to,
            'Merchendisher'  => $merchNames ?: 'N/A',
            'Customer'       => $customerNames ?: 'N/A',
            'Images'         => $imageList,
        ];
    });
    $filePath = 'exports/planogram_list_' . now()->format('Y_m_d_H_i_s') . '.' . $request->format;
    if ($request->format === 'csv') {
        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, array_keys($data->first()));
        foreach ($data as $row) {
            fputcsv($csv, $row);
        }
        rewind($csv);
        Storage::disk('public')->put($filePath, stream_get_contents($csv));
        fclose($csv);
    }
    else {
        Excel::store(
            new class($data) implements
                \Maatwebsite\Excel\Concerns\FromCollection,
                \Maatwebsite\Excel\Concerns\WithHeadings {

                private $data;
                public function __construct($data) { $this->data = $data; }
                public function collection() { return $this->data; }
                public function headings(): array { return array_keys($this->data->first()); }
            },
            $filePath,
            'public',
            ExcelFormat::XLSX
        );
    }
    $appUrl = rtrim(config('app.url'), '/');
    $downloadUrl = $appUrl . '/public/storage/' . $filePath;
    return response()->json([
        'status'       => 'success',
        'message'      => 'Export file generated successfully',
        'download_url' => $downloadUrl,
    ]);
}

    
    /**
     * @OA\Get(
     *     path="/api/merchendisher/planogram/merchendisher-list",
     *     tags={"Planograms"},
     *     summary="Get list of Merchendishers",
     *     description="Fetch all salesman whose type is merchendisher.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of merchendishers fetched successfully"
     *     )
     * )
     */
    public function listMerchendishers(): JsonResponse
    {
        $salesmen = $this->service->getMerchendishers();

        return response()->json([
            'status'  => true,
            'message' => 'Merchendisher list fetched successfully',
            'data'    => $salesmen
        ]);
    }
/**
 * @OA\Post(
 *     path="/api/merchendisher/planogram/getshelf",
 *     tags={"Planograms"},
 *     summary="Get shelves grouped by merchandiser and customer IDs",
 *     description="Fetch shelves grouped by merchandiser ID, filtered by customer IDs. Each shelf is associated with one or more customer IDs.",
 *     security={{"bearerAuth":{}}},    
 *     @OA\RequestBody(
 *         required=true,
 *         description="Customer groups by merchandiser ID",
 *         @OA\JsonContent(
 *             required={"customer_groups"},
 *             @OA\Property(
 *                 property="customer_groups",
 *                 type="object",
 *                 additionalProperties=@OA\Schema(
 *                     type="array",
 *                     @OA\Items(type="integer")
 *                 ),
 *                 example={
 *                     "88": {89},
 *                     "89": {72, 89},
 *                     "95": {92},
 *                     "98": {89}
 *                 }
 *             )
 *         )
 *     ),
 *     
 *     @OA\Response(
 *         response=200,
 *         description="Successful response with shelves data grouped by merchandiser and customer ID",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 additionalProperties=@OA\Schema(
 *                     type="object",
 *                     additionalProperties=@OA\Schema(
 *                         type="array",
 *                         @OA\Items(ref="#/components/schemas/PlanogramShelf")
 *                     )
 *                 ),
 *                 example={
 *                     "88": {
 *                         "89": {
 *                             {
 *                                 "shelf_id": 1,
 *                                 "shelf_name": "Shelf A",
 *                                 "code": "SHF001"
 *                             }
 *                         }
 *                     },
 *                     "89": {
 *                         "72": {},
 *                         "89": {}
 *                     },
 *                     "95": {
 *                         "92": {}
 *                     },
 *                     "98": {
 *                         "89": {}
 *                     }
 *                 }
 *             )
 *         )
 *     ),
 *     
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 example={
 *                     "customer_groups": {
 *                         "The customer_groups field is required."
 *                     }
 *                 }
 *             )
 *         )
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
/**
 * @OA\Schema(
 *     schema="PlanogramShelf",
 *     type="object",
 *     title="Planogram Shelf",
 *     description="Shelf resource details",
 *     @OA\Property(property="shelf_id", type="integer", example=1),
 *     @OA\Property(property="shelf_name", type="string", example="Shelf A"),
 *     @OA\Property(property="code", type="string", example="SHF001")
 * )
 */
   public function getShelvesByCustomerIds(Request $request)
{
    $request->validate([
        'customer_groups' => 'required|array',
        'customer_groups.*' => 'required|array',
        'customer_groups.*.*' => 'integer'
    ]);
    $customerGroups = collect($request->input('customer_groups'))
        ->mapWithKeys(function ($ids, $merchandiserId) {
            return [
                $merchandiserId => collect($ids)->map(fn($id) => (int) $id)->all()
            ];
        })->toArray();

    $shelves = $this->service->getShelvesByCustomerGroups($customerGroups);

    return response()->json([
        'status' => true,
        'data' => $shelves
    ]);
}

/**
 * @OA\Get(
 *     path="/api/merchendisher/planogram/export-file",
 *     operationId="exportPlanogram",
 *     summary="Export Planogram Data",
 *     description="Exports planogram data as a downloadable file in CSV, XLS, or XLSX format.",
 *     tags={"Planograms"},
 *
 *     @OA\Parameter(
 *         name="format",
 *         in="query",
 *         required=false,
 *         description="Export format: csv, xls, or xlsx (default is xlsx)",
 *         @OA\Schema(
 *             type="string",
 *             enum={"csv", "xls", "xlsx"},
 *             default="xlsx",
 *             example="xlsx"
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Successful file download",
 *         content={
 *             @OA\MediaType(
 *                 mediaType="application/octet-stream",
 *                 @OA\Schema(
 *                     type="string",
 *                     format="binary"
 *                 )
 *             )
 *         }
 *     ),
 *
 *     @OA\Response(
 *         response=400,
 *         description="Invalid export format"
 *     ),
 *
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error"
 *     )
 * )
 */

  public function exportplanogram(Request $request)
    {
        // Accept query param like ?format=csv or ?format=xlsx or ?format=xls
        $format = $request->get('format', 'xlsx');  // default xlsx
        $allowed = ['csv', 'xlsx', 'xls'];
        if (! in_array($format, $allowed)) {
            $format = 'xlsx';
        }

        $fileName = 'planograms_export_' . now()->format('Ymd_His') . '.' . $format;

        // Using the export class
        return Excel::download(
            new PlanogramExport($this->service),
            $fileName,
            $this->getExcelType($format)
        );
    }


    protected function getExcelType(string $format)
    {
        switch ($format) {
            case 'csv':
                return \Maatwebsite\Excel\Excel::CSV;
            case 'xls':
                return \Maatwebsite\Excel\Excel::XLS;
            case 'xlsx':
            default:
                return \Maatwebsite\Excel\Excel::XLSX;
        }
    }
}
