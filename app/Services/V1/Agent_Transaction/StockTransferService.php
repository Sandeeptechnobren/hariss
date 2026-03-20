<?php

namespace App\Services\V1\Agent_Transaction;

use App\Models\StockTransferHeader;
use Illuminate\Support\Collection;
use App\Models\WarehouseStock;
use App\Models\Warehouse;
use Illuminate\Pagination\LengthAwarePaginator; // ✅ IMPORTANT
use Illuminate\Support\Facades\Log;
use Throwable;

class StockTransferService
{
    // public function list(int $perPage = 50): LengthAwarePaginator
    // {
    //     try {
    //         return StockTransferHeader::query()
    //             ->with([
    //                 'details.item:id,name,erp_code'
    //             ])
    //             ->whereNull('deleted_at')
    //             ->orderBy('id', 'desc')
    //             ->paginate($perPage);
    //     } catch (Throwable $e) {
    //         dd($e);
    //         Log::error('[StockTransferService] List API failed', [
    //             'error' => $e->getMessage(),
    //         ]);

    //         throw new \Exception(
    //             'Unable to fetch stock transfer list. Please try again later.'
    //         );
    //     }
    // }


    public function list(int $perPage = 50, array $filters = []): LengthAwarePaginator
    {
        try {
            $query = StockTransferHeader::query()
                ->with([
                    'details.item:id,name,erp_code',
                    'sourceWarehouse:id,warehouse_code,warehouse_name',
                    'destinyWarehouse:id,warehouse_code,warehouse_name'
                ])
                ->whereNull('deleted_at')
                ->orderByDesc('id');

            // 🔹 OSA Code
            if (!empty($filters['osa_code'])) {
                $query->where('osa_code', 'ILIKE', '%' . $filters['osa_code'] . '%');
            }

            // 🔹 Status
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            // 🔹 Warehouse filter (SOURCE OR DESTINY)
            // if (!empty($filters['warehouse_id'])) {
            //     $warehouseId = $filters['warehouse_id'];

            //     $query->where(function ($q) use ($warehouseId) {
            //         $q->where('source_warehouse', $warehouseId)
            //             ->orWhere('destiny_warehouse', $warehouseId);
            //     });
            // }
            if (!empty($filters['source_warehouse'])) {
                $query->where('source_warehouse', $filters['source_warehouse']);
            }

            // 🔹 Destiny Warehouse
            if (!empty($filters['destiny_warehouse'])) {
                $query->where('destiny_warehouse', $filters['destiny_warehouse']);
            }

            // 🔹 Transfer Date
            if (!empty($filters['transfer_date'])) {
                $query->whereDate('transfer_date', $filters['transfer_date']);
            }

            // 🔹 Date Range
            if (!empty($filters['from_date'])) {
                $query->whereDate('transfer_date', '>=', $filters['from_date']);
            }

            if (!empty($filters['to_date'])) {
                $query->whereDate('transfer_date', '<=', $filters['to_date']);
            }

            return $query->paginate($perPage);
        } catch (Throwable $e) {
            dd($e);
            Log::error('[StockTransferService] List API failed', [
                'filters' => $filters,
                'error'   => $e->getMessage(),
            ]);

            throw new \Exception(
                'Unable to fetch stock transfer list. Please try again later.'
            );
        }
    }

    // public function list(int $perPage = 50): LengthAwarePaginator
    // {
    //     try {

    //         $transfers = StockTransferHeader::query()
    //             ->with([
    //                 'details.item:id,name,erp_code'
    //             ])
    //             ->whereNull('deleted_at')
    //             ->orderBy('id', 'desc')
    //             ->paginate($perPage);
    //             $transfers->getCollection()->transform(function ($transfer) {
    //             $workflowRequest = HtappWorkflowRequest::where('process_type', 'Distributor_Stock_Transfer')
    //                 ->where('process_id', $transfer->id)
    //                 ->orderBy('id', 'DESC')
    //                 ->first();
    //             if ($workflowRequest) {
    //                 $currentStep = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
    //                     ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
    //                     ->orderBy('step_order')
    //                     ->first();
    //                 $totalSteps = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)->count();
    //                 $completedSteps = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
    //                     ->where('status', 'APPROVED')
    //                     ->count();
    //                 $lastApprovedStep = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
    //                     ->where('status', 'APPROVED')
    //                     ->orderBy('step_order', 'DESC')
    //                     ->first();
    //                 $transfer->approval_status = $lastApprovedStep
    //                     ? $lastApprovedStep->message
    //                     : 'Initiated';
    //                 $transfer->current_step     = $currentStep?->title;
    //                 $transfer->request_step_id = $currentStep?->id;
    //                 $transfer->progress        = $totalSteps > 0
    //                     ? ($completedSteps . '/' . $totalSteps)
    //                     : null;

    //             } else {
    //                 $transfer->approval_status = null;
    //                 $transfer->current_step     = null;
    //                 $transfer->request_step_id = null;
    //                 $transfer->progress        = null;
    //             }

    //             return $transfer;
    //         });
    //         return $transfers;

    //     } catch (Throwable $e) {

    //         Log::error('[StockTransferService] List API failed', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //         ]);

    //         throw new \Exception(
    //             'Unable to fetch stock transfer list. Please try again later.'
    //         );
    //     }
    // }

    public function findByUuid(string $uuid): array
    {
        try {
            $header = StockTransferHeader::query()
                ->with([
                    'details.item:id,name,erp_code'
                ])
                ->where('uuid', $uuid)
                ->whereNull('deleted_at')
                ->first();

            if (!$header) {
                throw new \Exception('Stock transfer record not found.');
            }

            // 🔹 Fetch warehouses (once)
            $sourceWarehouse = Warehouse::select('id', 'warehouse_code', 'warehouse_name')
                ->where('id', $header->source_warehouse)
                ->first();

            $destinyWarehouse = Warehouse::select('id', 'warehouse_code', 'warehouse_name')
                ->where('id', $header->destiny_warehouse)
                ->first();

            // 🔹 Prepare item data
            $items = $header->details->map(function ($detail) use ($header) {

                $fromStock = WarehouseStock::where('warehouse_id', $header->source_warehouse)
                    ->where('item_id', $detail->item_id)
                    ->whereNull('deleted_at')
                    ->first();

                $toStock = WarehouseStock::where('warehouse_id', $header->destiny_warehouse)
                    ->where('item_id', $detail->item_id)
                    ->whereNull('deleted_at')
                    ->first();

                return [
                    'item_id'   => $detail->item_id,
                    'item_name' => $detail->item->name ?? null,
                    'erp_code'  => $detail->item->erp_code ?? null,

                    'transfer_qty' => $detail->transfer_qty,

                    // ✅ stock info
                    'source_warehouse_stock'   => $fromStock?->qty ?? 0,
                    'destiny_warehouse_stock'  => $toStock?->qty ?? 0,
                ];
            });

            return [
                'id'    => $header->id,
                'uuid'  => $header->uuid,
                'osa_code' => $header->osa_code,

                // ✅ source warehouse
                'source_warehouse' => [
                    'id'   => $sourceWarehouse->id ?? null,
                    'code' => $sourceWarehouse->warehouse_code ?? null,
                    'name' => $sourceWarehouse->warehouse_name ?? null,
                ],

                // ✅ destination warehouse
                'destiny_warehouse' => [
                    'id'   => $destinyWarehouse->id ?? null,
                    'code' => $destinyWarehouse->warehouse_code ?? null,
                    'name' => $destinyWarehouse->warehouse_name ?? null,
                ],

                'transfer_date' => $header->transfer_date,
                'status'        => $header->status,

                'items' => $items,
            ];
        } catch (Throwable $e) {

            Log::error('[StockTransferService] Fetch by UUID failed', [
                'uuid'  => $uuid,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception(
                'Unable to fetch stock transfer details. Please try again later.'
            );
        }
    }
}
