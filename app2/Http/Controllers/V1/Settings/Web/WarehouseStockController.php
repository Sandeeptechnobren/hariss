<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\WarehouseStockRequest;
use App\Http\Resources\V1\Settings\Web\WarehouseStockResource;
use App\Services\V1\Settings\Web\WarehouseStockService;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="WarehouseStock",
 *     type="object",
 *     title="WarehouseStock",
 *     description="Warehouse Stock schema",
 *     required={"warehouse_id", "item_id", "qty", "status"},
 *     @OA\Property(property="warehouse_id", type="integer", example=1, description="ID of the warehouse"),
 *     @OA\Property(property="item_id", type="integer", example=10, description="ID of the item"),
 *     @OA\Property(property="status", type="integer", example=1, description="Status of the stock: 0=Inactive, 1=Active")
 * )
 */
class WarehouseStockController extends Controller
{
    protected $service;

    public function __construct(WarehouseStockService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/settings/warehouse-stocks/list",
     *     tags={"WarehouseStock"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get all warehouse stocks",
     *     @OA\Response(response=200, description="Stocks fetched successfully")
     * )
     */
    public function index(Request $request)
    {

        $perPage = $request->get('per_page', 10);
        $filters = $request->only(['osa_code', 'warehouse_id', 'item_id', 'status']);
        $stocks = $this->service->list($perPage, $filters);

        return ResponseHelper::paginatedResponse(
            'Records fetched successfully',
            WarehouseStockResource::class,
            $stocks
        );
    }

    /**
     * @OA\Post(
     *     path="/api/settings/warehouse-stocks/add",
     *     tags={"WarehouseStock"},
     *     security={{"bearerAuth":{}}},
     *     summary="Create a new warehouse stock",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/WarehouseStock")
     *     ),
     *     @OA\Response(response=201, description="Stock created successfully")
     * )
     */
    public function store(WarehouseStockRequest $request)
    {
        $stock = $this->service->create($request->validated());

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Warehouse stock created successfully',
            'data' => new WarehouseStockResource($stock),
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/settings/warehouse-stocks/{uuid}",
     *     tags={"WarehouseStock"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get a specific warehouse stock by UUID",
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Stock fetched successfully"),
     *     @OA\Response(response=404, description="Stock not found")
     * )
     */
    public function show(string $uuid)
    {
        try {
            $stock = $this->service->getByUuid($uuid);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Warehouse stock fetched successfully',
                'data' => new WarehouseStockResource($stock),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Warehouse stock not found.',
            ], 404);
        }
    }


    /**
     * @OA\Put(
     *     path="/api/settings/warehouse-stocks/{uuid}",
     *     tags={"WarehouseStock"},
     *     security={{"bearerAuth":{}}},
     *     summary="Update a warehouse stock",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/WarehouseStock")),
     *     @OA\Response(response=200, description="Stock updated successfully")
     * )
     */
    public function update(WarehouseStockRequest $request, string $uuid)
    {
        $result = $this->service->update($uuid, $request->validated());

        if (!$result['status']) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $result['message'],
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => $result['message'],
            'data' => new WarehouseStockResource($result['data']),
        ]);
    }


    /**
     * @OA\Delete(
     *     path="/api/settings/warehouse-stocks/{uuid}",
     *     tags={"WarehouseStock"},
     *     security={{"bearerAuth":{}}},
     *     summary="Soft delete a warehouse stock",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Stock soft deleted successfully")
     * )
     */
    public function destroy(string $uuid)
    {
        $result = $this->service->softDelete($uuid);

        return response()->json($result);
    }

}
