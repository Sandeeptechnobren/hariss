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

    // public function show(Request $request): JsonResponse
    // {
    //     $data = $this->service->getStaticRouteResponse($request);

    //     return response()->json([
    //         'status' => 'success',
    //         'data'   => $data
    //     ]);
    // }


    public function getSalesman(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required',
        ]);

        $data = $this->service->getSalesmen($request->warehouse_id);

        return response()->json([
            'status' => true,
            'message' => 'Salesmen fetched successfully',
            'data' => $data
        ]);
    }


    public function track(Request $request)
    {
        $request->validate([
            'salesman_id' => 'required',
            'warehouse_id' => 'required',
            'date' => 'required|date',
        ]);

        $data = $this->service->getSalesmanLocationsWithCustomers(
            $request->salesman_id,
            $request->warehouse_id,
            $request->date
        );

        return response()->json([
            'status' => true,
            'message' => 'Salesman locations fetched successfully',
            'data' => $data
        ]);
    }
}
