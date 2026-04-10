<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\AgentCustomer;
use App\Models\Route;
use App\Models\AddChiller;
use App\Models\PromotionHeader;
use App\Models\PricingHeader;
use App\Models\RouteTransfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Support\Facades\File;
use App\Models\Warehouse;
use Illuminate\Support\Str;

class RouteTransferService
{
    public function transferRoute(array $data): array
    {
        DB::beginTransaction();
        try {
            $userId = auth()->id();
            $route = Route::select('id', 'warehouse_id')->find($data['new_route_id']);
            if (!$route) {
                throw new \Exception('Invalid new route selected.');
            }
            $customers = AgentCustomer::query()->where('route_id', $data['old_route_id'])->whereNull('deleted_at')->get(['id', 'warehouse']);
            if ($customers->isEmpty()) {
                throw new \Exception('No customers found on old route.');
            }
            $customerIds = $customers->pluck('id')->toArray();
            $oldWarehouseId = $customers->pluck('warehouse')->unique()->first();
            $transfer = RouteTransfer::create([
                'uuid'             => Str::uuid(),
                'old_route_id'     => $data['old_route_id'],
                'new_route_id'     => $data['new_route_id'],
                'old_warehouse_id' => $oldWarehouseId,
                'new_warehouse_id' => $route->warehouse_id,
                'customer_ids'     => implode(',', $customerIds),
                'performed_by'     => $userId,
                'created_user'     => $userId,
                'transferred_at'   => now(),
            ]);
            AgentCustomer::whereIn('id', $customerIds)->update([
                'route_id'     => $data['new_route_id'],
                'warehouse'    => $route->warehouse_id,
                'updated_user' => $userId,
            ]);
            AddChiller::where('warehouse_id', $oldWarehouseId)->update([
                'warehouse_id' => $route->warehouse_id
            ]);
            $pricings = PricingHeader::whereNotNull('warehouse_id')->get();
            foreach ($pricings as $pricing) {
                $warehouseIds = array_map('trim', explode(',', $pricing->warehouse_id));
                if (in_array($oldWarehouseId, $warehouseIds)) {
                    $updatedIds = array_map(function ($id) use ($oldWarehouseId, $route) {
                        return (string)$id === (string)$oldWarehouseId ? (string)$route->warehouse_id : $id;
                    }, $warehouseIds);
                    $pricing->update([
                        'warehouse_id' => implode(',', array_unique($updatedIds))
                    ]);
                }
            }
            $promotions = PromotionHeader::where('key_location', 'Warehouse')->whereNotNull('location')->where('location', 'LIKE', "%$oldWarehouseId%")->get();
            foreach ($promotions as $promotion) {
                $warehouseIds = array_map('trim', explode(',', $promotion->location));
                if (in_array($oldWarehouseId, $warehouseIds)) {
                    $updatedIds = array_map(function ($id) use ($oldWarehouseId, $route) {
                        return (string)$id === (string)$oldWarehouseId ? (string)$route->warehouse_id : $id;
                    }, $warehouseIds);
                    $promotion->update([
                        'location' => implode(',', array_unique($updatedIds))
                    ]);
                }
            }
            DB::commit();
            return [
                'status' => true,
                'message' => 'Route transferred successfully',
                'data' => [
                    'uuid' => $transfer->uuid,
                    'customers_updated' => count($customerIds),
                ]
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Route transfer failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getHistory($perPage = 10)
    {
        return RouteTransfer::query()
            ->whereNull('deleted_at')
            ->latest('transferred_at')
            ->paginate($perPage) // ✅ pagination added
            ->through(function ($transfer) {

                $customerIds = array_map(
                    'intval',
                    explode(',', $transfer->customer_ids)
                );

                return [
                    'uuid' => $transfer->uuid,

                    'old_route' => Route::select('id', 'route_code', 'route_name')
                        ->find($transfer->old_route_id),

                    'new_route' => Route::select('id', 'route_code', 'route_name')
                        ->find($transfer->new_route_id),

                    'old_warehouse' => Warehouse::select('id', 'warehouse_code', 'warehouse_name')
                        ->find($transfer->old_warehouse_id),

                    'new_warehouse' => Warehouse::select('id', 'warehouse_code', 'warehouse_name')
                        ->find($transfer->new_warehouse_id),

                    'customers' => AgentCustomer::whereIn('id', $customerIds)
                        ->select('id', 'name', 'osa_code')
                        ->get(),

                    'customers_moved' => count($customerIds),
                    'performed_by'    => $transfer->performed_by,
                    'transferred_at'  => $transfer->transferred_at,
                ];
            });
    }
}
