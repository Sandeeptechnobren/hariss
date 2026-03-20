<?php

namespace App\Services\V1\MasterServices\Mob;

use App\Models\Agent_Transaction\UnloadDetail;
use App\Models\Agent_Transaction\UnloadHeader;
use App\Models\Agent_Transaction\LoadHeader;
use App\Models\WarehouseStock;
use App\Models\Item;
use Illuminate\Support\Collection;
use App\Models\Route;
use App\Models\Salesman;
use App\Models\Warehouse;
use App\Models\ItemUOM;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use App\Helpers\DataAccessHelper;
use App\Models\Agent_Transaction\LoadDetail;

class UnloadHeaderService
{
    public function store(array $data)
    {
        DB::beginTransaction();
        try {
        $osaCodeHeader = $this->generateOsaCode('SUH');
        $warehouseId = $data['warehouse_id'] ?? null;
        $salesmanId  = $data['salesman_id'];
        if (!$warehouseId) {
            throw new \Exception("warehouse_id is required.");
        }
        $header = UnloadHeader::create([
            'uuid'          => Str::uuid(),
            'osa_code'      => $osaCodeHeader,
            'warehouse_id'  => $warehouseId,
            'route_id'      => $data['route_id'] ?? null,
            'salesman_id'   => $salesmanId,
            'unload_no'     => $data['unload_no'],
            'unload_date'   => $data['unload_date'] ?? null,
            'unload_time'   => $data['unload_time'] ?? null,
            'sync_date'     => $data['sync_date'] ?? null,
            'sync_time'     => $data['sync_time'] ?? null,
            'latitude'      => $data['latitude'] ?? null,
            'longtitude'    => $data['longitude'] ?? null,
            'salesman_type' => $data['salesman_type'] ?? null,
            'project_type'  => $data['project_type'] ?? null,
            'unload_from'   => $data['unload_from'] ?? 'salesman',
            'load_date'     => $data['load_date'] ?? null,
            'status'        => 1
        ]);
            foreach ($data['details'] as $detail) {
                $itemId = $detail['item_id'];
                $qty    = (float) $detail['qty'];
                $uomId  = $detail['uom'] ?? null;
                if (!$uomId) {
                    throw new \Exception("UOM missing for item_id {$itemId}");
                }
                $itemUom = DB::table('item_uoms')
                    ->where('item_id', $itemId)
                    ->where('uom_id', $uomId)
                    ->first();
                if (!$itemUom || !$itemUom->upc) {
                    throw new \Exception("Invalid UOM/UPC for item {$itemId}");
                }
                $convertedQty = $qty * (float) $itemUom->upc;
                // ?? Fetch stock row
                $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                    ->where('item_id', $itemId)
                    ->first();
                if (!$stock) {
                    throw new \Exception("Stock missing for item {$itemId}");
                }
                $newQty = $stock->qty + $convertedQty;
                $stock->update([
                    'qty'          => $newQty,
                    'updated_user' => $salesmanId
                ]);
                UnloadDetail::create([
                    'uuid'      => Str::uuid(),
                    'osa_code'  => $this->generateOsaCode('SUD'),
                    'header_id' => $header->id,
                    'item_id'   => $itemId,
                    'uom'       => $uomId,
                    'qty'       => $qty,
                    'status'    => 1
                ]);
                LoadDetail::where('item_id', $itemId)
                    ->where('unload_status', 0)
                    ->whereIn('header_id', function ($q) use ($salesmanId) {
                        $q->select('id')
                            ->from('tbl_load_header')
                            ->where('salesman_id', $salesmanId);
                    })
                    ->update(['unload_status' => 1]);
            }
            $pendingDetails = LoadDetail::where('unload_status', 0)
                ->whereIn('header_id', function ($q) use ($salesmanId) {
                    $q->select('id')
                        ->from('tbl_load_header')
                        ->where('salesman_id', $salesmanId);
                })
                ->count();

            if ($pendingDetails === 0) {
                LoadHeader::where('salesman_id', $salesmanId)
                    ->update([
                        'status'       => 1, 
                    ]);
            }

            DB::commit();

            return $header->load('details');
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Unload creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \Exception("Unload creation failed: " . $e->getMessage());
        }
    }


    private function generateUnloadNo($salesmanId)
    {
        $salesman = Salesman::find($salesmanId);
        if (!$salesman || !$salesman->osa_code) {
            throw new \Exception('Salesman code not found for ID ' . $salesmanId);
        }

        $salesmanCode = $salesman->osa_code;

        $lastUnload = UnloadHeader::where('unload_no', 'LIKE', "{$salesmanCode}%")
            ->orderByDesc('id')
            ->first();

        if ($lastUnload && preg_match('/' . preg_quote($salesmanCode, '/') . '(\d+)$/', $lastUnload->unload_no, $matches)) {
            $nextNumber = (int)$matches[1] + 1;
        } else {
            $nextNumber = 1;
        }
        return sprintf('%s%04d', $salesmanCode, $nextNumber);
    }

    private function generateOsaCode(string $prefix): string
    {
        $model = $prefix === 'SUH' ? new UnloadHeader() : new UnloadDetail();

        $lastRecord = $model->where('osa_code', 'LIKE', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;
        if ($lastRecord && preg_match('/(\d+)$/', $lastRecord->osa_code, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        }

        return sprintf('%s%03d', $prefix, $nextNumber);
    }
    
}