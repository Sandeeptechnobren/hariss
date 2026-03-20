<?php

namespace App\Http\Controllers\V1\Merchendisher\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Merchendisher\Web\PlanogramImageRequest;
use App\Http\Requests\V1\Merchendisher\Web\PlanogramimgUpdateRequest;
use App\Http\Resources\V1\Merchendisher\Web\PlanogramImageResource;
use App\Services\V1\Merchendisher\Web\PlanogramImageService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Illuminate\Support\Facades\Response;

class PlanogramImageController extends Controller
{
     protected $service;

    public function __construct(PlanogramImageService $service)
    {
        $this->service = $service;
    }
/**
 * @OA\Get(
 *     path="/api/merchendisher/planogram-image/list",
 *     summary="Get all planogram images (with optional global search)",
 *     tags={"Planogram Images"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="Search by customer name, merchandiser name, shelf name, or image name",
 *         required=false,
 *         @OA\Schema(type="string", example="Amit")
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="List of planogram images",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Planogram images retrieved successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="uuid", type="string", example="a1b2c3d4-e5f6-7890-abcd-1234567890ef"),
 *                     @OA\Property(property="customer_id", type="integer", example=1),
 *                     @OA\Property(property="merchandiser_id", type="integer", example=2),
 *                     @OA\Property(property="shelf_id", type="integer", example=3),
 *                     @OA\Property(property="image", type="string", example="/storage/planogram_images/sample.jpg"),
 *                     @OA\Property(property="created_at", type="string", example="2023-09-25T12:34:56Z"),
 *                     @OA\Property(property="updated_at", type="string", example="2023-09-25T12:34:56Z")
 *                 )
 *             ),
 *             @OA\Property(
 *                 property="pagination",
 *                 type="object",
 *                 @OA\Property(property="current_page", type="integer", example=1),
 *                 @OA\Property(property="last_page", type="integer", example=10),
 *                 @OA\Property(property="per_page", type="integer", example=15),
 *                 @OA\Property(property="total", type="integer", example=150)
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized")
 * )
 */

    public function index()
    {
        $data = $this->service->getAll();
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Planogram retrieved successfully',
            'data' => PlanogramImageResource::collection($data),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'last_page'    => $data->lastPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
            ]]);
    }
      /**
     * @OA\Post(
     *     path="/api/merchendisher/planogram-image/create",
     *     summary="Create a new planogram image",
     *     tags={"Planogram Images"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Form data including image file",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="customer_id", type="integer"),
     *                 @OA\Property(property="merchandiser_id", type="integer"),
     *                 @OA\Property(property="shelf_id", type="integer"),
     *                 @OA\Property(property="image", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Validation Error")
     * )
     */

    public function store(PlanogramImageRequest $request)
    {
        try {
            $data = $this->service->store($request->all(), $request->file('image'), auth()->id());
            return new PlanogramImageResource($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
     /**
     * @OA\Get(
     *     path="/api/merchendisher/planogram-image/show/{id}",
     *     summary="Get a single planogram image by ID",
     *     tags={"Planogram Images"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Planogram image ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Planogram image data"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */

    public function show($id)
    {
        $data = $this->service->show($id);
        return new PlanogramImageResource($data);
    }
      /**
     * @OA\Post(
     *     path="/api/merchendisher/planogram-image/update/{id}",
     *     summary="Update a planogram image by ID",
     *     tags={"Planogram Images"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Planogram image ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Form data including image file",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="customer_id", type="integer"),
     *                 @OA\Property(property="merchandiser_id", type="integer"),
     *                 @OA\Property(property="shelf_id", type="integer"),
     *                 @OA\Property(property="image", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated"),
     *     @OA\Response(response=422, description="Validation Error")
     * )
     */

    public function update(PlanogramimgUpdateRequest $request, $id)
    {
        try {
            $data = $this->service->update($id, $request->all(), $request->file('image'), auth()->id());
            return new PlanogramImageResource($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
     /**
     * @OA\Delete(
     *     path="/api/merchendisher/planogram-image/delete/{id}",
     *     summary="Delete a planogram image",
     *     tags={"Planogram Images"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Planogram image ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Deleted successfully"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */

    public function destroy($id)
    {
        $this->service->delete($id, auth()->id());
        return response()->json(['message' => 'Deleted successfully']);
    }

     /**
     * @OA\Post(
     *     path="/api/merchendisher/planogram-image/bulk-upload",
     *     summary="Bulk upload Planogram Images (CSV or Excel)",
     *     tags={"Planogram Images"},
     *     security={{"bearerAuth":{}}},
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
     *                     description="Upload a CSV or Excel file"
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
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Bulk upload completed"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="success",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="row", type="integer", example=2),
     *                         @OA\Property(property="message", type="string", example="Inserted successfully")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="failed",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="row", type="integer", example=3),
     *                         @OA\Property(property="error", type="string", example="Invalid merchandiser ID")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function bulkUpload(Request $request)
    {
        try {
            if (!$request->hasFile('file')) {
                return response()->json([
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'File is required'
                ], 400);
            }

            $file = $request->file('file');
            $results = $this->service->bulkUpload($file, auth()->id());

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Bulk upload completed',
                'data'    => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 422,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
 /**
 * @OA\Get(
 *     path="/api/merchendisher/planogram-image/export",
 *     summary="Export Planogram Image data in CSV or XLSX format",
 *     description="This API exports planogram image data filtered by optional date range in either CSV or XLSX format.",
 *     operationId="exportPlanogramImage",
 *     tags={"Planogram Images"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="format",
 *         in="query",
 *         required=true,
 *         description="Export file format. Allowed values: csv, xlsx",
 *         @OA\Schema(type="string", enum={"csv", "xlsx"})
 *     ),
 *     @OA\Parameter(
 *         name="valid_from",
 *         in="query",
 *         required=false,
 *         description="Filter records from this date (YYYY-MM-DD).",
 *         @OA\Schema(type="string", format="date")
 *     ),
 *     @OA\Parameter(
 *         name="valid_to",
 *         in="query",
 *         required=false,
 *         description="Filter records up to this date (YYYY-MM-DD). Must be after or equal to valid_from.",
 *         @OA\Schema(type="string", format="date")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="File export successful (CSV or XLSX file will be downloaded).",
 *         @OA\JsonContent(
 *             oneOf={
 *                 @OA\Schema(type="string", format="binary", description="CSV File"),
 *                 @OA\Schema(type="string", format="binary", description="XLSX File")
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="No data found for the given date range",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="message", type="string", example="No data found for the given date range.")
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
 *                 example={"format": {"The format field is required."}}
 *             )
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
        $planograms->load(['merchandiser', 'customer', 'shelf']);
        $data = $planograms->map(function ($item) {
            return [
                'ID'          => $item->id,
                'Merchandiser Name' => optional($item->merchandiser)->name, 
                'Customer Name'     => optional($item->customer)->business_name,       
                'Shelf Name'        => optional($item->shelf)->shelf_name,
                'image'      => $item->image,
                'Created At'  => $item->created_at->format('Y-m-d H:i:s'),
            ];
        });
        $fileName = 'planogram_image_' . now()->format('Y_m_d_H_i_s');
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
}
