<?php

namespace App\Http\Controllers\V1\Assets\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Assets\Web\FrigeCustomerUpdateRequest;
use App\Http\Resources\V1\Assets\Web\FrigeCustomerUpdateResource;
use App\Services\V1\Assets\Web\FrigeCustomerUpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FrigeCustomerUpdateController extends Controller
{
    public function __construct(
        protected FrigeCustomerUpdateService $service
    ) {}

    /**
     * 🔹 List API
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->service->list($request->all());

        return response()->json([
            'status' => 'success',
            'code'   => 200,
            'data'   => FrigeCustomerUpdateResource::collection($data),
            'pagination' => [
                'page'          => $data->currentPage(),
                'limit'         => $data->perPage(),
                'total_pages'   => $data->lastPage(),
                'total_records' => $data->total(),
            ]
        ]);
    }


    /**
     * 🔹 Get by UUID
     */
    public function show(string $uuid): JsonResponse
    {
        $record = $this->service->getByUuid($uuid);

        return response()->json([
            'status' => 'success',
            'code'   => 200,
            'data'   => new FrigeCustomerUpdateResource($record),
        ]);
    }

    /**
     * 🔹 Update by UUID
     */
    public function update(
        FrigeCustomerUpdateRequest $request,
        string $uuid
    ): JsonResponse {
        $record = $this->service->updateByUuid(
            $uuid,
            $request->validated()
        );

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Fridge customer update successfully updated',
            'data'    => new FrigeCustomerUpdateResource($record),
        ]);
    }

    public function export(Request $request)
    {
        try {
            $result = $this->service->export($request);

            return response()->json([
                'status' => true,
                'download_url' => $result['download_url']
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function globalSearch(Request $request): JsonResponse
    {
        $search  = $request->query('search');
        $perPage = (int) $request->query('per_page', 50);

        try {
            $data = $this->service->globalSearch($search, $perPage);

            return response()->json([
                'status' => 'success',
                'code'   => 200,
                'data'   => FrigeCustomerUpdateResource::collection($data),
                'pagination' => [
                    'page'          => $data->currentPage(),
                    'limit'         => $data->perPage(),
                    'total_pages'   => $data->lastPage(),
                    'total_records' => $data->total(),
                ]
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
