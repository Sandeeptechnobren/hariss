<?php

namespace App\Services\V1\Agent_transaction\Mob;

use App\Models\Agent_Transaction\ExchangeHeader;
use App\Models\Agent_Transaction\ExchangeInInvoice;
use App\Models\Agent_Transaction\ExchangeInReturn;
use App\Models\Agent_Transaction\ResonType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;


class ExchangeService
{
public function create(array $data): ?ExchangeHeader
{
    try {
        DB::beginTransaction();

        $header = ExchangeHeader::create([
            'exchange_code' => $data['exchange_code'] ?? null,
            'currency'      => $data['currency'] ?? null,
            'status'        => $data['status'] ?? 1,
            'warehouse_id'  => $data['warehouse_id'],
            'customer_id'   => $data['customer_id'],
            'salesman_id'   => $data['salesman_id'],
            'route_id'      => $data['route_id'],
            'comment'       => $data['comment'] ?? NULL,
            'latitude'      => $data['latitude'] ?? null,
            'longitude'     => $data['longitude'] ?? null,
        ]);

        if (!empty($data['invoices']) && is_array($data['invoices'])) {
            foreach ($data['invoices'] as $invoice) {
                ExchangeInInvoice::create([
                    'header_id'     => $header->id,
                    'exchange_code' => $header->exchange_code,
                    'item_id'       => $invoice['item_id'],
                    'uom_id'        => $invoice['uom_id'],
                    'item_price'    => $invoice['item_price'] ?? 0,
                    'item_quantity' => $invoice['item_quantity'] ?? 0,
                    'total'         => $invoice['total'] ?? 0,
                    'status'        => $invoice['status'] ?? 1,
                ]);
            }
        }

        if (!empty($data['returns']) && is_array($data['returns'])) {
            foreach ($data['returns'] as $return) {
                ExchangeInReturn::create([
                    'header_id'     => $header->id,
                    'exchange_code' => $header->exchange_code,
                    'item_id'       => $return['item_id'],
                    'uom_id'        => $return['uom_id'],
                    'item_price'    => $return['item_price'] ?? 0,
                    'item_quantity' => $return['item_quantity'] ?? 0,
                    'total'         => $return['total'] ?? 0,
                    'status'        => $return['status'] ?? 1,
                    'return_type'   => $return['return_type'] ?? null,
                    'region'        => $return['reason'] ?? null,
                ]);
            }
        }

        DB::commit();

        /**
         * ================================================
         * ?? APPLY WORKFLOW AUTOMATICALLY (SAVED PATTERN)
         * ================================================
         */
        $workflow = DB::table('htapp_workflow_assignments')
            ->where('process_type', 'Exchange_Header')
            ->where('is_active', true)
            ->first();

        if ($workflow) {
            app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)
                ->startApproval([
                    'workflow_id'  => $workflow->workflow_id,
                    'process_type' => 'Exchange_Header',
                    'process_id'   => $header->id,
                ]);
        }

        return $header->load(['invoices', 'returns']);

    } catch (Exception $e) {
        DB::rollBack();
        Log::error('ExchangeService::create Error: ' . $e->getMessage());
        throw $e;
    }
}
    public function getList()
    {
        return ResonType::with('returnType:id,return_type')
            ->get()
            ->map(function ($item) {
                return [
                    'id'          => $item->id,
                    'name'        => $item->reson,
                    'return_id'   => $item->returnType?->id,
                    'return_name' => $item->returnType?->return_type,
                ];
            });
    }
}

