<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use App\Services\V1\MasterServices\Web\RouteTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;
use App\Helpers\LogHelper;

class RouteTransferController extends Controller
{
    protected RouteTransferService $service;

    public function __construct(RouteTransferService $service)
    {
        $this->service = $service;
    }

    public function transfer(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'old_route_id' => 'required|integer',
                'new_route_id' => 'required|integer|different:old_route_id',
            ]);

            $result = $this->service->transferRoute($data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Route transfer completed successfully',
                'data'    => $result,
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    // public function transfer(Request $request): JsonResponse
    // {
    //     try {
    //         $data = $request->validate([
    //             'old_route_id' => 'required|integer',
    //             'new_route_id' => 'required|integer|different:tbl_route_id',
    //         ]);
    //         $result = $this->service->transferRoute($data);
    //         LogHelper::store(
    //             'master',
    //             'route_transfer',
    //             'update',
    //             [
    //                 'old_route_id' => $data['old_route_id'],
    //                 'new_route_id' => $data['new_route_id'],
    //             ],
    //             $result,
    //             auth()->id()
    //         );

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Route updated successfully',
    //             'data' => $result,
    //         ], 200);
    //     } catch (Throwable $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data'   => $this->service->getHistory(),
        ], 200);
    }
    // public function index(Request $request): JsonResponse
    // {
    //     $filters = $request->only([
    //         'route_id',
    //         'from_date',
    //         'to_date'
    //     ]);

    //     $data = $this->service->getHistory($filters);

    //     return response()->json([
    //         'status' => 'success',
    //         'total'  => count($data),
    //         'data'   => $data
    //     ]);
    // }
}
