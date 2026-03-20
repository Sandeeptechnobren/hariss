<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\SpareCategoryRequest;
use App\Http\Requests\V1\Settings\Web\UpdateSpareCRequest;
use App\Http\Resources\V1\Settings\Web\SpareCategoryResource;
use App\Services\V1\Settings\Web\SpareCategoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SpareCategoryController extends Controller
{
    protected SpareCategoryService $service;

    public function __construct(SpareCategoryService $service)
    {
        $this->service = $service;
    }

 public function store(SpareCategoryRequest $request): JsonResponse
    {
        try {
            $spareCategory = $this->service->createSpareCategory(
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Spare category created successfully.',
                'data'    => new SpareCategoryResource($spareCategory),
            ], 201);

        } catch (Throwable $e) {

            Log::error('SpareCategory creation failed', [
                'error' => $e->getMessage(),
                'data'  => $request->validated(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create spare category.',
            ], 500);
        }
    }

public function index(Request $request)
 {
    $perPage = $request->get('per_page', 50);
    $filters = $request->only(['osa_code', 'spare_category_name', 'status']);

    $data = $this->service->listsparecategory([
        'osa_code' => $filters['osa_code'] ?? null,
        'spare_category_name' => $filters['spare_category_name'] ?? null,
        'status' => $filters['status'] ?? null,
    ], $perPage);

    return response()->json([
        'status'     => 'success',
        'code'       => 200,
        'message'    => 'SpareCategory fetched successfully',
        'data'       => SpareCategoryResource::collection($data),
        'pagination' => [
            'currentPage'    => $data->currentPage(),
            'perPage'        => $data->perPage(),
            'lastPage'       => $data->lastPage(),
            'total'          => $data->total(),
        ]
    ]);
}


public function show(string $uuid)
{
    $bank = $this->service->getByUuid($uuid);

    if (!$bank) {
        return response()->json([
            'status'  => 'error',
            'code'    => 404,
            'message' => 'SpareCategory not found',
            'data'    => null
        ], 404);
    }

    return response()->json([
        'status'  => 'success',
        'code'    => 200,
        'message' => 'SpareCategory fetched successfully',
        'data'    => new SpareCategoryResource($bank)
    ]);
}

public function update(UpdateSpareCRequest $request, string $uuid): JsonResponse
{
    try {
        $updated = $this->service->updateBonus($uuid, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'SpareCategory updated successfully.',
            'data' => new SpareCategoryResource($updated),
        ], 200);

    } catch (Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'Failed to update sparecategory: ' . $e->getMessage(),
        ], 500);
    }
}
   public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->service->deleteByUuid($uuid);

            return response()->json([
                'success' => true,
                'message' => 'Spare category deleted successfully.',
            ], 200);

        } catch (ModelNotFoundException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Spare category not found.',
            ], 404);

        } catch (Throwable $e) {

            Log::error('SpareCategory deletion failed', [
                'uuid'  => $uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete spare category.',
            ], 500);
        }
    }
}