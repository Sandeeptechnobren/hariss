<?php

namespace App\Http\Controllers\V1\Agent_Transaction;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Agent_Transaction\RouteExpenceResource;
use App\Services\V1\Agent_Transaction\RouteExpenceService;
use Illuminate\Http\Request;
use Exception;

/**
 * @OA\Tag(
 *     name="Route Expences",
 *     description="API endpoints for managing Route Expences"
 * )
 */
class RouteExpenceController extends Controller
{
    protected $service;

    public function __construct(RouteExpenceService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/agent_transaction/route-expence/list",
     *     summary="Get all Route Expences with filters and pagination",
     *     tags={"Route Expences"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="page", in="query", description="Page number", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", description="Filter by status (1 or 0)", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="route_name", in="query", description="Search by route name", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="List of Route Expences fetched successfully")
     * )
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only([
                'osa_code',
                'status',
                'route_id',
                'warehouse_id',
                'salesman_id',
                'expence_type',
                'route_name',
                'warehouse_name',
                'description',
            ]);

            $perPage = $request->get('per_page', 50);
            $data = $this->service->getAll($perPage, $filters);

            return ResponseHelper::paginatedResponse(
                'Route expenses fetched successfully',
                RouteExpenceResource::class,
                $data
            );
            // return response()->json([
            //     'status'  => true,
            //     'code'    => 200,
            //     'message' => 'Route expenses fetched successfully',
            //     'data'    => $data->items(),
            //     'pagination' => [
            //         'page'         => $data->currentPage(),
            //         'limit'        => $data->perPage(),
            //         'totalPages'   => $data->lastPage(),
            //         'totalRecords' => $data->total(),
            //     ],
            // ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'code'    => 500,
                'message' => 'Failed to fetch route expenses: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/agent_transaction/route-expence/add",
     *     summary="Create a new Route Expence",
     *     tags={"Route Expences"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="salesman_id", type="integer", example=12, description="Must exist in salesman table"),
     *             @OA\Property(property="warehouse_id", type="integer", example=5, description="Must exist in tbl_warehouse table"),
     *             @OA\Property(property="route_id", type="integer", example=8, description="Must exist in tbl_route table"),
     *             @OA\Property(property="expence_type", type="integer", example=3, description="Must exist in tbl_expence_type table"),
     *             @OA\Property(property="description", type="string", example="Fuel and toll charges"),
     *             @OA\Property(property="image", type="string", example="receipt_123.png"),
     *             @OA\Property(property="date", type="string", format="date", example="2025-10-30"),
     *             @OA\Property(property="amount", type="number", format="float", example=1500.50),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Route expence created successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation failed"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $routeExpence = $this->service->create($request->all());
            return response()->json([
                'status'  => true,
                'code'    => 201,
                'message' => 'Route expense created successfully',
                'data'    => $routeExpence,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'code'    => 500,
                'message' => 'Failed to create route expense: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/agent_transaction/route-expence/{uuid}",
     *     summary="Get details of a specific Route Expence",
     *     tags={"Route Expences"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, description="UUID of the route expence", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Route expence details fetched successfully"),
     *     @OA\Response(response=404, description="Route expence not found")
     * )
     */
    public function show(string $uuid)
    {
        try {
            $routeExpence = $this->service->findByUuid($uuid);
            return response()->json([
                'status'  => true,
                'code'    => 200,
                'message' => 'Route expense details fetched successfully',
                'data' => new RouteExpenceResource($routeExpence)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'code'    => 404,
                'message' => 'Route expense not found: ' . $e->getMessage(),
            ], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/agent_transaction/route-expence/update/{uuid}",
     *     summary="Update an existing Route Expence",
     *     tags={"Route Expences"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="UUID of the route expense to update",
     *         @OA\Schema(type="string", example="b25a6b5c-6a8d-4d7f-9d40-12a3c95e6a98")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="salesman_id", type="integer", example=12, description="Must exist in salesman table"),
     *             @OA\Property(property="warehouse_id", type="integer", example=5, description="Must exist in tbl_warehouse table"),
     *             @OA\Property(property="route_id", type="integer", example=8, description="Must exist in tbl_route table"),
     *             @OA\Property(property="expence_type", type="integer", example=3, description="Must exist in tbl_expence_type table"),
     *             @OA\Property(property="description", type="string", example="Updated fuel expense details"),
     *             @OA\Property(property="image", type="string", example="updated_receipt_456.png"),
     *             @OA\Property(property="date", type="string", format="date", example="2025-11-01"),
     *             @OA\Property(property="amount", type="number", format="float", example=1800.75),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Route expense updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Route expense not found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
    public function update(Request $request, string $uuid)
    {
        try {
            $updated = $this->service->updateByUuid($uuid, $request->all());
            return response()->json([
                'status'  => true,
                'code'    => 200,
                'message' => 'Route expense updated successfully',
                'data' => new RouteExpenceResource($updated)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'code'    => 500,
                'message' => 'Failed to update route expense: ' . $e->getMessage(),
            ], 500);
        }
    }


    // /**
    //  * @OA\Delete(
    //  *     path="/api/agent_transaction/route-expence/delete/{uuid}",
    //  *     summary="Delete a Route Expence",
    //  *     tags={"Route Expences"},
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Parameter(name="uuid", in="path", required=true, description="UUID of the route expence", @OA\Schema(type="string")),
    //  *     @OA\Response(response=200, description="Route expence deleted successfully"),
    //  *     @OA\Response(response=404, description="Route expence not found")
    //  * )
    //  */
    // public function destroy(string $uuid)
    // {
    //     try {
    //         $this->service->deleteByUuid($uuid);
    //         return response()->json([
    //             'status'  => true,
    //             'code'    => 200,
    //             'message' => 'Route expense deleted successfully',
    //         ]);
    //     } catch (Exception $e) {
    //         return response()->json([
    //             'status'  => false,
    //             'code'    => 500,
    //             'message' => 'Failed to delete route expense: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }
}
