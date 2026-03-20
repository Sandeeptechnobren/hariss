<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\ManufacturingRequest;
use App\Http\Requests\V1\Settings\Web\UpdateManufacturingRequest;
use App\Http\Resources\V1\Settings\Web\ManufacturingResource;
use App\Services\V1\Settings\Web\ManufacturingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class ManufacturingController extends Controller
{
    protected ManufacturingService $service;

    public function __construct(ManufacturingService $service)
    {
        $this->service = $service;
    }

  public function store(ManufacturingRequest $request): JsonResponse
    {
        try {
            $bank = $this->service->createBank($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Manufacturing created successfully.',
                'data' => new ManufacturingResource($bank),
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create manufacturing: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function index(Request $request)
{
    $perPage = $request->get('per_page', 50);

    $filters = $request->only(['device_name', 'osa_code', 'IMEI_1','IMEI_2','modelno']);

    $data = $this->service->listBanks([
        'device_name' => $filters['device_name'] ?? null,
        'osa_code' => $filters['osa_code'] ?? null,
        'IMEI_1' => $filters['IMEI_1'] ?? null,
        'IMEI_2' => $filters['IMEI_2'] ?? null,
        'modelno' => $filters['modelno'] ?? null,
    ], $perPage);

    return response()->json([
        'status'     => 'success',
        'code'       => 200,
        'message'    => 'Manufacturing fetched successfully',
        'data'       => ManufacturingResource::collection($data->items()),
        'pagination' => [
            'page'         => $data->currentPage(),
            'limit'        => $data->perPage(),
            'totalPages'   => $data->lastPage(),
            'totalRecords' => $data->total(),
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
            'message' => 'Manufacturing not found',
            'data'    => null
        ], 404);
    }

    return response()->json([
        'status'  => 'success',
        'code'    => 200,
        'message' => 'Manufacturing fetched successfully',
        'data'    => new ManufacturingResource($bank)
    ]);
}



public function update(UpdateManufacturingRequest $request, string $uuid)
{
    $validatedData = $request->validated();
    $updatedBank = $this->service->updateBankByUuid($uuid, $validatedData);

    if (!$updatedBank) {
        return response()->json([
            'status'  => 'error',
            'code'    => 404,
            'message' => 'Manufacturing not found',
            'data'    => null
        ], 404);
    }

    return response()->json([
        'status'  => 'success',
        'code'    => 200,
        'message' => 'Manufacturing updated successfully',
        'data'    => new ManufacturingResource($updatedBank)
    ]);
}
}