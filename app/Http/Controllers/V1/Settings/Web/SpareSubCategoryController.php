<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\SpareSubCategoryRequest;
use App\Http\Requests\V1\Settings\Web\UpdateSpareSubCRequest;
use App\Http\Resources\V1\Settings\Web\SpareSubCategoryResource;
use App\Services\V1\Settings\Web\SpareSubCategoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SpareSubCategoryController extends Controller
{
    protected SpareSubCategoryService $service;

    public function __construct(SpareSubCategoryService $service)
    {
        $this->service = $service;
    }

 public function store(SpareSubCategoryRequest $request): JsonResponse
    {
        try {
            $spareCategory = $this->service->createSpareSubCategory(
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Spare subcategory created successfully.',
                'data'    => new SpareSubCategoryResource($spareCategory),
            ], 201);

        } catch (Throwable $e) {

            Log::error('SpareSubCategory creation failed', [
                'error' => $e->getMessage(),
                'data'  => $request->validated(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create spare subcategory.',
            ], 500);
        }
    }

public function index(Request $request)
 {
    $perPage = $request->get('per_page', 50);
    $filters = $request->only(['osa_code', 'spare_subcategory_name','category','status']);

    $data = $this->service->listsparesubcategory([
        'osa_code' => $filters['osa_code'] ?? null,
        'spare_subcategory_name' => $filters['spare_subcategory_name'] ?? null,
        'spare_category_id' => $filters['category'] ?? null,
        'status' => $filters['status'] ?? null,
    ], $perPage);

    return response()->json([
        'status'     => 'success',
        'code'       => 200,
        'message'    => 'SpareSubCategory fetched successfully',
        'data'       => SpareSubCategoryResource::collection($data),
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
            'message' => 'SpareSubCategory not found',
            'data'    => null
        ], 404);
    }

    return response()->json([
        'status'  => 'success',
        'code'    => 200,
        'message' => 'SpareSubCategory fetched successfully',
        'data'    => new SpareSubCategoryResource($bank)
    ]);
}

public function update(UpdateSpareSubCRequest $request, string $uuid): JsonResponse
{
    try {
        $updated = $this->service->updateBonus($uuid, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'SpareSubCategory updated successfully.',
            'data' => new SpareSubCategoryResource($updated),
        ], 200);

    } catch (Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'Failed to update sparesubcategory: ' . $e->getMessage(),
        ], 500);
    }
}
   public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->service->deleteByUuid($uuid);

            return response()->json([
                'success' => true,
                'message' => 'Spare subcategory deleted successfully.',
            ], 200);

        } catch (ModelNotFoundException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Spare subcategory not found.',
            ], 404);

        } catch (Throwable $e) {

            Log::error('SpareSubCategory deletion failed', [
                'uuid'  => $uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete spare subcategory.',
            ], 500);
        }
    }
}