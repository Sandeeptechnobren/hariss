<?php

namespace App\Http\Controllers\V1\Assets\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Assets\Web\AddChillerRequest;
use App\Http\Requests\V1\Assets\Web\UpdateChillerRequest;
use App\Http\Resources\V1\Assets\Web\ChillerResource;
use App\Services\V1\Assets\Web\ChillerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Helpers\CommonLocationFilter;
use App\Traits\RoleAuthorization;
use App\Models\AddChiller;
use App\Exports\AddChillerFullExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @OA\Schema(
 *     schema="Chiller",
 *     type="object",
 *     title="Chiller",
 *     description="Schema for Chiller object",
 *     @OA\Property(property="serial_number", type="string", example="SN123456"),
 *     @OA\Property(property="asset_number", type="string", example="ASSET789"),
 *     @OA\Property(property="model_number", type="string", example="MDL-2025"),
 *     @OA\Property(property="description", type="string", example="Chiller for storing beverages"),
 *     @OA\Property(property="acquisition", type="string", format="date", example="2025-01-10"),
 *     @OA\Property(
 *         property="vender_details",
 *         type="array",
 *         @OA\Items(type="integer"),
 *         example={3,4,5}
 *     ),
 *     @OA\Property(property="manufacturer", type="string", example="CoolTech Manufacturing"),
 *     @OA\Property(property="country_id", type="integer", example=1),
 *     @OA\Property(property="type_name", type="string", example="Double Door"),
 *     @OA\Property(property="sap_code", type="string", example="SAP-CH-001"),
 *     @OA\Property(property="status", type="integer", enum={0,1}, example=1),
 *     @OA\Property(property="is_assign", type="integer", enum={0,1,2}, example=0),
 *     @OA\Property(property="customer_id", type="integer", example=1),
 *     @OA\Property(property="agreement_id", type="integer", example=55),
 *     @OA\Property(property="document_type", type="string", enum={"ACF","CRF"}, example="ACF"),
 *     @OA\Property(property="document_id", type="integer", example=2001),
 * )
 */
class ChillerController extends Controller
{
    use ApiResponse, RoleAuthorization;

    protected ChillerService $service;

    public function __construct(ChillerService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/assets/chiller/list_chillers",
     *     tags={"Chiller"},
     *     summary="Get all chillers with pagination and optional filters",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="fridge_code", in="query", required=false, @OA\Schema(type="string", example="CH001")),
     *     @OA\Response(
     *         response=200,
     *         description="List of chillers",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Chillers fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Chiller")
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
        $perPage  = (int) $request->get('limit', 50);
        $filters  = $request->only(['osa_code', 'serial_number']);
        $dropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);

        $chillers = $this->service->all($perPage, $filters, $dropdown);

        return response()->json(array_filter([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Chillers fetched successfully',

            // 🔹 Dropdown → raw list
            // 🔹 Normal → resource collection
            'data' => $dropdown
                ? $chillers
                : ChillerResource::collection($chillers->items()),

            // 🔹 Pagination ONLY when dropdown = false
            'pagination' => $dropdown ? null : [
                'page'         => $chillers->currentPage(),
                'limit'        => $chillers->perPage(),
                'totalPages'   => $chillers->lastPage(),
                'totalRecords' => $chillers->total(),
            ],
        ]));
    }


    /**
     * @OA\Get(
     *     path="/api/assets/chiller/{uuid}",
     *     tags={"Chiller"},
     *     summary="Get a single chiller by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Chiller details", @OA\JsonContent(ref="#/components/schemas/Chiller")),
     *     @OA\Response(response=404, description="Chiller not found")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        // if ($resp = $this->authorizeRoleAccess(__FUNCTION__)) return $resp;

        $chiller = $this->service->findByUuid($uuid);
        if (!$chiller) {
            return $this->fail('Chiller not found', 404);
        }
        return $this->success(new ChillerResource($chiller), 'Chiller fetched successfully');
    }

    /**
     * @OA\Get(
     *     path="/api/assets/chiller/generate-code",
     *     tags={"Chiller"},
     *     security={{"bearerAuth":{}}},
     *     summary="Generate unique chiller code",
     *     @OA\Response(
     *         response=200,
     *         description="Unique chiller code generated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Unique chiller code generated successfully"),
     *             @OA\Property(property="data", type="object", @OA\Property(property="fridge_code", type="string", example="CH001"))
     *         )
     *     )
     * )
     */
    public function generateCode(): JsonResponse
    {
        // if ($resp = $this->authorizeRoleAccess(__FUNCTION__)) return $resp;

        try {
            $fridge_code = $this->service->generateCode();
            return $this->success(['fridge_code' => $fridge_code], 'Unique Chiller code generated successfully');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/assets/chiller/add_chiller",
     *     tags={"Chiller"},
     *     summary="Create a new chiller",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Chiller")),
     *     @OA\Response(response=200, description="Chiller created successfully")
     * )
     */
    public function store(AddChillerRequest $request): JsonResponse
    {
        $chiller = $this->service->create($request->all());
        return $this->success(new ChillerResource($chiller), 'Chiller created successfully', 200);
    }

    /**
     * @OA\Put(
     *     path="/api/assets/chiller/{uuid}",
     *     tags={"Chiller"},
     *     summary="Update a chiller by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Chiller")),
     *     @OA\Response(response=200, description="Chiller updated successfully"),
     *     @OA\Response(response=404, description="Chiller not found")
     * )
     */
    public function update(UpdateChillerRequest $request, string $uuid): JsonResponse
    {
        try {
            $validated = $request->validated();
            $updated = $this->service->updateByUuid($uuid, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Chiller updated successfully',
                'data' => new ChillerResource($updated),
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    // /**
    //  * @OA\Delete(
    //  *     path="/api/assets/chiller/{uuid}",
    //  *     tags={"Chiller"},
    //  *     summary="Delete a chiller by UUID",
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
    //  *     @OA\Response(response=200, description="Chiller deleted successfully"),
    //  *     @OA\Response(response=404, description="Chiller not found")
    //  * )
    //  */
    // public function destroy(string $uuid): JsonResponse
    // {
    //     if ($resp = $this->authorizeRoleAccess(__FUNCTION__)) return $resp;

    //     try {
    //         $this->service->deleteByUuid($uuid);
    //         return $this->success(null, 'Chiller deleted successfully');
    //     } catch (\Exception $e) {
    //         return $this->fail($e->getMessage(), 404);
    //     }
    // }


    public function exportChillers(Request $request)
    {
        $uuid = $request->input('uuid'); // optional filter
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';

        $filename = 'Fridge_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'addchillerexports/' . $filename;


        $filters = $request->input('filter', []);
        $model   = $filters['model_id'] ?? null;
        $status = $filters['status'] ?? [];
        if (!empty($status)) {
            if (!is_array($status)) {
                $status = explode(',', $status);
            }
            $status = array_filter(array_map('intval', $status));
        } else {
            $status = []; // ✅ no default [null]
        }
        $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);

        $export = new AddChillerFullExport(
            $uuid,
            $warehouseIds,
            $status,
            $model
        );

        Excel::store(
            $export,
            $path,
            'public',
            $format === 'csv'
                ? \Maatwebsite\Excel\Excel::CSV
                : \Maatwebsite\Excel\Excel::XLSX
        );

        // FIXED PATH SAME STYLE AS YOUR CODE
        $fullUrl = rtrim(config('app.url'), '/') . '/storage/app/public/' . $path;

        return response()->json([
            'status' => 'success',
            'uuid' => $uuid,
            'download_url' => $fullUrl,
        ]);
    }


    // public function getChiller(Request $request)
    // {
    //     $serial = $request->get('serial_number');

    //     $records = $this->service->getBySerialNo($serial);

    //     if ($records->isEmpty()) {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => 'No records found',
    //         ], 404);
    //     }

    //     return response()->json([
    //         'status'  => true,
    //         'message' => 'Records fetched successfully',
    //         'data'    => $records
    //     ], 200);
    // }



    public function globalSearch(Request $request)
    {
        $query = $request->get('query');

        $records = $this->service->globalSearch($query);

        if ($records->isEmpty()) {
            return response()->json([
                'status'  => false,
                'message' => 'No records found'
            ], 404);
        }

        return ChillerResource::collection($records);
    }

    public function transfer(Request $request)
    {
        $request->validate([
            'from_warehouse_id' => 'required|integer|exists:tbl_warehouse,id',
            'to_warehouse_id'   => 'required|integer|different:from_warehouse_id|exists:tbl_warehouse,id',
        ]);

        $this->service->transfer(
            $request->from_warehouse_id,
            $request->to_warehouse_id
        );

        return response()->json([
            'status'  => true,
            'code'    => 200,
            'message' => 'Warehouse chiller transfer completed successfully'
        ]);
    }

    public function transferindex(Request $request): JsonResponse
    {
        $request->validate([
            'warehouse_id'      => 'nullable|integer',
            'from_warehouse_id' => 'nullable|integer',
            'to_warehouse_id'   => 'nullable|integer',
            'from_date'         => 'nullable|date',
            'to_date'           => 'nullable|date|after_or_equal:from_date',
            'limit'             => 'nullable|integer|min:1'
        ]);

        $invoices = $this->service->transferlist($request);

        $pagination = [
            'current_page' => $invoices->currentPage(),
            'last_page'    => $invoices->lastPage(),
            'per_page'     => $invoices->perPage(),
            'total'        => $invoices->total(),
        ];

        return response()->json([
            'status'     => true,
            'code'       => 200,
            'message'    => 'Update warehouse chiller list fetched successfully',
            'data'       => $invoices->items(),
            'pagination' => $pagination
        ]);
    }
    public function filterByStatus(Request $request): JsonResponse
    {
        // dd($request);
        $perPage = (int) $request->input('per_page', 10);

        $data = $this->service->filterByStatus($request->all(), $perPage);

        return response()->json([
            'status'     => 'success',
            'code'       => 200,
            'message'    => 'Chillers fetched successfully',
            'data'       => ChillerResource::collection($data->items()),
            'pagination' => [
                'page'         => $data->currentPage(),
                'limit'        => $data->perPage(),
                'totalPages'   => $data->lastPage(),
                'totalRecords' => $data->total(),
            ]
        ]);
    }

    public function filterData(Request $request): JsonResponse
    {
        // dd($request);
        $perPage = (int) $request->input('per_page', 50);

        $data = $this->service->filterData($request->all(), $perPage);

        return response()->json([
            'status'     => 'success',
            'code'       => 200,
            'message'    => 'Chillers fetched successfully',
            'data'       => ChillerResource::collection($data->items()),
            'pagination' => [
                'page'         => $data->currentPage(),
                'limit'        => $data->perPage(),
                'totalPages'   => $data->lastPage(),
                'totalRecords' => $data->total(),
            ]
        ]);
    }


    public function getByWarehouse(Request $request): JsonResponse
    {
        $request->validate([
            'warehouse_id' => ['required']
        ]);

        $perPage = (int) $request->input('per_page', 10);

        /**
         * Normalize warehouse_id
         * Supports:
         * - warehouse_id=68
         * - warehouse_id=68,69
         * - warehouse_id[]=68&warehouse_id[]=69
         */
        $warehouseIds = $request->input('warehouse_id');

        if (is_string($warehouseIds)) {
            $warehouseIds = explode(',', $warehouseIds);
        }

        $warehouseIds = array_map('intval', (array) $warehouseIds);

        $data = $this->service->getByWarehouseId($warehouseIds, $perPage);

        return response()->json([
            'status'     => 'success',
            'code'       => 200,
            'message'    => 'Chillers fetched successfully',
            'data'       => ChillerResource::collection($data->items()),
            'pagination' => [
                'page'         => $data->currentPage(),
                'limit'        => $data->perPage(),
                'totalPages'   => $data->lastPage(),
                'totalRecords' => $data->total(),
            ]
        ]);
    }


    public function import(Request $request): JsonResponse
    {
        // 🔹 Safe limits for large CSV
        ini_set('memory_limit', '512M');
        set_time_limit(0);

        $request->validate([
            'file' => ['required', 'mimes:csv,txt']
        ]);

        $file   = $request->file('file');
        $userId = auth()->id();

        $handle = fopen($file->getRealPath(), 'r');

        if (!$handle) {
            return response()->json([
                'status'  => 'error',
                'code'    => 400,
                'message' => 'Unable to read CSV file'
            ]);
        }

        /**
         * ✅ EXACT DB COLUMNS (tbl_add_chillers)
         */
        $tableColumns = [
            'osa_code',
            'serial_number',
            'assets_category',
            'model_number',
            'acquisition',
            'vender',
            'manufacturer',
            'country_id',
            'assets_type',
            'sap_code',
            'status',
            'remarks',
            'branding',
            'trading_partner_number',
            'capacity',
            'manufacturing_year',
            'warehouse_id',
            'region_id',
            'customer_id',
            'is_assign',
            'year',
            'agreement_id',
            'document_type',
            'document_id',
            'print_status',
            'created_at',
        ];

        /**
         * 🔹 NUMERIC / INTEGER COLUMNS (PostgreSQL strict)
         */
        $numericColumns = [
            'assets_category',
            'manufacturer',
            'country_id',
            'warehouse_id',
            'region_id',
            'customer_id',
            'agreement_id',
            'document_id',
            'print_status',
            'status',
            'is_assign',
            'branding',
        ];

        /**
         * 🔹 Read & clean CSV header
         */
        $rawHeader = fgetcsv($handle);
        $rawHeader = array_map('trim', $rawHeader);
        $rawHeader = array_filter($rawHeader);           // remove empty headers
        $rawHeader = array_diff($rawHeader, ['id']);     // ignore auto id
        $header    = array_values($rawHeader);

        /**
         * 🔹 Validate headers (block only unknown columns)
         */
        $invalidHeaders = array_diff($header, $tableColumns);

        if (!empty($invalidHeaders)) {
            fclose($handle);
            return response()->json([
                'status'  => 'error',
                'code'    => 422,
                'message' => 'Invalid headers found in CSV',
                'errors'  => array_values($invalidHeaders)
            ]);
        }

        DB::beginTransaction();

        $inserted = 0;
        $skipped  = 0;
        $batch    = [];

        try {
            while (($row = fgetcsv($handle)) !== false) {

                // 🔹 Normalize row length
                $row = array_slice($row, 0, count($header));
                $row = array_pad($row, count($header), null);

                $rowData = array_combine($header, $row);

                if (!$rowData) {
                    $skipped++;
                    continue;
                }

                unset($rowData['id']);

                // 🔹 Keep only DB columns
                $payload = array_intersect_key(
                    $rowData,
                    array_flip($tableColumns)
                );

                // 🔹 Convert empty strings to NULL
                foreach ($payload as $key => $value) {
                    if ($value === '') {
                        $payload[$key] = null;
                    }
                }

                // 🔹 Cast numeric columns safely
                foreach ($numericColumns as $col) {
                    if (array_key_exists($col, $payload)) {
                        $payload[$col] = is_numeric($payload[$col])
                            ? (int) $payload[$col]
                            : null;
                    }
                }

                // 🔹 Fix acquisition date (d-m-Y → Y-m-d)
                if (!empty($payload['acquisition'])) {
                    try {
                        $payload['acquisition'] = \Carbon\Carbon::createFromFormat(
                            'd-m-Y',
                            $payload['acquisition']
                        )->format('Y-m-d');
                    } catch (\Exception $e) {
                        $payload['acquisition'] = null;
                    }
                }

                // 🔹 Handle scientific notation / large numbers
                if (!empty($payload['serial_number'])) {
                    $payload['serial_number'] = (string) $payload['serial_number'];
                }

                // 🔹 Skip fully empty rows
                if (empty(array_filter($payload))) {
                    $skipped++;
                    continue;
                }

                // 🔹 System fields
                $payload['uuid']         = (string) \Illuminate\Support\Str::uuid();
                $payload['created_user'] = $userId;
                $payload['created_at']   = now();

                $batch[] = $payload;
                $inserted++;

                // 🔥 Batch insert (500 rows)
                if (count($batch) >= 500) {
                    AddChiller::insert($batch);
                    $batch = [];
                }
            }

            // Insert remaining rows
            if (!empty($batch)) {
                AddChiller::insert($batch);
            }

            fclose($handle);
            DB::commit();

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'CSV imported successfully',
                'summary' => [
                    'inserted' => $inserted,
                    'skipped'  => $skipped
                ]
            ]);
        } catch (\Throwable $e) {

            DB::rollBack();
            fclose($handle);

            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => $e->getMessage()
            ]);
        }
    }
}
