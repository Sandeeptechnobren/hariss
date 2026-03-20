<?php

namespace App\Http\Controllers\V1\Merchendisher\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Merchendisher\Web\AsignInventoryRequest;
use App\Http\Resources\V1\Merchendisher\Web\AsignInventoryResource;
use App\Services\V1\Merchendisher\Web\AsignInventoryService;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;
use App\Exports\AsignInventoryExport;

class AsignInventoryController extends Controller
{
     protected $service;

    public function __construct(AsignInventoryService $service)
    {
        $this->service = $service;
    }
    
    /**
 * @OA\Get(
 *     path="/web/merchendisher_web/asign-inventory/list",
 *     tags={"AssignInventory"},
 *     summary="Get all assigned inventory (with optional global search)",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="Search by item code, item name, customer name, etc.",
 *         required=false,
 *         @OA\Schema(type="string", example="Amit")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Assignment Inventory fetched successfully"),
 *             @OA\Property(property="data", type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="uuid", type="string", example="9c50e8d1-e4f2-4e4e-8cde-1e7f9f0a1d91"),
 *                     @OA\Property(property="item_code", type="string", example="ITEM001"),
 *                     @OA\Property(property="item_uom", type="string", example="kg"),
 *                     @OA\Property(property="customer_id", type="integer", example=101),
 *                     @OA\Property(property="capacity", type="integer", example=100),
 *                 )
 *             )
 *         )
 *     )
 * )
 */
      public function index()
    {
        $inventories = $this->service->index();
        return ResponseHelper::paginatedResponse(
        'Asignment Inventory fetched successfully',
        AsignInventoryResource::class,
        $inventories
    );
    }
        /**
     * @OA\Post(
     *     path="/web/merchendisher_web/asign-inventory/asign",
     *     tags={"AssignInventory"},
     *     summary="Create a new assigned inventory",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"item_code", "item_uom", "customer_id", "capacity"},
     *             @OA\Property(property="item_uom", type="integer", example=13),
     *             @OA\Property(property="customer_id", type="integer", example=2),
     *             @OA\Property(property="capacity", type="integer", example=100)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Asignment Inventory created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="uuid", type="string", example="uuid-value"),
     *                 @OA\Property(property="item_code", type="string", example="ITEM001"),
     *                 @OA\Property(property="item_uom", type="string", example="kg"),
     *                 @OA\Property(property="customer_id", type="integer", example=101),
     *                 @OA\Property(property="capacity", type="integer", example=100),
     *             )
     *         )
     *     )
     * )
     */

    public function store(AsignInventoryRequest $request)
    {
        $customerItem = $this->service->store($request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Asignment Inventory created successfully',
            'data'    => new AsignInventoryResource($customerItem),
        ], 201);
    }

      /**
     * @OA\Get(
     *     path="/web/merchendisher_web/asign-inventory/{uuid}",
     *     tags={"AssignInventory"},
     *     summary="Get a single assigned inventory by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         example="9c50e8d1-e4f2-4e4e-8cde-1e7f9f0a1d91"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="uuid", type="string", example="uuid-value"),
     *             @OA\Property(property="item_code", type="string", example="ITEM001"),
     *             @OA\Property(property="item_uom", type="string", example="kg"),
     *             @OA\Property(property="customer_id", type="integer", example=101),
     *             @OA\Property(property="capacity", type="integer", example=100),
     *         )
     *     )
     * )
     */

    public function show($uuid)
    {
        $inventory = $this->service->show($uuid);
        return new AsignInventoryResource($inventory);
    }

     /**
     * @OA\Put(
     *     path="/web/merchendisher_web/asign-inventory/{uuid}",
     *     tags={"AssignInventory"},
     *     summary="Update an assigned inventory",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         example="9c50e8d1-e4f2-4e4e-8cde-1e7f9f0a1d91"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="item_code", type="string", example="ITEM001"),
     *             @OA\Property(property="item_uom", type="string", example="kg"),
     *             @OA\Property(property="customer_id", type="integer", example=101),
     *             @OA\Property(property="capacity", type="integer", example=100)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="uuid", type="string", example="uuid-value"),
     *             @OA\Property(property="item_code", type="string", example="ITEM001"),
     *             @OA\Property(property="item_uom", type="string", example="kg"),
     *             @OA\Property(property="customer_id", type="integer", example=101),
     *             @OA\Property(property="capacity", type="integer", example=100),
     *         )
     *     )
     * )
     */

    public function update(AsignInventoryRequest $request, $uuid)
    {
        $inventory = $this->service->update($request->validated(), $uuid);
        return new AsignInventoryResource($inventory);
    }

      /**
     * @OA\Delete(
     *     path="/web/merchendisher_web/asign-inventory/{uuid}",
     *     tags={"AssignInventory"},
     *     summary="Delete assigned inventory by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         example="9c50e8d1-e4f2-4e4e-8cde-1e7f9f0a1d91"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Deleted successfully")
     *         )
     *     )
     * )
     */

    public function destroy($uuid)
    {
        $this->service->destroy($uuid);
        return response()->json(['message' => 'Deleted successfully']);
    }

    /**
 * @OA\Post(
 *     path="/web/merchendisher_web/asign-inventory/bulk-upload",
 *     summary="Bulk upload inventory via file (CSV, XLSX, XLS)",
 *     tags={"AssignInventory"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Upload a CSV or Excel file",
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"file"},
 *                 @OA\Property(
 *                     property="file",
 *                     type="string",
 *                     format="binary",
 *                     description="CSV, XLSX or XLS file"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Bulk upload completed successfully"
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error (e.g. file type or size)"
 *     )
 * )
 */


     public function bulkUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx,xls|max:2048',
        ]);

        $inserted = $this->service->bulkUpload($request->file('file'));

        return response()->json([
            'status'  => 'success',
            'message' => 'Bulk upload completed successfully',
            'inserted_count' => count($inserted),
            'data'    => AsignInventoryResource::collection($inserted),
        ], 201);
    }

    /**
 * @OA\Get(
 *     path="/web/merchendisher_web/asign-inventory/export-stocks",
 *     summary="Export Assign Inventory data in CSV or XLSX format",
 *     description="Download inventory records as CSV or XLSX based on the 'format' query parameter.",
 *     tags={"AssignInventory"},
 *     @OA\Parameter(
 *         name="format",
 *         in="query",
 *         required=false,
 *         description="Export file format (csv or xlsx). Defaults to csv if not provided.",
 *         @OA\Schema(type="string", enum={"csv","xlsx"}, example="csv")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="File downloaded successfully (CSV or XLSX)",
 *         @OA\MediaType(
 *             mediaType="application/octet-stream",
 *             @OA\Schema(type="string", format="binary")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="No data available for export",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="No data available for export")
 *         )
 *     ),
 *     security={{"bearerAuth": {}}}
 * )
 */
     public function export(Request $request)
    {
        $format = $request->input('format', 'csv'); 
        $data = $this->service->getExportData(); 
        \Log::info('Export Data:', $data->toArray());

        if ($data->isEmpty()) {
            \Log::error('No data available for export.');
            return response()->json(['error' => 'No data available for export'], 500);
        }

        if ($format == 'csv') {
            return Excel::download(new AsignInventoryExport($data), 'asign_inventory.csv');
        } else {
            return Excel::download(new AsignInventoryExport($data), 'asign_inventory.xlsx', ExcelFormat::XLSX);

        }
    }
}