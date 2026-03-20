<?php

namespace App\Http\Controllers\V1\Agent_Transaction;

use App\Http\Controllers\Controller;
use App\Services\V1\Agent_Transaction\StockTransferService;
use App\Http\Resources\V1\Agent_Transaction\StockTransferHeaderResource;
use Illuminate\Http\JsonResponse;
use Throwable;
use Illuminate\Http\Request;

class StockTransferListController extends Controller
{
    protected StockTransferService $service;

    public function __construct(StockTransferService $service)
    {
        $this->service = $service;
    }

    public function list(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 10);

            // 🔹 Allowed filters
            $filters = $request->only([
                'osa_code',
                'status',
                'source_warehouse',
                'destiny_warehouse',
                // 'warehouse_id',   // ✅ SINGLE PARAM
                'transfer_date',
                'from_date',
                'to_date',
            ]);

            $paginator = $this->service->list($perPage, $filters);

            return response()->json([
                'status' => 'success',
                'data'   => StockTransferHeaderResource::collection($paginator->items()),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                    'last_page'    => $paginator->lastPage(),
                ],
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    // public function list(Request $request): JsonResponse
    // {
    //     try {
    //         $perPage = (int) $request->get('per_page', 10);

    //         $paginator = $this->service->list($perPage);

    //         return response()->json([
    //             'status' => 'success',
    //             'data'   => StockTransferHeaderResource::collection($paginator->items()),
    //             'pagination'   => [
    //                 'current_page' => $paginator->currentPage(),
    //                 'per_page'     => $paginator->perPage(),
    //                 'total'        => $paginator->total(),
    //                 'last_page'    => $paginator->lastPage(),
    //             ],
    //         ], 200);
    //     } catch (Throwable $e) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function show(string $uuid): JsonResponse
    {
        try {
            $record = $this->service->findByUuid($uuid);

            return response()->json([
                'status' => 'success',
                'data'   => $record,
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 404);
        }
    }
}
