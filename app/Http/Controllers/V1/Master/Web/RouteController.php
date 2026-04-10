<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Exports\RouteExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\RouteRequest;
use App\Http\Resources\V1\Master\Web\RouteResource;
use App\Models\Route;
use App\Services\V1\MasterServices\Web\RouteService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;
use App\Helpers\DataAccessHelper;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Route",
 *     description="API endpoints for managing routes"
 * )
 */
class RouteController extends Controller
{
    protected $routeService;

    public function __construct(RouteService $routeService)
    {
        $this->routeService = $routeService;
    }
    /**
     * @OA\Get(
     *     path="/api/master/route/list_routes",
     *     tags={"Route"},
     *     summary="Get all routes with pagination and search",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="route_name",
     *         in="query",
     *         description="Search by route name (case-insensitive, partial match)",
     *         required=false,
     *         @OA\Schema(type="string", example="Main")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of routes with pagination & filters",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Routes fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="route_name", type="string", example="Central Route"),
     *                     @OA\Property(property="route_code", type="string", example="RT01"),
     *                     @OA\Property(property="warehouse_id", type="integer", example=2),
     *                     @OA\Property(
     *                         property="warehouse",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="warehouse_name", type="string", example="Main Warehouse")
     *                     )
     *                 )
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
        $perPage = $request->get('limit', 50);
        $dropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);
        $filters = $request->only(['route_name', 'route_code', 'status', 'warehouse_id']);
        $routes = $this->routeService->getAll($perPage, $filters, $dropdown);
        if ($dropdown) {
            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Routes fetched successfully (dropdown mode)',
                'data'    => $routes,
            ]);
        }
        return response()->json([
            'status'    => 'success',
            'code'      => 200,
            'message'   => 'Routes fetched successfully',
            'data'      => RouteResource::collection($routes),
            'pagination' => [
                'page'          => $routes->currentPage(),
                'limit'         => $routes->perPage(),
                'totalPages'    => $routes->lastPage(),
                'totalRecords'  => $routes->total(),
            ],
        ]);
    }


    /**
     * @OA\Post(
     *     path="/api/master/route/add_routes",
     *     tags={"Route"},
     *     summary="Create a new route",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Route")),
     *     @OA\Response(
     *         response=201,
     *         description="Route created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Route")
     *     )
     * )
     */
    public function store(RouteRequest $request): JsonResponse
    {
        $route = $this->routeService->create($request->validated());
        return response()->json([
            'status'  => true,
            'code'    => 201,
            'message' => 'Route created successfully',
            'data'    => $route,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/master/route/routes/{id}",
     *     tags={"Route"},
     *     summary="Get a single route by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Route ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Route details",
     *         @OA\JsonContent(ref="#/components/schemas/Route")
     *     ),
     *     @OA\Response(response=404, description="Route not found")
     * )
     */
public function show(string $uuid): JsonResponse
    {
        $route = $this->routeService->getByUuid($uuid);
        return response()->json([
            'status'  => true,
            'code'    => 200,
            'message' => 'Success',
            'data'    => new RouteResource($route),
        ], 200);
    }

    /**
     * @OA\Put(
     *     path="/api/master/route/routes/{id}",
     *     tags={"Route"},
     *     summary="Update an existing route",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Route ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Route")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Route updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Route")
     *     )
     * )
     */
 public function update(RouteRequest $request, string $uuid): JsonResponse
{
    $route = $this->routeService->getByUuid($uuid); 
    if (!$route) {
        return response()->json([
            'status' => false,
            'code' => 404,
            'message' => 'Route not found'
        ], 404);
    }
    $validated = $request->validated();
    $updated = $this->routeService->update($route, $validated);

    return response()->json([
        'status' => true,
        'code' => 200,
        'message' => 'Route updated successfully',
        'data' => new RouteResource($updated)
    ], 200);
}


    /**
     * @OA\Delete(
     *     path="/api/master/route/routes/{id}",
     *     tags={"Route"},
     *     summary="Delete a route by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Route ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Route deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Route deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Route not found")
     * )
     */
    public function destroy(Route $route): JsonResponse
    {
        $this->routeService->delete($route);
        return response()->json([
            'status'  => true,
            'code'    => 200,
            'message' => 'Route deleted successfully',
        ], 200);
    }
    /**
     * @OA\Get(
     *     path="/api/master/route/global_search",
     *     tags={"Route"},
     *     summary="Global search for routes",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search keyword",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of records per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Routes fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Routes fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="route_code", type="string"),
     *                     @OA\Property(property="route_name", type="string"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="status", type="integer"),
     *                     @OA\Property(
     *                         property="warehouse",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="warehouse_code", type="string"),
     *                         @OA\Property(property="warehouse_name", type="string"),
     *                         @OA\Property(property="owner_name", type="string")
     *                     ),
     *                     @OA\Property(
     *                         property="created_by",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="firstname", type="string"),
     *                         @OA\Property(property="lastname", type="string"),
     *                         @OA\Property(property="username", type="string")
     *                     ),
     *                     @OA\Property(
     *                         property="updated_by",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="firstname", type="string"),
     *                         @OA\Property(property="lastname", type="string"),
     *                         @OA\Property(property="username", type="string")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="page", type="integer", example=1),
     *                 @OA\Property(property="limit", type="integer", example=10),
     *                 @OA\Property(property="totalPages", type="integer", example=1),
     *                 @OA\Property(property="totalRecords", type="integer", example=5)
     *             )
     *         )
     *     )
     * )
     */

public function global_search(Request $request)
{
    try {
        $perPage = $request->get('per_page', 10);
        $searchTerm = $request->get('search');

        $routes = $this->routeService->globalSearch($perPage, $searchTerm);

        return response()->json([
            'status'      => 'success',
            'code'        => 200,
            'message'     => 'Routes fetched successfully',
            'data'        => RouteResource::collection($routes),
            'pagination'  => [
                'page'          => $routes->currentPage(),
                'limit'         => $routes->perPage(),
                'totalPages'    => $routes->lastPage(),
                'totalRecords'  => $routes->total(),
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'code'    => 500,
            'message' => $e->getMessage(),
            'data'    => null
        ], 500);
    }
}

//     public function global_search(Request $request)
//     {

//         try {
//             $perPage = $request->get('per_page', 10);
//             $searchTerm = $request->get('search');

//             $routes = $this->routeService->globalSearch($perPage, $searchTerm);
// // dd($routes);
//             return response()->json([
//                 "status" => "success",
//                 "code" => 200,
//                 "message" => "Routes fetched successfully",
//                 "data" => $routes->items(),
//                 "pagination" => [
//                     "page" => $routes->currentPage(),
//                     "limit" => $routes->perPage(),
//                     "totalPages" => $routes->lastPage(),
//                     "totalRecords" => $routes->total(),
//                 ]
//             ], 200);
//         } catch (\Exception $e) {
//             return response()->json([
//                 "status" => "error",
//                 "code" => 500,
//                 "message" => $e->getMessage(),
//                 "data" => null
//             ], 500);
//         }
//     }

    // /**
    //  * @OA\Get(
    //  *     path="/api/master/route/routes/export",
    //  *     summary="Export routes data",
    //  *     description="Export routes in CSV or XLSX format with optional date filters.",
    //  *     tags={"Route"},
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Parameter(
    //  *         name="start_date",
    //  *         in="query",
    //  *         description="Filter routes created from this date (YYYY-MM-DD)",
    //  *         required=false,
    //  *         @OA\Schema(type="string", format="date", example="2025-09-01")
    //  *     ),
    //  *     @OA\Parameter(
    //  *         name="end_date",
    //  *         in="query",
    //  *         description="Filter routes created until this date (YYYY-MM-DD)",
    //  *         required=false,
    //  *         @OA\Schema(type="string", format="date", example="2025-10-15")
    //  *     ),
    //  *     @OA\Parameter(
    //  *         name="format",
    //  *         in="query",
    //  *         description="File format for export",
    //  *         required=true,
    //  *         @OA\Schema(type="string", enum={"csv","xlsx"}, example="csv")
    //  *     ),
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="File downloaded successfully",
    //  *         @OA\MediaType(
    //  *             mediaType="application/octet-stream",
    //  *             @OA\Schema(type="string", format="binary")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=422,
    //  *         description="Validation error",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="message", type="string", example="The format field is required.")
    //  *         )
    //  *     )
    //  * )
    //  */
    // public function export(Request $request)
    // {
    //     $request->validate([
    //         'start_date' => 'nullable|date_format:Y-m-d',
    //         'end_date'   => 'nullable|date_format:Y-m-d',
    //         'format'     => 'required|in:csv,xlsx',
    //     ]);

    //     $startDate = $request->input('start_date');
    //     $endDate   = $request->input('end_date');
    //     $format    = $request->input('format');

    //     $data = $this->routeService->exportRoutes($startDate, $endDate);

    //     $export = new RouteExport($data);
    //     $fileName = 'routes_' . now()->format('Ymd_His') . '.' . $format;

    //     if (ob_get_length()) {
    //         ob_end_clean();
    //     }

    //     return Excel::download($export, $fileName, $format === 'csv' ? ExcelFormat::CSV : ExcelFormat::XLSX);
    // }

    /**
     * @OA\post(
     *     path="/api/master/route/export",
     *     summary="Get warehouses by area",
     *     tags={"Route"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Warehouse Export",
     *     )
     * )
     */
    // public function export()
    // {
    //     $filters = request()->input('filters', []);
    //     $format = strtolower(request()->input('format', 'csv'));
    //     $search = $request->input('search');
    //     $filename = 'routes_' . now()->format('Ymd_His');
    //     $filePath = "exports/{$filename}";

    //     $query = \DB::table('tbl_route')
    //         ->leftJoin('tbl_warehouse', 'tbl_warehouse.id', '=', 'tbl_route.warehouse_id')
    //         ->leftJoin('tbl_vehicle', 'tbl_vehicle.id', '=', 'tbl_route.vehicle_id')
    //         ->leftJoin('route_types', 'route_types.id', '=', 'tbl_route.route_type')
    //      ->select(
    //             'tbl_route.route_code',
    //             'tbl_route.route_name',
    //             'tbl_route.description',
    //             \DB::raw("COALESCE(tbl_warehouse.warehouse_name, '') AS warehouse"),
    //             \DB::raw("COALESCE(route_types.route_type_name, '') AS route_type"),
    //             \DB::raw("COALESCE(tbl_vehicle.vehicle_code, '') AS vehicle"),
    //             'tbl_route.status'
    //         );

    //     if (!empty($filters)) {
    //         if (!empty($filters['status'])) {
    //             $query->where('tbl_route.status', $filters['status']);
    //         }
    //         if (!empty($filters['search'])) {
    //             $query->where('tbl_route.route_name', 'like', '%' . $filters['search'] . '%');
    //         }
    //     }

    //     $data = $query->get();

    //     if ($data->isEmpty()) {
    //         return response()->json(['message' => 'No data available for export'], 404);
    //     }

    //     $export = new \App\Exports\RouteExport($data);
    //     $filePath .= $format === 'xlsx' ? '.xlsx' : '.csv';

    //     $success = \Maatwebsite\Excel\Facades\Excel::store(
    //         $export,
    //         $filePath,
    //         'public',
    //         $format === 'xlsx'
    //             ? \Maatwebsite\Excel\Excel::XLSX
    //             : \Maatwebsite\Excel\Excel::CSV
    //     );

    //     if (!$success) {
    //         throw new \Exception(strtoupper($format) . ' export failed.');
    //     }

    //     $appUrl = rtrim(config('app.url'), '/');
    //     $fullUrl = $appUrl . '/storage/app/public/' . $filePath;

    //     // $downloadUrl = \Storage::disk('public')->url($filePath);
    //     return response()->json(['url' => $fullUrl], 200);
    // }
public function export(\Illuminate\Http\Request $request)
{
    $filters = $request->input('filter', []);
    $format = strtolower($request->input('format', 'csv'));
    $search = strtolower($request->input('search', ''));
    $columns = $request->input('columns', []);
    $filename = 'routes_' . now()->format('Ymd_His');
    $filePath = "exports/{$filename}";
// dd($filters);
    $query = \App\Models\Route::with([
        'warehouse:id,warehouse_code,warehouse_name',
        'vehicle:id,vehicle_code',
        'getrouteType:id,route_type_name',
    ]);

    $query = DataAccessHelper::filterRoutes($query, Auth::user());

    if (!empty($search)) {
        $like = '%' . $search . '%';

        $query->where(function ($q) use ($like) {
            $q->whereRaw('LOWER(route_code) LIKE ?', [$like])
              ->orWhereRaw('LOWER(route_name) LIKE ?', [$like])
              ->orWhereRaw('LOWER(description) LIKE ?', [$like])

              ->orWhereHas('warehouse', function ($w) use ($like) {
                  $w->whereRaw('LOWER(warehouse_name) LIKE ?', [$like]);
              })

              ->orWhereHas('vehicle', function ($v) use ($like) {
                  $v->whereRaw('LOWER(vehicle_code) LIKE ?', [$like]);
              })

              ->orWhereHas('getrouteType', function ($r) use ($like) {
                  $r->whereRaw('LOWER(route_type_name) LIKE ?', [$like]);
              });
        });
    }

    if (!empty($filters['status'])) {
        $query->where('status', $filters['status']);
    }

    if (!empty($filters['warehouse_id'])) {
        $query->where('warehouse_id', $filters['warehouse_id']);
    }

    if (!empty($filters['route_type'])) {
        $query->where('route_type', $filters['route_type']);
    }

    if (!empty($filters['vehicle_id'])) {
        $query->where('vehicle_id', $filters['vehicle_id']);
    }

    $routes = $query->get();

    if ($routes->isEmpty()) {
        return response()->json(['message' => 'No data available for export'], 404);
    }

    $export = new \App\Exports\RouteExport($routes, $columns);

    $filePath .= $format === 'xlsx' ? '.xlsx' : '.csv';

    \Maatwebsite\Excel\Facades\Excel::store(
        $export,
        $filePath,
        'public',
        $format === 'xlsx'
            ? \Maatwebsite\Excel\Excel::XLSX
            : \Maatwebsite\Excel\Excel::CSV
    );

    return response()->json([
        'status' => 'success',
        'url' => rtrim(config('app.url'), '/') . '/storage/app/public/' . $filePath
    ], 200);
}

    /**
     * @OA\Post(
     *     path="/api/master/route/bulk-update-status",
     *     summary="Bulk update route statuses",
     *     tags={"Route"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ids", "status"},
     *             @OA\Property(
     *                 property="ids",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={12, 15, 23}
     *             ),
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 example="active"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Routes updated successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */

    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:tbl_route,id',
            'status' => 'required|in:active,inactive,0,1', // adjust based on your status values
        ]);

        $updatedCount = $this->routeService->bulkUpdateStatus($validated['ids'], $validated['status']);

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => "Updated {$updatedCount} routes successfully",
            'updated_count' => $updatedCount,
        ]);
    }
}
