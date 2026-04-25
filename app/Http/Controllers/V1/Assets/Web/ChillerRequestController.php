<?php

namespace App\Http\Controllers\V1\Assets\Web;

use App\Http\Controllers\Controller;
use App\Models\ChillerRequest;
use App\Helpers\CommonLocationFilter;
use App\Exports\AssetRequestExport;
use App\Http\Requests\V1\Assets\Web\ChillerRequestRequest;
use App\Http\Requests\V1\Assets\Web\UpdateChillerRequestRequest;
use App\Http\Resources\V1\Assets\Web\ChillerRequestResource;
use App\Http\Resources\V1\Assets\Web\GetCRFData;
use App\Services\V1\Assets\Web\ChillerRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Storage;
use PDF;

/**
 * @OA\Schema(
 *     schema="ChillerRequest",
 *     type="object",
 *     title="ChillerRequest",
 *     description="Schema for creating or updating a Chiller record",
 *     @OA\Property(property="owner_name", type="string", example="Ramesh Kumar"),
 *     @OA\Property(property="status", type="integer", enum={0,1}, example=1),
 *     @OA\Property(property="fridge_status", type="integer", enum={0,1}, example=0),
 *     @OA\Property(property="customer_id", type="integer", example=12),
 *     @OA\Property(property="warehouse_id", type="integer", example=3),
 *     @OA\Property(property="salesman_id", type="integer", example=9),
 *     @OA\Property(property="outlet_id", type="integer", example=5),
 *     @OA\Property(property="stock_share_with_competitor", type="integer", example=50),
 *     @OA\Property(property="contact_number", type="string", example="+91-9876543210"),
 *     @OA\Property(property="landmark", type="string", example="Near City Mall"),
 *     @OA\Property(property="existing_coolers", type="string", example="2"),
 *     @OA\Property(property="outlet_weekly_sale_volume", type="string", example="1500"),
 *     @OA\Property(property="display_location", type="string", example="Front Display Area"),
 *     @OA\Property(property="chiller_safty_grill", type="string", example="Installed"),
 *     @OA\Property(property="manager_sales_marketing", type="integer", example=15),
 *     @OA\Property(property="national_id", type="string", example="AB1234567"),
 *     @OA\Property(property="outlet_stamp", type="string", example="Outlet Stamp Verified"),
 *     @OA\Property(property="model", type="string", example="LG-CHL-2024"),
 *     @OA\Property(property="hil", type="string", example="HIL-009"),
 *     @OA\Property(property="ir_reference_no", type="string", example="IR-REF-789"),
 *     @OA\Property(property="installation_done_by", type="string", example="CoolCare Pvt Ltd"),
 *     @OA\Property(property="date_lnitial", type="string", example="2025-10-01"),
 *     @OA\Property(property="date_lnitial2", type="string", example="2025-10-05"),
 *     @OA\Property(property="contract_attached", type="string", example="Yes"),
 *     @OA\Property(property="machine_number", type="string", example="MCH-2456"),
 *     @OA\Property(property="brand", type="string", example="Samsung"),
 *     @OA\Property(property="lc_letter", type="string", example="LC-Document-123"),
 *     @OA\Property(property="trading_licence", type="string", example="LIC-998877"),
 *     @OA\Property(property="password_photo", type="string", example="PASS-IMG-001"),
 *     @OA\Property(property="outlet_address_proof", type="string", example="Proof Document"),
 *     @OA\Property(property="chiller_asset_care_manager", type="integer", example=22),
 *     @OA\Property(property="chiller_manager_id", type="integer", example=33),
 *     @OA\Property(property="is_merchandiser", type="integer", enum={0,1}, example=1),
 *     @OA\Property(property="iro_id", type="integer", example=44),
 *     @OA\Property(property="remark", type="string", example="All documents verified and approved."),
 *     @OA\Property(property="password_photo_file", type="string", format="binary", description="Upload password photo file"),
 *     @OA\Property(property="lc_letter_file", type="string", format="binary", description="Upload LC letter file"),
 *     @OA\Property(property="trading_licence_file", type="string", format="binary", description="Upload trading licence file"),
 *     @OA\Property(property="outlet_stamp_file", type="string", format="binary", description="Upload outlet stamp file"),
 *     @OA\Property(property="outlet_address_proof_file", type="string", format="binary", description="Upload outlet address proof file"),
 *     @OA\Property(property="sign__customer_file", type="string", format="binary", description="Upload customer signature file"),
 *     @OA\Property(property="national_id_file", type="string", format="binary", description="Upload national ID file")
 * )
 */
class ChillerRequestController extends Controller
{
    use ApiResponse;

    protected ChillerRequestService $service;

    public function __construct(ChillerRequestService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/assets/chiller-request/list",
     *     tags={"ChillerRequest"},
     *     summary="Get all chiller requests with pagination and optional filters",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="outlet_name", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="List of chiller requests",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Chiller Requests fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/ChillerRequest")
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="page", type="integer", example=1),
     *                 @OA\Property(property="limit", type="integer", example=10),
     *                 @OA\Property(property="totalPages", type="integer", example=5),
     *                 @OA\Property(property="totalRecords", type="integer", example=50)
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage  = $request->get('limit', 50);
        $dropdown = $request->boolean('dropdown', false);
        $filters = $request->only([
            'asset_code',
            'asset_name',
            'warehouse_id',
            'salesman_id',
            'status',
            'filter'
        ]);
        if ($dropdown) {
            $data = $this->service->getAll($perPage, $filters, true);

            return $this->success(
                $data,
                'Assets (dropdown) fetched successfully'
            );
        }
        $assets = $this->service->getAll($perPage, $filters, false);
        return $this->success(
            ChillerRequestResource::collection($assets->items()),
            'Assets Request fetched successfully',
            200,
            [
                'page'         => $assets->currentPage(),
                'limit'        => $assets->perPage(),
                'totalPages'   => $assets->lastPage(),
                'totalRecords' => $assets->total(),
            ]
        );
    }


    /**
     * @OA\Post(
     *     path="/api/assets/chiller-request/add",
     *     tags={"ChillerRequest"},
     *     summary="Create a new chiller",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ChillerRequest")),
     *     @OA\Response(response=200, description="Chiller created successfully")
     * )
     */
    public function store(ChillerRequestRequest $request): JsonResponse
    {
        // if ($resp = $this->authorizeRoleAccess(_FUNCTION_)) return $resp;

        $chiller = $this->service->create($request->validated());
        return $this->success(new ChillerRequestResource($chiller), 'Chiller created successfully', 200);
    }


    /**
     * @OA\Get(
     *     path="/api/assets/chiller-request/{uuid}",
     *     tags={"ChillerRequest"},
     *     summary="Get a single chiller request by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Chiller Request details", @OA\JsonContent(ref="#/components/schemas/ChillerRequest")),
     *     @OA\Response(response=404, description="Chiller Request not found")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        $chillerRequest = $this->service->findByUuid($uuid);
        if (!$chillerRequest) {
            return $this->fail('Chiller Request not found', 404);
        }
        return $this->success(new ChillerRequestResource($chillerRequest), 'Chiller Request fetched successfully');
    }


    /**
     * @OA\Put(
     *     path="/api/assets/chiller-request/{uuid}",
     *     tags={"ChillerRequest"},
     *     summary="Update a chiller by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ChillerRequest")),
     *     @OA\Response(response=200, description="Chiller updated successfully"),
     *     @OA\Response(response=404, description="Chiller not found")
     * )
     */
    public function update(UpdateChillerRequestRequest $request, string $uuid): JsonResponse
    {
        // if ($resp = $this->authorizeRoleAccess(_FUNCTION_)) return $resp;
        // dd($uuid);
        try {
            $validatedData = $request->validated();
            $updated = $this->service->updateByUuid($uuid, $validatedData);
            // dd($updated);
            return $this->success(new ChillerRequestResource($updated->fresh()), 'Chiller updated successfully');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 404);
        }
    }


    /**
     * @OA\Delete(
     *     path="/api/assets/chiller-request/{uuid}",
     *     tags={"ChillerRequest"},
     *     summary="Delete a chiller request by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Chiller Request deleted successfully"),
     *     @OA\Response(response=404, description="Chiller Request not found")
     * )
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->service->deleteByUuid($uuid);
            return $this->success(null, 'Chiller Request deleted successfully');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/assets/chiller-request/generate-code",
     *     tags={"ChillerRequest"},
     *     summary="Generate unique ChillerRequest code",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Unique ChillerRequest code generated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Unique ChillerRequest code generated successfully"),
     *             @OA\Property(property="data", type="object", @OA\Property(property="osa_code", type="string", example="CR001"))
     *         )
     *     )
     * )
     */
    public function generateCode(): JsonResponse
    {
        try {
            $osa_code = $this->service->generateCode();
            return $this->success(['osa_code' => $osa_code], 'Unique ChillerRequest code generated successfully');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/assets/chiller-request/global_search",
     *     tags={"ChillerRequest"},
     *     summary="Global search ChillerRequest with pagination",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer"),
     *         description="Number of records per page (default: 10)"
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         description="Search keyword for areas"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="ChillerRequest fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="ChillerRequest fetched successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items()),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="page", type="integer", example=1),
     *                 @OA\Property(property="limit", type="integer", example=10),
     *                 @OA\Property(property="totalPages", type="integer", example=5),
     *                 @OA\Property(property="totalRecords", type="integer", example=50),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to search areas"
     *     )
     * )
     */

    public function global_search(Request $request)
    {
        try {
            $perPage    = $request->get('per_page', 10);
            $searchTerm = $request->get('search');

            $chillerRequests = $this->service->globalSearch($perPage, $searchTerm);

            return response()->json([
                "status" => "success",
                "code" => 200,
                "message" => "ChillerRequest fetched successfully",
                "data" => ChillerRequestResource::collection($chillerRequests->items()),
                "pagination" => [
                    "page" => $chillerRequests->currentPage(),
                    "limit" => $chillerRequests->perPage(),
                    "totalPages" => $chillerRequests->lastPage(),
                    "totalRecords" => $chillerRequests->total(),
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



    // public function approvedChillerRequests(Request $request)
    // {
    //     $filters = [
    //         'region'    => $request->query('region'),
    //         'area'      => $request->query('area'),
    //         'warehouse' => $request->query('warehouse'),
    //     ];

    //     $data = $this->service->getApprovedChillerRequests($filters);

    //     return response()->json([
    //         'status'  => 'success',
    //         'code'    => 200,
    //         'message' => 'Approved chiller requests fetched successfully',
    //         'data'    => $data
    //     ]);
    // }

    public function filterChillerRequests(Request $request)
    {
        $filters = [
            'warehouse_id' => $request->query('warehouse_id'),
            'model_number' => $request->query('model_number'),
            'salesman_id'  => $request->query('salesman_id'),
        ];

        $paginated = $this->service->filterChillerRequests($filters);

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Assets fetched successfully',

            // Apply resource here
            'data'    => GetCRFData::collection($paginated->getCollection()),

            'pagination' => [
                'total'         => $paginated->total(),
                'per_page'      => $paginated->perPage(),
                'current_page'  => $paginated->currentPage(),
                'last_page'     => $paginated->lastPage(),
            ],
        ]);
    }


    public function exportChillerRequestPdf(Request $request)
    {
        $id = $request->input('id');
        if (!$id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'IR Detail ID is required.'
            ], 422);
        }

        $record = $this->service->getAgreementByIrDetailId($id);
        if (!$record) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized or agreement data not found.'
            ], 403);
        }

        $filename = 'asset_agreement_' . now()->format('Ymd_His') . '.pdf';
        $path     = 'chillerexports/' . $filename;

        $pdf = PDF::loadView('chiller_request', [
            'agreement' => $record
        ])->setPaper('A4', 'portrait');

        Storage::disk('public')->makeDirectory('chillerexports');
        Storage::disk('public')->put($path, $pdf->output());

        $appUrl  = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json([
            'status'       => 'success',
            'download_url' => $fullUrl
        ]);
    }


    // public function exportChillerRequestPdf(Request $request)
    // {
    //     $id     = $request->input('id');
    //     $format = strtolower($request->input('format', 'pdf'));

    //     if (!$id) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => 'Chiller request ID is required.'
    //         ], 422);
    //     }

    //     $extension = 'pdf';
    //     $filename  = 'asset_agreement_' . now()->format('Ymd_His') . '.' . $extension;
    //     $path      = 'chillerexports/' . $filename;

    //     $record = ChillerRequest::find($id);
    //     if (!$record) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => 'Chiller request not found.'
    //         ], 404);
    //     }

    //     $pdf = \PDF::loadView('chiller_request', [
    //         'record' => $record
    //     ])->setPaper('A4');

    //     \Storage::disk('public')->makeDirectory('chillerexports');

    //     \Storage::disk('public')->put($path, $pdf->output());

    //     $appUrl  = rtrim(config('app.url'), '/');
    //     $fullUrl = $appUrl . '/storage/app/public/' . $path;

    //     return response()->json([
    //         'status'       => 'success',
    //         'download_url' => $fullUrl,
    //     ]);
    // }
    // public function export(Request $request)
    //     {
    //         try {
    //             // dd($request);
    //             $filters = [
    //                 'status'     => $request['filter']['status'],
    //                 'region_id'     => $request['filter']['region_id'],
    //                 'user_id'     => $request['filter']['user_id'],
    //                 'warehouse_id'     => $request['filter']['warehouse_id'],
    //                 'route_id'     => $request['filter']['route_id'],
    //                 'salesman_id'     => $request['filter']['salesman_id'],
    //                 'from_date'     => $request['filter']['from_date'],
    //                 'to_date'     => $request['filter']['to_date'],
    //                 'request_status'     => $request['filter']['request_status'],
    //             ];

    //             $format = strtolower($request->get('format', 'xlsx'));

    //             if (!in_array($format, ['xlsx', 'csv'])) {
    //                 $format = 'xlsx';
    //             }
    //             $filename = 'crf_export_' . now()->format('Ymd_His') . '.' . $format;
    //             $path     = 'exports/chiller/' . $filename;
    //             \Storage::disk('public')->makeDirectory('exports/chiller');
    //             \Maatwebsite\Excel\Facades\Excel::store(
    //                 new \App\Exports\CRFRequestExport($filters),
    //                 $path,
    //                 'public'
    //             );
    //             $url = rtrim(config('app.url'), '/') . '/storage/app/public/' . $path;
    //             return response()->json([
    //                 'status'       => 'success',
    //                 'message'      => strtoupper($format) . ' export generated successfully',
    //                 'download_url' => $url
    //             ]);
    //         } catch (\Throwable $e) {
    //             dd($e);
    //             return response()->json([
    //                 'status'  => 'error',
    //                 'message' => 'Failed to generate CRF export',
    //                 'error'   => config('app.debug') ? $e->getMessage() : null
    //             ], 500);
    //         }
    //         catch (\Exceptions $e) {
    //             dd($e);
    //             return response()->json([
    //                 'status'  => 'error',
    //                 'message' => 'Failed to generate CRF export',
    //                 'error'   => config('app.debug') ? $e->getMessage() : null
    //             ], 500);
    //         }
    //     }
    public function export(Request $request)
    {
        try {
            $filters = $request->input('filter', []);
            $format = strtolower($request->get('format', 'xlsx'));

            if (!in_array($format, ['xlsx', 'csv'])) {
                $format = 'xlsx';
            }

            $filename = 'Chiller_Request_' . now()->format('Ymd_His') . '.' . $format;
            $path = 'exports/chiller/' . $filename;

            \Storage::disk('public')->makeDirectory('exports/chiller');

            \Maatwebsite\Excel\Facades\Excel::store(
                new \App\Exports\CRFRequestExport($filters),
                $path,
                'public'
            );

            $url = asset('storage/' . $path);

            return response()->json([
                'status' => 'success',
                'message' => strtoupper($format) . ' export generated successfully',
                'download_url' => $url
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate CRF export',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function getCRFData(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 50);
            $filters = $request->all();

            $requests = $this->service->getCRFData($perPage, $filters);

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Chiller requests fetched successfully',
                'data'    => $requests->items(),
                'pagination' => [
                    'page'         => $requests->currentPage(),
                    'limit'        => $requests->perPage(),
                    'totalPages'   => $requests->lastPage(),
                    'totalRecords' => $requests->total(),
                ],
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to fetch chiller requests',
            ], 500);
        }
    }
    public function globalFilter(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 50);
            $filters = $request->all();

            $requests = $this->service->globalFilter($perPage, $filters);

            return $this->success(
                ChillerRequestResource::collection($requests->items()),
                'Asset Request fetched successfully',
                200,
                [
                    'page'         => $requests->currentPage(),
                    'limit'        => $requests->perPage(),
                    'totalPages'   => $requests->lastPage(),
                    'totalRecords' => $requests->total(),
                ]
            );
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to fetch chiller requests',
            ], 500);
        }
    }

    public function exportPdf($uuid)
    {
        $path = $this->service->generateAndStorePdf($uuid);

        if (!$path) {
            return response()->json([
                'message' => 'Record not found'
            ], 404);
        }

        $appUrl  = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json([
            'message' => 'PDF generated successfully',
            'url' => $fullUrl
        ]);
    }



    public function exportAssetRequest(Request $request)
    {
        $format    = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';

        $date = now()->format('Ymd_His');
        $random = rand(1000, 9999);

        $filename = "CRF_{$date}_{$random}.{$extension}";
        $path = 'crfexports/' . $filename;

        $filters = $request->input('filter', []);
        // dd($filters);
        $fromDate = $filters['from_date'] ?? null;
        $toDate   = $filters['to_date'] ?? null;

        /** ✅ request_status (MULTIPLE) */
        $requestStatus = !empty($filters['request_status'])
            ? array_map('intval', explode(',', $filters['request_status']))
            : [];

        /** ✅ ONLY THIS (IMPORTANT) */
        $resolvedWarehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);

        $inputWarehouseIds = !empty($filters['warehouse_id'])
            ? array_map('intval', explode(',', $filters['warehouse_id']))
            : [];

        $warehouseIds = !empty($inputWarehouseIds)
            ? array_values(array_intersect($resolvedWarehouseIds, $inputWarehouseIds))
            : $resolvedWarehouseIds;

        $export = new AssetRequestExport(
            $fromDate,
            $toDate,
            $requestStatus,
            $warehouseIds
        );

        \Maatwebsite\Excel\Facades\Excel::store(
            $export,
            $path,
            'public',
            $format === 'csv'
                ? \Maatwebsite\Excel\Excel::CSV
                : \Maatwebsite\Excel\Excel::XLSX
        );

        $fullUrl = rtrim(config('app.url'), '/') . '/storage/app/public/' . $path;

        return response()->json([
            'status' => 'success',
            'download_url' => $fullUrl,
        ]);
    }
}
