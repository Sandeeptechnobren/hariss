<?php

namespace App\Http\Controllers\V1\Assets\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Assets\Web\CallRegisterStoreRequest;
use App\Http\Resources\V1\Assets\Web\CallRegisterResource;
use App\Services\V1\Assets\Web\CallRegisterService;
use App\Traits\ApiResponse;
use App\Http\Requests\V1\Assets\Web\CallRegisterUpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class CallRegisterController extends Controller
{
    use ApiResponse;

    protected CallRegisterService $service;

    public function __construct(CallRegisterService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/call-register",
     *     tags={"CallRegister"},
     *     summary="Get paginated Call Register list",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="outlet_name",
     *         in="query",
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Data fetched successfully"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        // ✅ ADD THIS BLOCK (Nothing else changed)
        if ($request->boolean('dropdown')) {

            $records = $this->service->getAll(0, [], true);

            return $this->success(
                $records,
                "Call Register dropdown fetched successfully"
            );
        }

        $perPage = $request->get('limit', 20);
        $filters = $request->only(['status', 'outlet_name', 'ticket_type', 'owner_name']);

        $records = $this->service->getAll($perPage, $filters);

        return $this->success(
            CallRegisterResource::collection($records->items()),
            "Call Register fetched successfully",
            200,
            [
                'page'         => $records->currentPage(),
                'limit'        => $records->perPage(),
                'totalPages'   => $records->lastPage(),
                'totalRecords' => $records->total(),
            ]
        );
    }

    /**
     * @OA\Post(
     *     path="/api/call-register",
     *     tags={"CallRegister"},
     *     summary="Create Call Register",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Call Register data payload"
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Created successfully"
     *     )
     * )
     */
    public function store(CallRegisterStoreRequest $request): JsonResponse
    {
        // dd($request);
        $record = $this->service->create($request->validated());

        return $this->success(
            new CallRegisterResource($record),
            "Call Register created successfully",
            201
        );
    }

    /**
     * @OA\Get(
     *     path="/api/call-register/{uuid}",
     *     tags={"CallRegister"},
     *     summary="Get single Call Register by UUID",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Fetched successfully"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        $record = $this->service->findByUuid($uuid);

        if (!$record) {
            return $this->fail("Record not found", 404);
        }

        return $this->success(
            new CallRegisterResource($record),
            "Record fetched successfully"
        );
    }

    /**
     * @OA\Put(
     *     path="/api/call-register/{uuid}",
     *     tags={"CallRegister"},
     *     summary="Update Call Register",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true),
     *
     *     @OA\Response(response=200, description="Updated successfully")
     * )
     */
    public function update(CallRegisterUpdateRequest $request, string $uuid)
    {
        $record = $this->service->updateByUuid($uuid, $request->validated());

        return $this->success(
            new CallRegisterResource($record),
            "Record updated successfully"
        );
    }



    /**
     * @OA\Delete(
     *     path="/api/call-register/{uuid}",
     *     tags={"CallRegister"},
     *     summary="Delete Call Register",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Deleted successfully")
     * )
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->service->deleteByUuid($uuid);

            return $this->success(
                null,
                "Record deleted successfully"
            );
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage(), 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/call-register/global-search",
     *     tags={"CallRegister"},
     *     summary="Global search",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Search result")
     * )
     */
    public function global_search(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 50);
            $search  = $request->get('search');

            $records = $this->service->globalSearch($search, $perPage);

            return $this->success(
                $records->items(),
                "Search result",
                200,
                [
                    'page'         => $records->currentPage(),
                    'limit'        => $records->perPage(),
                    'totalPages'   => $records->lastPage(),
                    'totalRecords' => $records->total(),
                ]
            );
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }


    public function getChillerData(Request $request): JsonResponse
    {
        try {
            $serial = $request->get('serial_number');

            if (!$serial) {
                return $this->fail("serial_number is required", 422);
            }

            $data = $this->service->getChillerBySerial($serial);

            if (!$data) {
                return $this->fail("No chiller found for serial number: {$serial}", 404);
            }

            return $this->success($data, "Chiller & agent data fetched successfully");
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    public function findCurrentCustomer(Request $request): JsonResponse
    {
        $request->validate([
            'search' => 'required|string'
        ]);

        $search = trim($request->query('search'));

        $customer = $this->service->findCurrentCustomer($search);

        if (!$customer) {
            return response()->json([
                'status'  => false,
                'message' => 'Customer not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $customer
        ]);
    }


    public function exportCallRegister(Request $request)
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
    public function globalFilter(Request $request): JsonResponse
    {
        $perPage = $request->get('limit', 20);

        // If payload comes inside "filter" object, extract it
        $filters = $request->except(['limit']);

        $records = $this->service->globalFilter($perPage, $filters);

        return $this->success(
            CallRegisterResource::collection($records->items()),
            "Call Register fetched successfully",
            200,
            [
                'page'         => $records->currentPage(),
                'limit'        => $records->perPage(),
                'totalPages'   => $records->lastPage(),
                'totalRecords' => $records->total(),
            ]
        );
    }
}
