<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\VehicleRequest;
use App\Models\Vehicle;
use App\Services\V1\MasterServices\Web\VehicleService;
use Illuminate\Http\JsonResponse;

use Illuminate\Http\Request;

class VehicleController extends Controller
{
    protected $service;

    public function __construct(VehicleService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/master/vehicle/list",
     *     tags={"Vehicle"},
     *     summary="Get all vehicles with filters & pagination",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="vehicle_code",
     *         in="query",
     *         description="Filter by vehicle code",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="number_plat",
     *         in="query",
     *         description="Filter by number plate",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status (1=active, 0=inactive)",
     *         @OA\Schema(type="integer", enum={0,1})
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of vehicles",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Vehicles fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="vehicle_code", type="string", example="VH01"),
     *                     @OA\Property(property="number_plat", type="string", example="MH12AB1234"),
     *                     @OA\Property(property="vehicle_chesis_no", type="string", example="CH12345678"),
     *                     @OA\Property(property="description", type="string", example="Truck for deliveries"),
     *                     @OA\Property(property="capacity", type="string", example="500kg"),
     *                     @OA\Property(property="vehicle_type", type="string", example="1"),
     *                     @OA\Property(property="owner_type", type="string", example="0"),
     *                     @OA\Property(property="warehouse_id", type="integer", example=2),
     *                     @OA\Property(
     *                         property="warehouse",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="warehouse_name", type="string", example="Main Warehouse")
     *                     ),
     *                     @OA\Property(property="valid_from", type="string", format="date", example="2025-09-01"),
     *                     @OA\Property(property="valid_to", type="string", format="date", example="2026-09-01"),
     *                     @OA\Property(property="opening_odometer", type="string", example="1000"),
     *                     @OA\Property(property="status", type="integer", example=1),
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
        $perPage = $request->get('limit', 10);
        $filters = $request->only(['vehicle_code', 'number_plat', 'status', 'warehouse_id']);
        $vehicles = $this->service->getAll($perPage, $filters);
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Vehicles fetched successfully',
            'data' => $vehicles->items(),
            'pagination' => [
                'page' => $vehicles->currentPage(),
                'limit' => $vehicles->perPage(),
                'totalPages' => $vehicles->lastPage(),
                'totalRecords' => $vehicles->total(),
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/master/vehicle/create",
     *     tags={"Vehicle"},
     *     summary="Create a new vehicle",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="number_plat", type="string", example="MH12AB1234"),
     *             @OA\Property(property="vehicle_chesis_no", type="string", example="CH12345678"),
     *             @OA\Property(property="description", type="string", example="Truck for deliveries"),
     *             @OA\Property(property="capacity", type="string", example="500kg"),
     *             @OA\Property(property="vehicle_type", type="string", example="1"),
     *             @OA\Property(property="owner_type", type="string", example="0"),
     *             @OA\Property(property="warehouse_id", type="integer", example=2),
     *             @OA\Property(property="valid_from", type="string", format="date", example="2025-09-01"),
     *             @OA\Property(property="valid_to", type="string", format="date", example="2026-09-01"),
     *             @OA\Property(property="opening_odometer", type="string", example="1000"),
     *             @OA\Property(property="status", type="integer", example=1),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Vehicle created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="Vehicle created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
public function store(Request $request): JsonResponse
{
    try {
        $vehicle = $this->service->create($request->all());

        return response()->json([
            'status' => 'success',
            'code' => 201,
            'message' => 'Vehicle created successfully',
            'data' => $vehicle
        ], 201);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'code' => 500,
            'message' => 'Vehicle creation failed',
            'data' => null,
            'errors' => ['exception' => $e->getMessage()]
        ], 500);
    }
}


    /**
     * @OA\Get(
     *     path="/api/master/vehicle/{id}",
     *     tags={"Vehicle"},
     *     summary="Get a single vehicle by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vehicle details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Vehicle not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $vehicle = $this->service->findById($id);
        if (!$vehicle) {
            return response()->json(['status' => 'error', 'code' => 404, 'message' => 'Vehicle not found'], 404);
        }
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Success',
            'data' => $vehicle
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/master/vehicle/{id}/update",
     *     tags={"Vehicle"},
     *     summary="Update an existing vehicle",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="vehicle_code", type="string", example="VH01"),
     *             @OA\Property(property="number_plat", type="string", example="MH12AB1234"),
     *             @OA\Property(property="vehicle_chesis_no", type="string", example="CH12345678"),
     *             @OA\Property(property="description", type="string", example="Truck for deliveries"),
     *             @OA\Property(property="capacity", type="string", example="500kg"),
     *             @OA\Property(property="vehicle_type", type="string", example="1"),
     *             @OA\Property(property="owner_type", type="string", example="0"),
     *             @OA\Property(property="warehouse_id", type="integer", example=2),
     *             @OA\Property(property="valid_from", type="string", format="date", example="2025-09-01"),
     *             @OA\Property(property="valid_to", type="string", format="date", example="2026-09-01"),
     *             @OA\Property(property="opening_odometer", type="string", example="1000"),
     *             @OA\Property(property="status", type="integer", example=1),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vehicle updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Vehicle updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Vehicle not found")
     * )
     */
    public function update(VehicleRequest $request, int $id): JsonResponse
    {
        $vehicle = $this->service->findById($id);
        if (!$vehicle) {
            return response()->json(['status' => 'error', 'code' => 404, 'message' => 'Vehicle not found'], 404);
        }
        $vehicle = $this->service->update($vehicle, $request->validated());
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Vehicle updated successfully',
            'data' => $vehicle
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/master/vehicle/{id}/delete",
     *     tags={"Vehicle"},
     *     summary="Delete a vehicle by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vehicle deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Vehicle deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Vehicle not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $vehicle = $this->service->findById($id);
        if (!$vehicle) {
            return response()->json(['status' => 'error', 'code' => 404, 'message' => 'Vehicle not found'], 404);
        }
        $this->service->delete($vehicle);
        return response()->json(['status' => 'success', 'code' => 200, 'message' => 'Vehicle deleted successfully']);
    }
    /**
 * @OA\Get(
 *     path="/api/master/vehicle/global_search",
 *     tags={"Vehicle"},
 *     summary="Global search for vehicles",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         required=false,
 *         description="Search term to filter vehicles by name, code, number plate, brand, type, etc.",
 *         @OA\Schema(type="string", example="truck")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Vehicles fetched successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Vehicles fetched successfully"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="current_page", type="integer", example=1),
 *                 @OA\Property(property="data", type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="vehicle_code", type="string", example="VH001"),
 *                         @OA\Property(property="number_plat", type="string", example="KA01AB1234"),
 *                         @OA\Property(property="vehicle_type", type="string", example="Truck"),
 *                         @OA\Property(property="vehicle_brand", type="string", example="Tata"),
 *                         @OA\Property(property="capacity", type="string", example="10 Tons"),
 *                         @OA\Property(property="status", type="integer", example=1),
 *                         @OA\Property(property="warehouse", type="object",
 *                             @OA\Property(property="id", type="integer", example=2),
 *                             @OA\Property(property="warehouse_code", type="string", example="WH001"),
 *                             @OA\Property(property="warehouse_name", type="string", example="Main Warehouse"),
 *                             @OA\Property(property="owner_name", type="string", example="John Doe")
 *                         ),
 *                         @OA\Property(property="createdBy", type="object",
 *                             @OA\Property(property="id", type="integer", example=1),
 *                             @OA\Property(property="firstname", type="string", example="Admin"),
 *                             @OA\Property(property="lastname", type="string", example="User"),
 *                             @OA\Property(property="username", type="string", example="adminuser")
 *                         ),
 *                         @OA\Property(property="updatedBy", type="object",
 *                             @OA\Property(property="id", type="integer", example=2),
 *                             @OA\Property(property="firstname", type="string", example="Editor"),
 *                             @OA\Property(property="lastname", type="string", example="User"),
 *                             @OA\Property(property="username", type="string", example="edituser")
 *                         )
 *                     )
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=500, description="Failed to fetch vehicles")
 * )
 */

public function global_search(Request $request)
{
    try {
        $perPage = $request->get('per_page', 10);
        $searchTerm = $request->get('search');

        $vehicles = $this->service->globalSearch($perPage, $searchTerm);

        return response()->json([
            "status" => "success",
            "code" => 200,
            "message" => "Vehicles fetched successfully",
            "data" => $vehicles->items(),
            "pagination" => [
                "page" => $vehicles->currentPage(),
                "limit" => $vehicles->perPage(),
                "totalPages" => $vehicles->lastPage(),
                "totalRecords" => $vehicles->total(),
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
/**
 * @OA\Post(
 *     path="/api/master/vehicle/export",
 *     summary="Export vehicle list (csv, xlsx, pdf)",
 *     tags={"Vehicle"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="filters", type="object", example={"status":1}),
 *             @OA\Property(property="format", type="string", example="csv")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Export URL",
 *         @OA\JsonContent(
 *             @OA\Property(property="url", type="string", example="/storage/exports/vehicles_20251015_123456.csv")
 *         )
 *     )
 * )
 */ 

public function exportVehicles(Request $request)
{
    $filters = $request->input('filters', []);
    $format = strtolower($request->input('format', 'csv'));
    $downloadUrl = $this->service->export($filters, $format);

    return response()->json(['url' => $downloadUrl], 200);
}

/**
 * @OA\Post(
 *     path="/api/master/vehicle/multiple_status_update",
 *     summary="Update status of multiple vehicles",
 *     tags={"Vehicle"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"vehicle_ids","status"},
 *             @OA\Property(
 *                 property="vehicle_ids",
 *                 type="array",
 *                 @OA\Items(type="integer"),
 *                 description="Array of vehicle IDs to update",
 *                 example={42,44,50}
 *             ),
 *             @OA\Property(
 *                 property="status",
 *                 type="integer",
 *                 description="New status value to set",
 *                 example=1
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Status updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Vehicle statuses updated.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Update failed"
 *     )
 * )
 */


public function updateMultipleStatus(Request $request)
{
    $request->validate([
        'vehicle_ids' => 'required|array|min:1',
        'vehicle_ids.*' => 'integer|exists:tbl_vehicle,id',
        'status' => 'required|integer',
    ]);

    $vehicleIds = $request->input('vehicle_ids');
    $status = $request->input('status');

    $result = $this->service->updateVehiclesStatus($vehicleIds, $status);

    if ($result) {
        return response()->json([
            'success' => true,
            'message' => 'Vehicle statuses updated.'
        ], 200);
    } else {
        return response()->json([
            'success' => false,
            'message' => 'Update failed.'
        ], 500);
    }
}



}
