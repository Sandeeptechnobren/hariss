<?php

namespace App\Http\Controllers\V1\EfrisAPI;

use App\Http\Controllers\Controller;
use App\Services\V1\EfrisAPI\DailyStockCountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DailyStockCountController extends Controller
{
    protected $service;

    public function __construct(DailyStockCountService $service)
    {
        $this->service = $service;
    }

    public function dailyStockCount(): JsonResponse
    {
        try {
            $message = $this->service->insertData();

            return response()->json([
                'success' => true,
                'message' => $message
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getWarehouseStockFromCronJobs(Request $request)
    {
        $filters = $request->only([
            'warehouse_id',
            'filter'
        ]);

        if (empty($filters['warehouse_id'])) {
            return response()->json([
                'status' => false,
                'message' => 'warehouse_id is required'
            ], 400);
        }

        $data = $this->service->getWarehouseStockFromCronJobs($filters);

        return response()->json([
            'status' => true,
            'message' => 'Stock data fetched successfully',
            'data' => $data
        ]);
    }
}
