<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\RouteVisitRequest;
use App\Http\Requests\V1\MasterRequests\Web\RouteVisitSingleUpdatedRequest;
use App\Http\Requests\V1\MasterRequests\Web\RouteVisitUpdateRequest;
use App\Http\Resources\V1\Master\Web\RouteVisitResource;
use App\Services\V1\MasterServices\Web\RouteVisitService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Helpers\LogHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\RouteVisit;
use App\Models\RouteVisitHeader;
use App\Models\Route;
use App\Models\Warehouse;
use App\Models\Area;
use App\Models\Region;
use App\Models\Company;
use App\Models\AgentCustomer;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Exports\RouteVisitDummyExport;
use App\Exports\RouteVisitSingleExport;
use App\Exports\RouteVisitCollapseExport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelExcel;
use PhpOffice\PhpSpreadsheet\IOFactory;


/**
 * @OA\Tag(
 *     name="RouteVisit",
 *     description="API endpoints for managing route visits"
 * )
 *
 * @OA\Schema(
 *     schema="RouteVisit",
 *     type="object",
 *     required={"customer_type", "customers"},
 *     @OA\Property(
 *         property="customer_type",
 *         type="integer",
 *         description="1 for Agent Customer, 2 for Merchandisor",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="customers",
 *         type="array",
 *         description="List of customers to create route visits for",
 *         @OA\Items(
 *             type="object",
 *             required={"customer_id", "company_id", "from_date", "to_date"},
 *
 *             @OA\Property(property="customer_id", type="integer", example=101),
 *
 *             @OA\Property(
 *                 property="company_id",
 *                 type="string",
 *                 description="Comma-separated list of company IDs",
 *                 example="1,2"
 *             ),
 *             @OA\Property(
 *                 property="region",
 *                 type="string",
 *                 description="Comma-separated list of regions",
 *                 example="1,2"
 *             ),
 *             @OA\Property(
 *                 property="area",
 *                 type="string",
 *                 description="Comma-separated list of areas",
 *                 example="1,2"
 *             ),
 *             @OA\Property(
 *                 property="warehouse",
 *                 type="string",
 *                 description="Comma-separated list of warehouses",
 *                 example="1,2"
 *             ),
 *             @OA\Property(
 *                 property="route",
 *                 type="string",
 *                 description="Comma-separated list of routes",
 *                 example="1,2"
 *             ),
 *             @OA\Property(
 *                 property="days",
 *                 type="string",
 *                 description="Comma-separated list of days",
 *                 example="Monday,Wednesday,Friday"
 *             ),
 *             @OA\Property(
 *                 property="from_date",
 *                 type="string",
 *                 format="date",
 *                 example="2025-10-28"
 *             ),
 *             @OA\Property(
 *                 property="to_date",
 *                 type="string",
 *                 format="date",
 *                 example="2025-11-10"
 *             ),
 *             @OA\Property(
 *                 property="status",
 *                 type="integer",
 *                 description="0 for inactive, 1 for active",
 *                 example=1
 *             )
 *         )
 *     )
 * )
 */
class RouteVisitController extends Controller
{
    protected $service;

    public function __construct(RouteVisitService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/master/route-visits/list",
     *     tags={"RouteVisit"},
     *     summary="Get all route visits with pagination & filters",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="osa_code", in="query", description="Filter by OSA code", @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="List of route visits",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Route visits fetched successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/RouteVisit")),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="page", type="integer"),
     *                 @OA\Property(property="limit", type="integer"),
     *                 @OA\Property(property="totalPages", type="integer"),
     *                 @OA\Property(property="totalRecords", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $perPage = request()->get('limit', 50);
        $filters = request()->only(['customer', 'from_date', 'to_date', 'customer_id', 'customer_type', 'status',]);
        $data = $this->service->getAll($perPage, $filters);

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Route visits fetched successfully',
            'data' => RouteVisitResource::collection($data),
            'pagination' => [
                'page' => $data->currentPage(),
                'limit' => $data->perPage(),
                'totalPages' => $data->lastPage(),
                'totalRecords' => $data->total(),
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/master/route-visits/add",
     *     tags={"RouteVisit"},
     *     summary="Create a new route visit",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/RouteVisit")),
     *     @OA\Response(response=201, description="Route visit created successfully", @OA\JsonContent(ref="#/components/schemas/RouteVisit"))
     * )
     */
    public function store(RouteVisitRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->service->create($validated);

        $routeVisits = is_array($result) && isset($result['details'])
            ? $result['details']
            : $result;

        $isMultiple = is_iterable($routeVisits) && count($routeVisits) > 1;

        $logCurrentData = $isMultiple
            ? collect($routeVisits)->map(fn($r) => $r->toArray())->toArray()
            : (is_array($routeVisits)
                ? $routeVisits[0]->toArray()
                : $routeVisits->toArray());

        LogHelper::store(
            '7',
            '36',
            'add',
            null,
            $logCurrentData,
            auth()->id()
        );

        return response()->json([
            'status' => 'success',
            'code'   => 201,
            'message' => $isMultiple
                ? 'Multiple route visits created successfully'
                : 'Route visit created successfully',
            'data' => $isMultiple
                ? RouteVisitResource::collection($routeVisits)
                : new RouteVisitResource(
                    is_array($routeVisits) ? $routeVisits[0] : $routeVisits
                )
        ], 201);
    }

// public function store(RouteVisitRequest $request): JsonResponse
// {
//     $validated = $request->validated();
//     $result = $this->service->create($validated);
//     $isMultiple = is_iterable($result) && count($result) > 1;
//     $logCurrentData = $isMultiple
//         ? collect($result)->map(fn ($r) => $r->toArray())->toArray()
//         : (is_array($result) ? $result[0]->toArray() : $result->toArray());
//     LogHelper::store( 
//         'master',           
//         'route_visit',     
//         'add',           
//         null,             
//         $logCurrentData, 
//         auth()->id()        
//     );

//     return response()->json([
//         'status' => 'success',
//         'code' => 201,
//         'message' => $isMultiple
//             ? 'Multiple route visits created successfully'
//             : 'Route visit created successfully',
//         'data' => $isMultiple
//             ? RouteVisitResource::collection($result)
//             : new RouteVisitResource(is_array($result) ? $result[0] : $result)
//     ], 201);
// }


    /**
     * @OA\Get(
     *     path="/api/master/route-visits/{uuid}",
     *     tags={"RouteVisit"},
     *     summary="Get a single route visit by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Route visit details", @OA\JsonContent(ref="#/components/schemas/RouteVisit")),
     *     @OA\Response(response=404, description="Route visit not found")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        $result = $this->service->findByUuid($uuid);

        if (!$result) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Route visit not found',
                'data' => null
            ], 404);
        }

        $isMultiple = is_iterable($result) && count($result) > 1;

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => $isMultiple
                ? 'Multiple route visits fetched successfully'
                : 'Route visit fetched successfully',
            'data' => $isMultiple
                ? RouteVisitResource::collection($result)
                : new RouteVisitResource(is_array($result) ? $result[0] : $result)
        ], 200);
    }


    // /**
    //  * @OA\Put(
    //  *     path="/api/master/route-visits/update/{uuid}",
    //  *     tags={"RouteVisit"},
    //  *     summary="Update a route visit by UUID",
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
    //  *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/RouteVisit")),
    //  *     @OA\Response(response=200, description="Route visit updated successfully", @OA\JsonContent(ref="#/components/schemas/RouteVisit"))
    //  * )
    //  */
    public function update(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate([
            'customer_type' => 'nullable|integer|in:1,2',
            'merchandiser_id' => 'nullable|integer',

            'customers' => 'required|array',

            'customers.*.customer_id' => 'required|integer',

            'customers.*.company_id'  => 'nullable',
            'customers.*.region'      => 'nullable',
            'customers.*.area'        => 'nullable',
            'customers.*.warehouse'   => 'nullable',
            'customers.*.route'       => 'nullable',
            'customers.*.days'        => 'nullable|string',

            'customers.*.from_date'   => 'nullable|date',
            'customers.*.to_date'     => 'nullable|date',
            'customers.*.status'      => 'nullable|integer',

            'global_days' => 'nullable|string',
        ]);

        $result = $this->service->updateByUuid($uuid, $validated);

        $isMultiple = is_iterable($result) && count($result) > 1;

        return response()->json([
            'status' => 'success',
            'code'   => 200,
            'message' => $isMultiple
                ? 'Multiple route visits updated successfully'
                : 'Route visit updated successfully',
            'data' => $isMultiple
                ? RouteVisitResource::collection($result)
                : new RouteVisitResource(is_array($result) ? $result[0] : $result)
        ], 200);
    }


    /**
     * @OA\Put(
     *     path="/api/master/route-visits/bulk-update",
     *     tags={"RouteVisit"},
     *     summary="Bulk update multiple route visits",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"customer_type", "customers"},
     *             @OA\Property(
     *                 property="customer_type",
     *                 type="integer",
     *                 description="1 for Agent Customer, 2 for Merchandisor",
     *                 example=1
     *             ),
     *             @OA\Property(
     *                 property="customers",
     *                 type="array",
     *                 description="List of customers with route visit data to update",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"customer_id", "company_id", "from_date", "to_date"},
     *                     @OA\Property(property="customer_id", type="integer", example=79),
     *
     *                     @OA\Property(
     *                         property="company_id",
     *                         type="string",
     *                         description="Comma-separated list of company IDs",
     *                         example="157,158"
     *                     ),
     *                     @OA\Property(
     *                         property="region",
     *                         type="string",
     *                         description="Comma-separated list of region IDs",
     *                         example="103,104"
     *                     ),
     *                     @OA\Property(
     *                         property="area",
     *                         type="string",
     *                         description="Comma-separated list of area IDs",
     *                         example="44"
     *                     ),
     *                     @OA\Property(
     *                         property="warehouse",
     *                         type="string",
     *                         description="Comma-separated list of warehouse IDs",
     *                         example="119,120"
     *                     ),
     *                     @OA\Property(
     *                         property="route",
     *                         type="string",
     *                         description="Comma-separated list of route IDs",
     *                         example="63,64"
     *                     ),
     *                     @OA\Property(
     *                         property="days",
     *                         type="string",
     *                         description="Comma-separated list of days",
     *                         example="Monday,Wednesday,Friday"
     *                     ),
     *                     @OA\Property(
     *                         property="from_date",
     *                         type="string",
     *                         format="date",
     *                         example="2025-10-28"
     *                     ),
     *                     @OA\Property(
     *                         property="to_date",
     *                         type="string",
     *                         format="date",
     *                         example="2025-11-10"
     *                     ),
     *                     @OA\Property(
     *                         property="status",
     *                         type="integer",
     *                         description="0 for inactive, 1 for active",
     *                         example=1
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Route visits updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Multiple route visits updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/RouteVisit")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input data"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function bulkUpdate(RouteVisitUpdateRequest $request): JsonResponse
    {
        $result = $this->service->update(null, $request->validated());

        $isMultiple = is_iterable($result) && count($result) > 1;

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => $isMultiple
                ? 'Multiple route visits updated successfully'
                : 'Route visit updated successfully',
            'data' => RouteVisitResource::collection($result)
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/master/route-visits/salesmen",
     *     tags={"RouteVisit"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get salesman list (only sub_type = 1)",
     *     description="This API returns the list of salesmen where sub_type = 1. You can filter by status, search keyword, or apply pagination.",
     *     
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by salesman status (e.g. 1 for active, 0 for inactive)",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by salesman name",
     *         required=false,
     *         @OA\Schema(type="string", example="Rohit")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of records per page for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     * 
     *     @OA\Response(
     *         response=200,
     *         description="Salesman list fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Salesman list fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="osa_code", type="string", example="OSA001"),
     *                     @OA\Property(property="name", type="string", example="Rohit Sharma")
     *                 )
     *             )
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Something went wrong")
     *         )
     *     )
     * )
     */
    public function salesmanlist(Request $request)
    {
        try {
            $filters = $request->only(['status', 'search', 'per_page']);
            $data = $this->service->getAlll($filters);

            return response()->json([
                'status' => true,
                'message' => 'Merchandisher list fetched successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/master/route-visits/customerlist/{merchandiser_id}",
     *     tags={"RouteVisit"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get company customers by merchandiser ID (only id, code, name)",
     *     description="Returns all company customers for a specific merchandiser_id with only id, code, and name fields.",
     *
     *     @OA\Parameter(
     *         name="merchandiser_id",
     *         in="path",
     *         required=true,
     *         description="ID of the merchandiser",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by customer status (1=Active, 0=Inactive)",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Search by business name",
     *         @OA\Schema(type="string", example="Star Traders")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Customers fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customers fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="code", type="string", example="OSA001"),
     *                     @OA\Property(property="name", type="string", example="Star Traders")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No customers found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No customers found for this merchandiser ID"),
     *             @OA\Property(property="data", type="string", example=null)
     *         )
     *     )
     * )
     */
    public function getByMerchandiser($merchandiser_id, Request $request)
    {
        try {
            $filters = $request->only(['status', 'search']);

            $data = $this->service->getByMerchandiser($merchandiser_id, $filters);

            /**
             * 🔹 No data case (NOT an error, so 200 OK)
             */
            if ($data->isEmpty()) {
                return response()->json([
                    'status'  => true,
                    'message' => 'No customers available for the selected merchandiser.',
                    'data'    => [],
                ], 200);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Customers fetched successfully.',
                'data'    => $data,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Unable to fetch customers. Please try again later.',
                'data'    => null,
            ], 500);
        }
    }


    // public function export(Request $request)
    // {
    //     $format = $request->input('format', 'csv');

    //     $result = $this->service->export($format);

    //     if (!$result['status']) {
    //         return response()->json($result, 200);
    //     }

    //     return response()->json($result, 200);
    // }

    // public function globlesearch(Request $request)
    // {
    //     $keyword = $request->input('query');
    //     $perPage = $request->input('per_page', 50);

    //     $visits = $this->service->globalSearch($keyword, $perPage);

    //     return response()->json([
    //         'status'  => 'success',
    //         'code'    => 200,
    //         'message' => 'Route visits fetched successfully',
    //         'data'    => RouteVisitResource::collection($visits),
    //         'pagination' => [
    //             'current_page' => $visits->currentPage(),
    //             'last_page'    => $visits->lastPage(),
    //             'per_page'     => $visits->perPage(),
    //             'total'        => $visits->total(),
    //         ]
    //     ]);
    // }

    public function globlesearch(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 50);
        $keyword = $request->input('query');

        $data = $this->service->globalSearch($keyword, $perPage);

        return response()->json([
            'status' => 'success',
            'data' => $data->items(),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
                'last_page'    => $data->lastPage(),
            ]
        ]);
    }


    public function list(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 10);

        $data = $this->service->list($request->all(), $perPage);

        return response()->json([
            'status' => 'success',
            'data' => $data->items(),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
                'last_page'    => $data->lastPage(),
            ]
        ]);
    }

    // New data
    public function bulkImport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:csv,txt,xlsx'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {

            $file      = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());

            /* ================= READ FILE ================= */
            if ($extension === 'xlsx') {

                $spreadsheet = IOFactory::load($file->getRealPath());
                $sheetData   = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

                if (count($sheetData) < 2) {
                    throw new \Exception('Invalid XLSX file');
                }

                $csvHeader = array_map('trim', array_values(array_shift($sheetData)));
                $rows      = array_map(fn($row) => array_values($row), $sheetData);
            } else {

                $handle = fopen($file->getRealPath(), 'r');

                $csvHeader = fgetcsv($handle);
                if (!$csvHeader) {
                    throw new \Exception('Invalid CSV file');
                }

                $csvHeader = array_map(fn($h) => trim($h), $csvHeader);

                $rows = [];
                while (($row = fgetcsv($handle)) !== false) {
                    $rows[] = $row;
                }

                fclose($handle);
            }

            /* ================= CREATE HEADER ================= */
            $header = RouteVisitHeader::create([
                'uuid'         => Str::uuid(),
                'osa_code'     => $this->service->generateHeaderCode(),
                'created_user' => Auth::id(),
            ]);

            $detailInserted  = 0;
            $processedRoutes = []; // 🔒 track route deletion

            /* ================= PROCESS ROWS ================= */
            foreach ($rows as $row) {

                $data = array_combine($csvHeader, $row);

                /* ---------------- ROUTE ---------------- */
                if (empty($data['route'])) continue;

                $route = Route::where('route_code', trim($data['route']))->first();
                if (!$route) continue;

                /**
                 * 🔥 ONLY CHANGE:
                 * If route exists → delete ALL old route_visit + headers (once)
                 */
                if (!in_array($route->id, $processedRoutes)) {

                    // 1️⃣ Get related header IDs
                    $headerIds = RouteVisit::where('route', $route->id)
                        ->pluck('header_id')
                        ->filter()
                        ->unique()
                        ->toArray();

                    // 2️⃣ Delete route visits
                    RouteVisit::where('route', $route->id)->delete();

                    // 3️⃣ Delete headers
                    if (!empty($headerIds)) {
                        RouteVisitHeader::whereIn('id', $headerIds)->delete();
                    }

                    $processedRoutes[] = $route->id;
                }

                /* -------- ROUTE → WAREHOUSES -------- */
                $warehouseIdsArr = collect(explode(',', (string) $route->warehouse_id))
                    ->map(fn($id) => (int) trim($id))
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();

                if (!$warehouseIdsArr) continue;
                $warehouseIds = implode(',', $warehouseIdsArr);

                /* -------- WAREHOUSES → AREAS -------- */
                $areaIdsArr = Warehouse::whereIn('id', $warehouseIdsArr)
                    ->pluck('area_id')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();

                if (!$areaIdsArr) continue;
                $areaIds = implode(',', $areaIdsArr);

                /* -------- AREAS → REGIONS -------- */
                $regionIdsArr = Area::whereIn('id', $areaIdsArr)
                    ->pluck('region_id')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();

                if (!$regionIdsArr) continue;
                $regionIds = implode(',', $regionIdsArr);

                /* -------- REGIONS → COMPANIES -------- */
                $companyIdsArr = Region::whereIn('id', $regionIdsArr)
                    ->pluck('company_id')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();

                if (!$companyIdsArr) continue;
                $companyIds = implode(',', $companyIdsArr);

                /* ---------------- DAYS ---------------- */
                $dayColumns = [
                    'monday',
                    'tuesday',
                    'wednesday',
                    'thursday',
                    'friday',
                    'saturday',
                    'sunday'
                ];

                $days = collect($dayColumns)
                    ->filter(fn($d) => isset($data[$d]) && strtolower(trim($data[$d])) === 'yes')
                    ->map(fn($d) => ucfirst($d))
                    ->implode(',');

                if ($days === '') continue;

                /* ---------------- CUSTOMER TYPE ---------------- */
                $rawType = strtolower(trim($data['customer type'] ?? ''));

                $customerType   = null;
                $customerId     = null;
                $merchandiserId = null;

                if ($rawType === 'field customer') {

                    if (empty($data['customer'])) continue;

                    $customer = AgentCustomer::where('osa_code', trim($data['customer']))->first();
                    if (!$customer) continue;

                    $customerType = 1;
                    $customerId   = $customer->id;
                } elseif ($rawType === 'merchandiser') {

                    if (empty($data['customer'])) continue;

                    $merchandiser = AgentCustomer::where('code', trim($data['customer']))->first();
                    if (!$merchandiser) continue;

                    $customerType   = 2;
                    $merchandiserId = $merchandiser->id;
                } else {
                    continue;
                }

                /* ---------------- DATES ---------------- */
                $fromDate = $this->parseDate($data['start date']);
                $toDate   = $this->parseDate($data['end date']);

                /* ---------------- INSERT ---------------- */
                RouteVisit::create([
                    'uuid'            => Str::uuid(),
                    'osa_code'        => $this->service->generateDetailCode(),
                    'customer_type'   => $customerType,
                    'customer_id'     => $customerId,
                    'merchandiser_id' => $merchandiserId,
                    'route'           => $route->id,
                    'warehouse'       => $warehouseIds,
                    'area'            => $areaIds,
                    'region'          => $regionIds,
                    'company_id'      => $companyIds,
                    'days'            => $days,
                    'from_date'       => $fromDate,
                    'to_date'         => $toDate,
                    'status'          => 1,
                    'header_id'       => $header->id,
                    'created_user'    => Auth::id(),
                ]);

                $detailInserted++;
            }

            if ($detailInserted === 0) {
                throw new \Exception('No valid detail records found.');
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => "Import successful. Rows inserted: {$detailInserted}"
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    private function parseDate($value)
    {
        if (empty($value)) {
            return null;
        }

        // Excel numeric date
        if (is_numeric($value)) {
            return Carbon::instance(
                \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)
            )->format('Y-m-d');
        }

        $value = trim($value);

        $formats = [
            'd-m-Y',
            'd/m/Y',
            'Y-m-d',
            'Y/m/d',
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d');
            } catch (\Exception $e) {
                // try next format
            }
        }

        throw new \Exception("Invalid date format: {$value}");
    }

    public function downloadRouteVisitDummyCsv(Request $request)
    {
        // format support (default csv)
        $format = strtolower($request->input('format', 'csv'));
        $extension = $format === 'xlsx' ? 'xlsx' : 'csv';

        $filename = 'route_visit_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'routevisitexports/' . $filename;

        $export = new RouteVisitDummyExport();

        if ($format === 'xlsx') {
            Excel::store($export, $path, 'public', ExcelExcel::XLSX);
        } else {
            Excel::store($export, $path, 'public', ExcelExcel::CSV);
        }

        // generate public URL
        $appUrl = rtrim(config('app.url'), '/');
        $downloadUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json([
            'status'        => 'success',
            'download_url'  => $downloadUrl,
        ]);
    }

    public function exportAll(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';

        $uuid = $request->input('uuid'); // optional

        $filename = 'route_visit_' . ($uuid ?? 'all') . '.' . $extension;
        $path = 'routevisitexports/' . $filename;

        // Only Collapse Export
        $export = new RouteVisitCollapseExport($uuid);

        Excel::store(
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
            'file_url' => $fullUrl,
        ]);
    }
}
