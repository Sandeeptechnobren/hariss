<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Services\V1\Settings\Web\FridgeStatusService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FridgeStatusController extends Controller
{
    protected FridgeStatusService $service;

    public function __construct(FridgeStatusService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->except(['page', 'per_page']);

            $result = $this->service->list($filters);

            return response()->json([
                'status'  => 'success',
                'message' => 'Fridge status list fetched successfully',
                'data'    => $result
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
