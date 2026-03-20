<?php

namespace App\Services\V1\Settings\Web;

use App\Models\ItemUOM;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class WarehouseStockService
{
    /**
     * Get all warehouse stocks (including soft-deleted)
     */
    public function list($perPage = 10, array $filters = [])
    {
        try {
            $query = WarehouseStock::with(['warehouse', 'item']);

            foreach ($filters as $field => $value) {
                if (!empty($value)) {
                    if (in_array($field, ['osa_code'])) {
                        $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                    } else {
                        $query->where($field, $value);
                    }
                }
            }
            // dd($query);

            return $query->paginate($perPage);
        } catch (Throwable $e) {
            Log::error("❌ [WarehouseStockService] Error fetching warehouse stocks", [
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception("Unable to fetch warehouse stock list. Please try again later.");
        }
    }

    /**
     * Generate unique osa_code
     */
    public function generateCode(): string
    {
        do {
            $last = WarehouseStock::withTrashed()->latest('id')->first();
            $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $osa_code = 'WHS' . str_pad($next, 3, '0', STR_PAD_LEFT);
        } while (WarehouseStock::withTrashed()->where('osa_code', $osa_code)->exists());

        return $osa_code;
    }

    /**
     * Create a new warehouse stock
     */

    public function create(array $data)
    {
        DB::beginTransaction();

        try {
            $data = array_merge($data, [
                'uuid' => $data['uuid'] ?? Str::uuid()->toString(),
                'osa_code' => $this->generateCode(),
            ]);

            $keepingUom = ItemUOM::where('item_id', $data['item_id'])
                ->where('is_stock_keeping', true)
                ->first();

            $data['qty'] = $keepingUom ? $keepingUom->keeping_quantity : 0;

            $stock = WarehouseStock::create($data);
            DB::commit();
            return $stock;
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("❌ Failed to create Warehouse Stock", [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Unable to create Warehouse Stock. Please try again later.');
        }
    }



    /**
     * Find stock by UUID
     */
    public function getByUuid(string $uuid)
    {
        try {
            return WarehouseStock::where('uuid', $uuid)->firstOrFail();
        } catch (Throwable $e) {
            Log::error("❌ [WarehouseStockService] Error fetching warehouse stock", [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception("Warehouse stock not found.");
        }
    }


    /**
     * Update warehouse stock by UUID
     */
    public function update(string $uuid, array $data)
    {
        DB::beginTransaction();

        try {
            $stock = WarehouseStock::withTrashed()->where('uuid', $uuid)->firstOrFail();

            if (isset($data['item_id'])) {
                $keepingUom = ItemUom::where('item_id', $data['item_id'])
                    ->where('is_stock_keeping', true)
                    ->first();

                $data['qty'] = $keepingUom ? $keepingUom->keeping_quantity : 0;
            }

            $stock->update($data);
            DB::commit();

            return [
                'status' => true,
                'message' => '✅ Warehouse Stock updated successfully.',
                'data' => $stock,
            ];
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("❌ [WarehouseStockService] Failed to update Warehouse Stock", [
                'uuid' => $uuid,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => 'Unable to update Warehouse Stock. Please try again later.',
                'error' => $e->getMessage(),
            ];
        }
    }


    /**
     * Soft delete warehouse stock
     */
    public function softDelete(string $uuid)
    {
        try {
            $stock = WarehouseStock::where('uuid', $uuid)->firstOrFail();
            $stock->delete();

            return [
                'status' => true,
                'code' => 200,
                'message' => 'Warehouse Stock deleted successfully.',
            ];
        } catch (Throwable $e) {
            Log::error("❌ [WarehouseStockService] Error soft deleting warehouse stock", [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => 'Unable to delete warehouse stock. Please try again later.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Restore soft-deleted warehouse stock
     */
    public function restore(string $uuid)
    {
        try {
            $stock = WarehouseStock::onlyTrashed()->where('uuid', $uuid)->firstOrFail();
            $stock->restore();

            return [
                'status' => true,
                'message' => 'Warehouse Stock restored successfully.',
                'data' => $stock,
            ];
        } catch (Throwable $e) {
            Log::error("❌ [WarehouseStockService] Error restoring warehouse stock", [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => 'Unable to restore warehouse stock. Please try again later.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Permanently delete warehouse stock
     */
    public function forceDelete(string $uuid)
    {
        try {
            $stock = WarehouseStock::onlyTrashed()->where('uuid', $uuid)->firstOrFail();
            $stock->forceDelete();

            return [
                'status' => true,
                'message' => 'Warehouse Stock permanently deleted from the system.',
            ];
        } catch (Throwable $e) {
            Log::error("❌ [WarehouseStockService] Error permanently deleting warehouse stock", [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => 'Unable to permanently delete warehouse stock. Please try again later.',
                'error' => $e->getMessage(),
            ];
        }
    }
}
