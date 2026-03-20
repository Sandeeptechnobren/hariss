<?php

namespace App\Http\Controllers\V1\Merchendisher\Web;

use App\Http\Controllers\Controller;
use App\Models\Planogram;
use App\Http\Requests\V1\Merchendisher\Web\PlanogramRequest;
use App\Http\Requests\V1\Merchendisher\Web\PlanogramUpdateRequest;
use App\Http\Resources\V1\Merchendisher\Web\PlanogramResource;
use App\Services\V1\Merchendisher\Web\PlanogramService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Helpers\ResponseHelper;
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
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by planogram name or other related fields",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
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
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "valid_from", "valid_to", "merchendisher_id", "customer_id", "shelf_id"},
     *                 @OA\Property(property="name", type="string", maxLength=55, description="Name of the planogram", example="October Planogram"),
     *                 @OA\Property(property="valid_from", type="string", format="date", description="Start date", example="2025-10-01"),
     *                 @OA\Property(property="valid_to", type="string", format="date", description="End date", example="2025-10-31"),
     *                 @OA\Property(property="merchendisher_id[]", type="array", @OA\Items(type="integer"), example={93,95}),
     *                 @OA\Property(property="customer_id[]", type="array", @OA\Items(type="integer"), example={72,89}),
     *                 @OA\Property(property="shelf_id[]", type="array", @OA\Items(type="integer"), example={81,62}),
     *                 @OA\Property(property="images", type="string", format="binary", description="Images uploaded as files (multiple allowed)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Planogram created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Planogram and images saved successfully."),
     *             @OA\Property(
     *                 property="planogram",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=123),
     *                 @OA\Property(property="name", type="string", example="October Planogram"),
     *                 @OA\Property(property="valid_from", type="string", format="date", example="2025-10-01"),
     *                 @OA\Property(property="valid_to", type="string", format="date", example="2025-10-31"),
     *                 @OA\Property(property="merchendisher_id", type="array", @OA\Items(type="integer"), example={93,95}),
     *                 @OA\Property(property="customer_id", type="array", @OA\Items(type="integer"), example={72,89}),
     *                 @OA\Property(property="shelf_id", type="array", @OA\Items(type="integer"), example={81,62}),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-05T14:48:00.000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-10-06T10:15:00.000Z")
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
     *                 @OA\Property(
     *                     property="name",
     *                     type="array",
     *                     @OA\Items(type="string", example="The name field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to store planogram"),
     *             @OA\Property(property="details", type="string", example="SQLSTATE[23502]: Not null violation...")
     *         )
     *     )
     * )
     */
    public function store(PlanogramRequest $request): JsonResponse
    {
        try {
            $planogram = $this->service->store($request->validated());
            return response()->json([
                'message' => 'Planogram and images saved successfully.',
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
     * @OA\Put(
     *     path="/api/merchendisher/planogram/update/{uuid}",
     *     summary="Update a planogram",
     *     tags={"Planograms"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="Planogram uuid",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="New Planogram"),
     *             @OA\Property(property="valid_from", type="date", example="2025-09-26"),
     *             @OA\Property(property="valid_to", type="date", example="2025-10-01"),
     *             @OA\Property(property="status", type="int", example=1),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Planogram updated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Updated Planogram"),
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
    public function update(PlanogramUpdateRequest $request, string $uuid): JsonResponse
    {
        try {
            $planogram = Planogram::where('uuid', $uuid)->firstOrFail();
            $validatedData = $request->validated();  
            if (!isset($validatedData['name'])) {
                throw new \Exception('Name field is missing in the request.');
            }
            $updatedPlanogram = $this->service->update($planogram, $validatedData);

            return response()->json([
                'message' => 'Planogram and images updated successfully.',
                'planogram' => new PlanogramResource($updatedPlanogram),
            ]);
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
     *     summary="Export planogram data in CSV or XLSX format",
     *     description="Exports planogram records based on optional date filters. The response will download the file directly.",
     *     tags={"Planograms"},
     *     @OA\Parameter(
     *         name="format",
     *         in="query",
     *         required=true,
     *         description="File format to export (csv or xlsx)",
     *         @OA\Schema(type="string", enum={"csv","xlsx"}, example="csv")
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
     *         description="End date for filtering records (YYYY-MM-DD, must be after or equal to valid_from)",
     *         @OA\Schema(type="string", format="date", example="2025-12-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File export successful (CSV or XLSX)",
     *         @OA\Header(
     *             header="Content-Disposition",
     *             description="Attachment header with the exported filename",
     *             @OA\Schema(type="string", example="attachment; filename=planogram_list_2025_10_06_12_30_00.csv")
     *         ),
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
     *         description="Validation error (invalid parameters)",
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

        $planograms = $this->service->getFiltered(
            $request->valid_from,
            $request->valid_to
        );

        if ($planograms->isEmpty()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No data found for the given date range.'
            ], 404);
        }
        $planograms = Planogram::with(['merchendisher', 'customer'])->get();
        $data = $planograms->map(function ($item) {
            return [
                'ID'           => $item->id,
                'Code'         => $item->code,
                'Name'         => $item->name,
                'Valid From'   => $item->valid_from,
                'Valid To'     => $item->valid_to,
                'Merchendisher'=> $item->merchendisher->name ?? 'N/A',
                'Customer'     => $item->customer->business_name ?? 'N/A',
                'Created At'   => $item->created_at->format('Y-m-d H:i:s'),
            ];
        });

        $fileName = 'planogram_list_' . now()->format('Y_m_d_H_i_s');
        if ($request->format === 'csv') {
            $fileName .= '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$fileName\"",
            ];

            $callback = function() use ($data) {
                $file = fopen('php://output', 'w');
                fputcsv($file, array_keys($data->first())); 

                foreach ($data as $row) {
                    fputcsv($file, $row);
                }
                fclose($file);
            };

            return Response::stream($callback, 200, $headers);
        } 
        else {
            $fileName .= '.xlsx';

            return Excel::download(new class($data) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
                private $data;
                public function __construct($data) { $this->data = $data; }
                public function collection() { return $this->data; }
                public function headings(): array { return array_keys($this->data->first()); }
            }, $fileName, ExcelFormat::XLSX);
        }
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
     *     summary="Get shelves by customer IDs",
     *     description="Fetch shelves filtered by customer IDs.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Array of customer IDs",
     *         @OA\JsonContent(
     *             required={"customer_ids"},
     *             @OA\Property(
     *                 property="customer_ids",
     *                 type="array",
     *                 @OA\Items(type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with shelves data",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="shelf_name", type="string", example="Shelf A"),
     *                     @OA\Property(property="code", type="string", example="SHF001")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getShelvesByCustomerIds(Request $request)
    {
        $request->validate([
            'customer_ids' => 'required|array',
            'customer_ids.*' => 'integer',
        ]);
        $customerIds = $request->input('customer_ids'); 
        $shelves = $this->service->getShelvesByCustomerIds($request->customer_ids);

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
     *     @OA\Response(
     *         response=400,
     *         description="Invalid export format"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
    public function exportplanogram(Request $request)
    {
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
