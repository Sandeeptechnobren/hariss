<?php

namespace App\Http\Controllers\V1\Assets\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Assets\Web\StoreSpareRequest;
use App\Http\Requests\V1\Assets\Web\UpdateSpareRequest;
use App\Http\Resources\V1\Assets\Web\SpareResource;
use App\Services\V1\Assets\Web\SpareService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SpareController extends Controller
{
    protected SpareService $service;

    public function __construct(SpareService $service)
    {
        $this->service = $service;
    }

 public function store(StoreSpareRequest $request): JsonResponse
    {
        try {
            $spareCategory = $this->service->createSpare(
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Spare created successfully.',
                'data'    => new SpareResource($spareCategory),
            ], 201);

        } catch (Throwable $e) {

            Log::error('Spare creation failed', [
                'error' => $e->getMessage(),
                'data'  => $request->validated(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create spare.',
            ], 500);
        }
    }

public function index(Request $request)
 {
    $perPage = $request->get('per_page', 50);
    $filters = $request->only(['osa_code', 'spare_name', 'spare_categoryid','spare_subcategoryid','plant']);

    $data = $this->service->listspare([
        'osa_code' => $filters['osa_code'] ?? null,
        'spare_name' => $filters['spare_name'] ?? null,
        'spare_categoryid' => $filters['spare_categoryid'] ?? null,
        'spare_subcategoryid' => $filters['spare_subcategoryid'] ?? null,
        'plant' => $filters['plant'] ?? null,
    ], $perPage);

    return response()->json([
        'status'  => 'success',
        'code'    => 200,
        'message' => 'Spare fetched successfully',
        'data'    => SpareResource::collection($data->items()),
        'pagination' => [
            'currentPage' => $data->currentPage(),
            'perPage'     => $data->perPage(),
            'lastPage'    => $data->lastPage(),
            'total'       => $data->total(),
        ]
    ]);
}


public function show(string $uuid)
{
    // dd($uuid);
    $bank = $this->service->getByUuid($uuid);

    if (!$bank) {
        return response()->json([
            'status'  => 'error',
            'code'    => 404,
            'message' => 'Spare not found',
            'data'    => null
        ], 404);
    }

    return response()->json([
        'status'  => 'success',
        'code'    => 200,
        'message' => 'Spare fetched successfully',
        'data'    => new SpareResource($bank)
    ]);
}

public function update(UpdateSpareRequest $request, string $uuid): JsonResponse
{
    try {
        $updated = $this->service->updateBonus($uuid, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Spare updated successfully.',
            'data' => new SpareResource($updated),
        ], 200);

    } catch (Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'Failed to update spare: ' . $e->getMessage(),
        ], 500);
    }
}
   public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->service->deleteByUuid($uuid);

            return response()->json([
                'success' => true,
                'message' => 'Spare deleted successfully.',
            ], 200);

        } catch (ModelNotFoundException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Spare not found.',
            ], 404);

        } catch (Throwable $e) {

            Log::error('Spare deletion failed', [
                'uuid'  => $uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete spare.',
            ], 500);
        }
    }
}