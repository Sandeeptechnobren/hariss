<?php

namespace App\Http\Controllers\V1\Agent_Transaction;

use App\Http\Controllers\Controller;
use App\Services\V1\Agent_Transaction\SalesTeamTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesTeamTrackingController extends Controller
{
    protected SalesTeamTrackingService $service;

    public function __construct(SalesTeamTrackingService $service)
    {
        $this->service = $service;
    }

    // public function show(Request $request): JsonResponse
    // {
    //     $salesmanId = $request->query('salesman_id');

    //     if (! $salesmanId) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => 'salesman_id is required'
    //         ], 422);
    //     }

    //     $data = $this->service->getRouteBySalesmanId($salesmanId);

    //     return response()->json([
    //         'status' => 'success',
    //         'data'   => $data
    //     ]);
    // }

    public function show(Request $request): JsonResponse
    {
        // dd($request);
        $data = $this->service->getStaticRouteResponse();
// dd($data);
        return response()->json([
            'status' => 'success',
            'data'   => $data
        ]);
    }
}
