<?php

namespace App\Http\Controllers\V1\Loyality_Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Loyality_Management\AdjustmentRequest;
use App\Http\Requests\V1\Loyality_Management\UpdateAdjustmentRequest;
use App\Http\Resources\V1\Loyality_Management\AdjustmentResource;
use App\Services\V1\Loyality_Management\AdjustmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdjustmentController extends Controller
{
    protected AdjustmentService $service;

    public function __construct(AdjustmentService $service)
    {
        $this->service = $service;
    }
     public function store(AdjustmentRequest $request): JsonResponse
    {
        try {
            $reward = $this->service->createBonus($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Adjustment created successfully.',
                'data' => new AdjustmentResource($reward),
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create adjustment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
{
    try {
        $filters = $request->only([
            'osa_code',
            'warehouse_id',
            'route_id',
            'customer_id',
            'from_date',
            'to_date',
            'min_adjustment',
            'max_adjustment',
            'search',
            'per_page'
        ]);

        $perPage = $request->get('per_page', 50);

        $data = $this->service->getAllAdjustment($filters, $perPage);

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Adjustment records fetched successfully',
            'data'    => AdjustmentResource::collection($data),
            'meta'    => [
                'current_page' => $data->currentPage(),
                'total_pages'  => $data->lastPage(),
                'total'        => $data->total(),
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'code'    => 500,
            'message' => 'Failed to fetch records',
            'error'   => $e->getMessage(),
        ], 500);
    }
}
public function show(string $uuid, Request $request): JsonResponse
{
    try {
        $filters = $request->only([
            'warehouse_id',
            'route_id',
            'customer_id',
            'from_date',
            'to_date',
            'min_adjustment',
            'max_adjustment',
            'search',
            'osa_code'
        ]);

        $data = $this->service->getByUuid($uuid, $filters);

        if (!$data) {
            return response()->json([
                'status' => 'error',
                'code'   => 404,
                'message' => 'Record not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'code'   => 200,
            'message' => 'Record fetched successfully',
            'data'    => new AdjustmentResource($data)
        ], 200);

    } catch (\Exception $e) {

        return response()->json([
            'status'  => 'error',
            'code'    => 500,
            'message' => 'Failed to retrieve record',
            'error'   => $e->getMessage()
        ], 500);
    }
}
public function update(UpdateAdjustmentRequest $request, string $uuid): JsonResponse
{
    try {
        $updated = $this->service->updateAdjustment($uuid, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Adjustment updated successfully.',
            'data' => new AdjustmentResource($updated),
        ], 200);

    } catch (Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'Failed to update adjustment: ' . $e->getMessage(),
        ], 500);
    }
}

}