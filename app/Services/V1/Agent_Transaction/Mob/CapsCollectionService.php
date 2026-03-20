<?php

namespace App\Services\V1\Agent_transaction\Mob;

use App\Models\Agent_Transaction\CapsCollectionHeader;
use App\Models\Agent_Transaction\CapsCollectionDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;
use App\Models\CapsCollectionQty;

class CapsCollectionService
{
    public function create(array $data): ?CapsCollectionHeader
    {
        try {
            DB::beginTransaction();

            $code = $data['code'] ?? null;
            $header = CapsCollectionHeader::create([
                'warehouse_id' => $data['warehouse_id'],
                'route_id' => $data['route_id'] ?? null,
                'salesman_id' => $data['salesman_id'] ?? null,
                'customer' => $data['customer'],
                'status' => $data['status'] ?? 1,
                'code' => $code,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ]);
            if (!empty($data['details']) && is_array($data['details'])) {

                foreach ($data['details'] as $detail) {

                    CapsCollectionDetail::create([
                        'header_id' => $header->id,
                        'item_id' => $detail['item_id'],
                        'uom_id' => $detail['uom_id'],
                        'collected_quantity' => $detail['collected_quantity'] ?? null,
                    ]);


                    $warehouseId = $header->warehouse_id;
                    $itemId = $detail['item_id'];
                    $qty = $detail['collected_quantity'];

                    $existingQty = CapsCollectionQty::where('warehouse_id', $warehouseId)
                        ->where('item_id', $itemId)
                        ->lockForUpdate()
                        ->first();

                    if ($existingQty) {
                        $existingQty->update([
                            'quantity' => $existingQty->quantity + $qty
                        ]);
                    } else {
                        CapsCollectionQty::create([
                            'warehouse_id' => $warehouseId,
                            'item_id' => $itemId,
                            'quantity' => $qty
                        ]);
                    }
                }
            }

            DB::commit();
           $workflow = DB::table('htapp_workflow_assignments')
            ->where('process_type', 'Caps_Collection_Header')
            ->where('is_active', true)
            ->first();
        if ($workflow) {
            app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)
                ->startApproval([
                    'workflow_id'  => $workflow->workflow_id,
                    'process_type' => 'Caps_Collection_Header',
                    'process_id'   => $header->id,
                ]);
        }
            return $header->load('details.item', 'details.uom');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('CapsCollectionService::create Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
