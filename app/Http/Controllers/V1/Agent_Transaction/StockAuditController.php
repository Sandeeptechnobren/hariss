<?php

namespace App\Http\Controllers\V1\Agent_Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Agent_Transaction\StoreStockAuditRequest;
use App\Http\Resources\V1\Agent_Transaction\StockAuditResource;
use Illuminate\Http\Request;
use App\Http\Resources\WarehouseStockResource;
use App\Services\V1\Agent_Transaction\StockAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Helpers\ResponseHelper;

class StockAuditController extends Controller
{
    protected $service;

    public function __construct(StockAuditService $service)
    {
        $this->service = $service;
    }

    public function getStock(Request $request)
    {
        try {
            // ✅ Validation
            $validated = $request->validate([
                'warehouse' => 'required|integer', // 👈 now mandatory
                'search'    => 'nullable|string'
            ]);

            $data = $this->service->getStock($validated);

            $response = $data->map(function ($row) {

                $uoms = $row->item?->itemUoms?->map(function ($uom) {
                    return [
                        'uom_id' => $uom->uom_id,
                        'name'   => $uom->name,
                    ];
                })->values();

                return [
                    'item_id'   => $row->item?->id,
                    'item_name' => $row->item?->name,
                    'erp_code'  => $row->item?->erp_code,
                    'qty'       => (float) $row->qty,
                    'uoms'      => $uoms ?? []
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'Warehouse stock fetched successfully',
                'data'    => $response
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function store(StoreStockAuditRequest $request)
    {
        try {
            $data = $this->service->store($request->validated());

            return response()->json([
                'status'  => true,
                'message' => 'Stock audit created successfully',
                'data'    => new StockAuditResource($data)
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {

            $perPage = $request->get('limit', 20);

            $filters = $request->only([
                'warehouse_id',
                'from_date',
                'to_date',
                'search'
            ]);
            $records = $this->service->getAll($perPage, $filters);
            return ResponseHelper::paginatedResponse(
                'Stock audit fetched successfully',
                StockAuditResource::class,
                $records
            );
        } catch (\Throwable $e) {

            Log::error('Stock Audit Index Error', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
                'request' => $request->all()
            ]);

            return response()->json([
                'status'  => false,
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'Something went wrong, please try again later'
            ], 500);
        }
    }


    public function show(string $uuid): JsonResponse
    {
        try {

            $record = $this->service->getByUuid($uuid);

            return response()->json([
                'status'  => 'success',
                'message' =>  'Stock audit fetched successfully',
                'data'    => new StockAuditResource($record),
            ], 200);
        } catch (\Throwable $e) {

            Log::error('Stock Audit Show Error', [
                'uuid'  => $uuid,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status'  => false,
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'Something went wrong, please try again later'
            ], 500);
        }
    }

    public function globalFilter(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('limit', 50);
            $filters = $request->except(['limit']);

            $records = $this->service->globalFilter($perPage, $filters);

            $pagination = [
                'current_page' => $records->currentPage(),
                'last_page'    => $records->lastPage(),
                'per_page'     => $records->perPage(),
                'total'        => $records->total(),
            ];

            return response()->json([
                'status'     => 'success',
                'code'       => 200,
                'message'    => 'Stock audit fetched successfully',
                'data'       => StockAuditResource::collection($records),
                'pagination' => $pagination,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve invoices',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
