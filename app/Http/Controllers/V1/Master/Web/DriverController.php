<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\DriverRequest;
use App\Http\Requests\V1\MasterRequests\Web\UpdateDriverRequest;
use App\Http\Resources\V1\Master\Web\DriverResource;
use App\Models\Driver;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Services\V1\MasterServices\Web\DriverService;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    protected DriverService $service;

    public function __construct(DriverService $service)
    {
        $this->service = $service;
    }

  public function store(DriverRequest $request): JsonResponse
    {
        try {
            $bank = $this->service->createBank($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Driver created successfully.',
                'data' => new DriverResource($bank),
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create driver: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function index(Request $request)
{
    $perPage = $request->get('per_page', 50);

    $filters = $request->only(['device_name', 'osa_code', 'IMEI_1','IMEI_2','modelno']);

    $data = $this->service->listBanks([
        'osa_code' => $filters['osa_code'] ?? null,
        'device_name' => $filters['device_name'] ?? null,
        'modelno' => $filters['modelno'] ?? null,
        'IMEI_1' => $filters['IMEI_1'] ?? null,
        'IMEI_2' => $filters['IMEI_2'] ?? null,
    ], $perPage);

    return response()->json([
        'status'     => 'success',
        'code'       => 200,
        'message'    => 'Driver fetched successfully',
        'data'       => DriverResource::collection($data->items()),
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
            'message' => 'Driver not found',
            'data'    => null
        ], 404);
    }

    return response()->json([
        'status'  => 'success',
        'code'    => 200,
        'message' => 'Driver fetched successfully',
        'data'    => new DriverResource($bank)
    ]);
}



public function update(UpdateDriverRequest $request, string $uuid)
{
    $validatedData = $request->validated();
    $updatedBank = $this->service->updateBankByUuid($uuid, $validatedData);

    if (!$updatedBank) {
        return response()->json([
            'status'  => 'error',
            'code'    => 404,
            'message' => 'Driver not found',
            'data'    => null
        ], 404);
    }

    return response()->json([
        'status'  => 'success',
        'code'    => 200,
        'message' => 'Driver updated successfully',
        'data'    => new DriverResource($updatedBank)
    ]);
}
}