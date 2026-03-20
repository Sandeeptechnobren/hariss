<?php

namespace App\Http\Controllers;

use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\V1\Logs\LogListResource;
use App\Http\Resources\V1\Logs\LogResource;

class LogController extends Controller
{
    protected LogService $service; 

    public function __construct(LogService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request): JsonResponse
    {
        $logs = $this->service->getLogs($request);

        return response()->json([
            'status' => 'success',
            'code'   => 200,
            'message'=> 'Logs fetched successfully',
            'data'   => LogListResource::collection($logs->items()),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'per_page'     => $logs->perPage(),
                'total'        => $logs->total(),
                'last_page'    => $logs->lastPage(),
            ]
        ], 200);
    }

    public function show(int $id): JsonResponse
    {
        $log = $this->service->getLogById($id);

        if (!$log) {
            return response()->json([
                'status'  => 'error',
                'code'    => 404,
                'message' => 'Log not found',
                'data'    => null
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Log fetched successfully',
            'data'    => new LogResource($log),
        ], 200);
    }
}
