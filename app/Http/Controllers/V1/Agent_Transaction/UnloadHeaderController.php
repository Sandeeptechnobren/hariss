<?php

namespace App\Http\Controllers\V1\Agent_Transaction;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Agent_Transaction\UnloadHeaderRequest;
use App\Http\Requests\V1\Agent_Transaction\UnloadHeaderUpdateRequest;
use App\Http\Resources\V1\Agent_Transaction\UnloadHeaderResource;
use App\Services\V1\Agent_Transaction\UnloadHeaderService;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UnloadHeaderFullExport;
use App\Exports\UnloadFullExport;
use App\Exports\UnloadCollapseExport;
use App\Helpers\LogHelper;
use App\Helpers\CommonLocationFilter;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Agent_Transaction\UnloadHeader;
use App\Models\Agent_Transaction\UnloadDetail;


/**
 * @OA\Tag(
 *     name="Salesman Unload Header",
 *     description="API endpoints for managing Unload Headers and their Details"
 * )
 */
class UnloadHeaderController extends Controller
{
    public function __construct(protected UnloadHeaderService $service) {}

    /**
     * @OA\Get(
     *     path="/api/agent_transaction/unload/list",
     *     tags={"Salesman Unload Header"},
     *     security={{"bearerAuth":{}}},
     *     summary="List all unloads with pagination and filters",
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="route_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="warehouse_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="List of unloads fetched successfully")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search',
                'warehouse_id',
                'route_id',
                'salesman_id',
                // 'region_id',
                'from_date',
                'to_date',
                'status'
            ]);

            $headers = $this->service->all(50, $filters);

            return ResponseHelper::paginatedResponse(
                'Unloads fetched successfully',
                UnloadHeaderResource::class,
                $headers
            );
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function globalFilter(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 50);
            $filters = $request->all();

            $unloads = $this->service->globalFilter($perPage, $filters);

            return ResponseHelper::paginatedResponse(
                'Unloads fetched successfully',
                UnloadHeaderResource::class,
                $unloads
            );
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/agent_transaction/unload/add",
     *     tags={"Salesman Unload Header"},
     *     security={{"bearerAuth":{}}},
     *     summary="Create a new Unload with details",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"warehouse_id","route_id","salesman_id","details"},
     *             @OA\Property(property="warehouse_id", type="integer", example=112),
     *             @OA\Property(property="route_id", type="integer", example=60),
     *             @OA\Property(property="salesman_id", type="integer", example=133),
     *             @OA\Property(property="latitude", type="string", example="28.6139"),
     *             @OA\Property(property="longtitude", type="string", example="77.2090"),
     *             @OA\Property(property="unload_from", type="string", example="Backend"),
     *             @OA\Property(
     *                 property="details",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"item_id","uom","qty"},
     *                     @OA\Property(property="item_id", type="integer", example=45),
     *                     @OA\Property(property="uom", type="integer", example=7),
     *                     @OA\Property(property="qty", type="number", example=25.5)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Unload created successfully")
     * )
     */
    public function store(UnloadHeaderRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $header = $this->service->store($validated);
            if ($header) {
                LogHelper::store(
                    '13',
                    '41',
                    'add',
                    null,
                    $header->getAttributes(),
                    auth()->id()
                );
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Unload created successfully',
                'data'    => new UnloadHeaderResource($header)
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // public function store(UnloadHeaderRequest $request): JsonResponse
    // {
    //     try {
    //         $header = $this->service->store($request->validated());
    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Unload created successfully',
    //             'data' => new UnloadHeaderResource($header)
    //         ], 201);
    //     } catch (Exception $e) {
    //         return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    //     }
    // }

    /**
     * @OA\Get(
     *     path="/api/agent_transaction/unload/{uuid}",
     *     tags={"Salesman Unload Header"},
     *     security={{"bearerAuth":{}}},
     *     summary="Fetch specific unload by UUID",
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="UUID of unload header",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Unload fetched successfully")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $header = $this->service->findByUuid($uuid);
            return response()->json([
                'status' => 'success',
                'data' => new UnloadHeaderResource($header)
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/agent_transaction/unload/update/{uuid}",
     *     tags={"Salesman Unload Header"},
     *     security={{"bearerAuth":{}}},
     *     summary="Update unload header and details by UUID",
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="UUID of unload to update",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="warehouse_id", type="integer", example=115),
     *             @OA\Property(property="route_id", type="integer", example=62),
     *             @OA\Property(property="salesman_id", type="integer", example=140),
     *             @OA\Property(property="latitude", type="number", example=27.2046),
     *             @OA\Property(property="longtitude", type="number", example=77.4977),
     *             @OA\Property(property="unload_from", type="string", example="mobile"),
     *             @OA\Property(
     *                 property="details",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="uuid", type="string", example="b3e9f9a2-9f3c-4b1b-b6e9-7a13eae73d8e"),
     *                     @OA\Property(property="item_id", type="integer", example=22),
     *                     @OA\Property(property="uom", type="integer", example=12),
     *                     @OA\Property(property="qty", type="number", example=40.75)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Unload updated successfully")
     * )
     */
    public function update(UnloadHeaderUpdateRequest $request, string $uuid): JsonResponse
    {
        try {
            $header = $this->service->updateByUuid($uuid, $request->validated());
            return response()->json([
                'status' => 'success',
                'message' => 'Unload updated successfully',
                'data' => new UnloadHeaderResource($header)
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/agent_transaction/unload/{uuid}",
     *     tags={"Salesman Unload Header"},
     *     security={{"bearerAuth":{}}},
     *     summary="Delete an unload by UUID",
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="UUID of unload to delete",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Unload deleted successfully")
     * )
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->service->deleteByUuid($uuid);
            return response()->json([
                'status' => 'success',
                'message' => 'Unload deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    //     /**
    //  * @OA\Get(
    //  *     path="/api/agent_transaction/unloads/export",
    //  *     summary="Export Unload Headers",
    //  *     description="Exports Unload Header data as an Excel (.xlsx) or CSV (.csv) file.",
    //  *     tags={"Unload Header"},
    //  *     @OA\Parameter(
    //  *         name="format",
    //  *         in="query",
    //  *         description="Export format (xlsx or csv)",
    //  *         required=false,
    //  *         @OA\Schema(
    //  *             type="string",
    //  *             enum={"xlsx", "csv"},
    //  *             default="xlsx"
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="Successful export - returns a downloadable file",
    //  *         @OA\Content(
    //  *             mediaType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    //  *             schema=@OA\Schema(type="string", format="binary")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=404,
    //  *         description="Not found or no data available",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="status", type="string", example="error"),
    //  *             @OA\Property(property="code", type="integer", example=404),
    //  *             @OA\Property(property="message", type="string", example="Unload not found")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=500,
    //  *         description="Server error",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="status", type="string", example="error"),
    //  *             @OA\Property(property="message", type="string", example="Internal server error")
    //  *         )
    //  *     )
    //  * )
    //  */
    public function exportUnloadHeader(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = 'unload_header_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'unloadexports/' . $filename;

        $export = new UnloadHeaderFullExport();

        if ($format === 'csv') {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
        } else {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
        }

        $appUrl = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json([
            'status' => 'success',
            'download_url' => $fullUrl,
        ]);
    }

    public function exportUnload(Request $request)
    {
        $uuid   = $request->input('uuid');
        $format = strtolower($request->input('format', 'xlsx'));

        $extension = $format === 'csv'
            ? 'csv'
            : ($format === 'pdf' ? 'pdf' : 'xlsx');

        $header = UnloadHeader::select('osa_code')
            ->where('uuid', $uuid)
            ->firstOrFail();

        $osaCode = preg_replace('/[^A-Za-z0-9\-]/', '', $header->osa_code); // safe filename

        $filename = 'Unload_' . $osaCode . '.' . $extension;
        $path     = 'unloadexports/' . $filename;
        if ($format === 'pdf') {

            $order = UnloadHeader::with([
                'warehouse',
                'salesman.subtype',
                'salesman.salesmanType'
            ])->where('uuid', $uuid)->firstOrFail();

            $orderDetails = UnloadDetail::with([
                'item',
                'uoms'
            ])->where('header_id', $order->id)->get();

            $pdf = Pdf::loadView('unload', [
                'order'        => $order,
                'orderDetails' => $orderDetails,
            ])->setPaper('A4', 'portrait');

            \Storage::disk('public')->put($path, $pdf->output());

            $appUrl  = rtrim(config('app.url'), '/');
            $fullUrl = $appUrl . '/storage/app/public/' . $path;

            return response()->json([
                'status'       => 'success',
                'download_url' => $fullUrl,
            ]);
        }
        $export = new UnloadFullExport($uuid);

        if ($format === 'csv') {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
        } else {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
        }

        $appUrl  = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json([
            'status'       => 'success',
            'download_url' => $fullUrl,
        ]);
    }

    public function exportUnloadCollapse(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = 'Unload_detail_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'unloadexportscollapse/' . $filename;

        $filters = $request->input('filter', []);

        $fromDate = $filters['from_date'] ?? null;
        $toDate   = $filters['to_date'] ?? null;

        $routeIds    = CommonLocationFilter::normalizeIds($filters['route_id'] ?? []);
        $salesmanIds = CommonLocationFilter::normalizeIds($filters['salesman_id'] ?? []);
        $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);

        $export = new UnloadCollapseExport(
            $fromDate,
            $toDate,
            $warehouseIds,
            $routeIds,
            $salesmanIds
        );
        // $export = new UnloadCollapseExport();

        if ($format === 'csv') {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
        } else {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
        }

        $appUrl = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json([
            'status' => 'success',
            'download_url' => $fullUrl,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/agent_transaction/unload/unload-data/{salesman_id}",
     *     tags={"Salesman Unload Header"},
     *     security={{"bearerAuth":{}}},
     *     summary="Fetch loads and their details for a specific salesman",
     *     description="Returns load headers with related details for the given salesman_id",
     *     @OA\Parameter(
     *         name="salesman_id",
     *         in="path",
     *         required=true,
     *         description="ID of the salesman",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Loads fetched successfully"
     *     ),
     *     @OA\Response(response=404, description="No loads found for this salesman"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function getUnloadData(int $salesman_id): JsonResponse
    {
        try {
            $date = request()->query('date');  // single date

            $unloadData = $this->service->calculateUnloadBySalesmanId(
                $salesman_id,
                $date
            );

            if (empty($unloadData)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No unload data found for this salesman'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $unloadData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // public function getUnloadData(int $salesman_id): JsonResponse
    // {
    //     try {
    //         $unloadData = $this->service->calculateUnloadBySalesmanId($salesman_id);

    //         if (empty($unloadData)) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'No unload data found for this salesman'
    //             ], 404);
    //         }

    //         return response()->json([
    //             'status' => 'success',
    //             'data' => $unloadData
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function getProjectSalesmanByRoute(Request $request): JsonResponse
    {
        $routeId = $request->route_id;

        if (!$routeId || !is_numeric($routeId)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Valid route_id is required'
            ], 422);
        }

        $salesmen = $this->service->getProjectSalesmanByRoute((int)$routeId);

        return response()->json([
            'status'  => 'success',
            'message' => 'Salesmen fetched successfully',
            'data'    => $salesmen
        ]);
    }
}
