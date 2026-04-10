<?php

namespace App\Http\Controllers\V1\EfrisAPI;

use App\Http\Controllers\Controller;
use App\Services\V1\EfrisAPI\DailyStockCountService;
use Illuminate\Http\JsonResponse;

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
}
