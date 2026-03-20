<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\RouteTypeRequest;
use App\Services\V1\Settings\Web\RouteTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Exception;

/** 
 * @OA\Tag(
 *     name="RouteType",
 *     description="API endpoints for managing Route Types"
 * )
 */
class RouteTypeController extends Controller
{
    protected $routeTypeService;

    public function __construct(RouteTypeService $routeTypeService)
    {
        $this->routeTypeService = $routeTypeService;
    }

    /**
     * @OA\Get(
     *     path="/api/settings/route-type/list",
     *     tags={"RouteType"},
     *     summary="Get all Route Types",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of Route Types",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Route Types fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="route_type_code", type="string", example="RTC001"),
     *                     @OA\Property(property="route_type_name", type="string", example="Urban"),
     *                     @OA\Property(property="status", type="integer", example=1)
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
    public function index(): JsonResponse
{
    $allData  = request()->boolean('allData', false);
    $perPage  = $allData ? null : request()->get('limit', 50);
    $dropdown = request()->boolean('dropdown', false);

    // 🔹 Filters
    $filters = request()->only([
        'route_type_name',
        'route_type_code',
        'status'
    ]);

    $routeTypes = $this->routeTypeService->getAll(
        $perPage,
        $filters,
        $dropdown
    );

    // 🔹 DROPDOWN RESPONSE (unchanged)
    if ($dropdown) {
        return response()->json([
            'status'  => true,
            'code'    => 200,
            'message' => 'Route Types fetched successfully',
            'data'    => $routeTypes
        ]);
    }

    // 🔹 allData = true → NO pagination
    if ($allData) {
        return response()->json([
            'status'  => true,
            'code'    => 200,
            'message' => 'Route Types fetched successfully',
            'data'    => $routeTypes
        ]);
    }

    // 🔹 NORMAL PAGINATED RESPONSE (unchanged)
    return response()->json([
        'status'  => true,
        'code'    => 200,
        'message' => 'Route Types fetched successfully',
        'data'    => $routeTypes->items(),
        'pagination' => [
            'page'         => $routeTypes->currentPage(),
            'limit'        => $routeTypes->perPage(),
            'totalPages'   => $routeTypes->lastPage(),
            'totalRecords' => $routeTypes->total(),
        ]
    ]);
}

    // public function index(): JsonResponse
    // {
    //     $routeTypes = $this->routeTypeService->getAll();
    //     return response()->json([
    //         'status'  => true,
    //         'code'    => 200,
    //         'message' => 'Route Types fetched successfully',
    //         'data' => $routeTypes->items(),
    //         'pagination' => [
    //             'page' => $routeTypes->currentPage(),
    //             'limit' => $routeTypes->perPage(),
    //             'totalPages' => $routeTypes->lastPage(),
    //             'totalRecords' => $routeTypes->total(),
    //         ]
    //     ]);
    // }

    /**
     * @OA\Post(
     *     path="/api/settings/route-type/add",
     *     tags={"RouteType"},
     *     summary="Create a new Route Type",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"name"},
     *             @OA\Property(property="route_type_name", type="string", example="Urban"),
     *             @OA\Property(property="status", type="integer", enum={0,1}, example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Route Type created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="Route Type created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function store(RouteTypeRequest $request)
    {
        try {
            $routeType = $this->routeTypeService->create($request->validated(), Auth::id());
            return response()->json([
                'status'  => true,
                'code'    => 201,
                'message' => 'Route Type created successfully',
                'data'    => $routeType
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'code'    => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/settings/route-type/{id}",
     *     tags={"RouteType"},
     *     summary="Get a Route Type by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Route Type details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Route Type fetched successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Route Type not found")
     * )
     */
    public function show($id): JsonResponse
    {
        try {
            $routeType = $this->routeTypeService->getById($id);
            return response()->json([
                'status'  => true,
                'code'    => 200,
                'message' => 'Route Type fetched successfully',
                'data'    => $routeType
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'code'    => 404,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/settings/route-type/{id}/update",
     *     tags={"RouteType"},
     *     summary="Update a Route Type",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"name"},
     *             @OA\Property(property="route_type_name", type="string", example="Urban Updated"),
     *             @OA\Property(property="status", type="integer", enum={0,1}, example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Route Type updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Route Type updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function update(RouteTypeRequest $request, $id): JsonResponse
    {
        try {
            $routeType = $this->routeTypeService->update($id, $request->validated(), Auth::id());
            return response()->json([
                'status'  => true,
                'code'    => 200,
                'message' => 'Route Type updated successfully',
                'data'    => $routeType
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'code'    => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/settings/route-type/{id}/delete",
     *     tags={"RouteType"},
     *     summary="Delete a Route Type",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Route Type deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Route Type deleted successfully"),
     *             @OA\Property(property="data", type="object", nullable=true, example=null)
     *         )
     *     )
     * )
     */
    public function destroy($id): JsonResponse
    {
        try {
            $this->routeTypeService->delete($id);
            return response()->json([
                'status'  => true,
                'code'    => 200,
                'message' => 'Route Type deleted successfully',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'code'    => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
