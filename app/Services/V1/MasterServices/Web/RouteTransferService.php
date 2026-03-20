<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\AgentCustomer;
use App\Models\Route;
use App\Models\RouteTransfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Support\Facades\File;
use App\Models\Warehouse;
use Illuminate\Support\Str;

class RouteTransferService
{
    // public function transferRoute(array $data): array
    // {
    //     DB::beginTransaction();

    //     $routeLogger = Log::build([
    //         'driver' => 'single',
    //         'path'   => storage_path('logs/route_transfer.log'),
    //         'level'  => 'info',
    //     ]);
    //     try {
    //         $route = Route::select('id', 'warehouse_id')
    //             ->where('id', $data['new_route_id'])
    //             ->first();

    //         if (!$route) {
    //             Log::error('[ROUTE TRANSFER FAILED - INVALID ROUTE]', [
    //                 'new_route_id' => $data['new_route_id'],
    //                 'performed_by' => auth()->id(),
    //                 'datetime'     => now()->toDateTimeString(),
    //             ]);

    //             throw new \Exception('Invalid new route selected.');
    //         }

    //         $customers = AgentCustomer::where('route_id', $data['old_route_id'])
    //             ->whereNull('deleted_at')
    //             ->get();

    //         if ($customers->isEmpty()) {
    //             Log::warning('[ROUTE TRANSFER FAILED - NO DATA]', [
    //                 'old_route_id' => $data['old_route_id'],
    //                 'new_route_id' => $data['new_route_id'],
    //                 'performed_by' => auth()->id(),
    //                 'datetime'     => now()->toDateTimeString(),
    //             ]);

    //             throw new \Exception('No customers found on the selected route.');
    //         }

    //         $oldWarehouses = $customers
    //             ->pluck('warehouse')
    //             ->unique()
    //             ->values()
    //             ->toArray();

    //         $routeLogger->info('[ROUTE TRANSFER START]', [
    //             'old_route_id'      => $data['old_route_id'],
    //             'new_route_id'      => $data['new_route_id'],
    //             'old_warehouse_ids' => $oldWarehouses,
    //             'new_warehouse_id'  => $route->warehouse_id,
    //             'total_customers'   => $customers->count(),
    //             'performed_by'      => auth()->id(),
    //             'datetime'          => now()->toDateTimeString(),
    //         ]);

    //         foreach ($customers as $customer) {

    //             $routeLogger->info('[ROUTE TRANSFER CUSTOMER]', [
    //                 'customer_id'    => $customer->id,
    //                 'customer_uuid'  => $customer->uuid,
    //                 'customer_name'  => $customer->name,
    //                 'old_route_id'   => $data['old_route_id'],
    //                 'new_route_id'   => $data['new_route_id'],
    //                 'old_warehouse'  => $customer->warehouse,
    //                 'new_warehouse'  => $route->warehouse_id,
    //                 'datetime'       => now()->toDateTimeString(),
    //             ]);

    //             $customer->route_id     = $data['new_route_id'];
    //             $customer->warehouse    = $route->warehouse_id;
    //             $customer->updated_user = auth()->id();
    //             $customer->save();
    //         }

    //         DB::commit();

    //         $routeLogger->info('[ROUTE TRANSFER COMPLETED]', [
    //             'old_route_id'      => $data['old_route_id'],
    //             'new_route_id'      => $data['new_route_id'],
    //             'old_warehouse_ids' => $oldWarehouses,
    //             'new_warehouse_id'  => $route->warehouse_id,
    //             'customers_updated' => $customers->count(),
    //             'performed_by'      => auth()->id(),
    //             'datetime'          => now()->toDateTimeString(),
    //         ]);

    //         return [
    //             'old_route_id'      => $data['old_route_id'],
    //             'new_route_id'      => $data['new_route_id'],
    //             'new_warehouse_id'  => $route->warehouse_id,
    //             'customers_updated' => $customers->count(),
    //         ];
    //     } catch (Throwable $e) {
    //         DB::rollBack();

    //         Log::error('[ROUTE TRANSFER FAILED - EXCEPTION]', [
    //             'old_route_id' => $data['old_route_id'] ?? null,
    //             'new_route_id' => $data['new_route_id'] ?? null,
    //             'error'        => $e->getMessage(),
    //             'performed_by' => auth()->id(),
    //             'datetime'     => now()->toDateTimeString(),
    //         ]);

    //         throw new \Exception(
    //             'Route transfer failed. ' . $e->getMessage()
    //         );
    //     }
    // }

    public function transferRoute(array $data): array
    {
        return DB::transaction(function () use ($data) {

            $userId = auth()->id();

            // 🔹 Validate new route
            $route = Route::select('id', 'warehouse_id')
                ->find($data['new_route_id']);

            if (! $route) {
                throw new \Exception('Invalid new route selected.');
            }

            // 🔹 Fetch customers on old route
            $customers = AgentCustomer::query()
                ->where('route_id', $data['old_route_id'])
                ->whereNull('deleted_at')
                ->get(['id', 'warehouse']);

            if ($customers->isEmpty()) {
                throw new \Exception('No customers found on old route.');
            }

            $customerIds = $customers->pluck('id')->toArray();

            // 🔹 Old warehouse (assumed same for all customers)
            $oldWarehouseId = $customers
                ->pluck('warehouse')
                ->unique()
                ->first();

            // 🔹 Create route transfer record
            $transfer = RouteTransfer::create([
                'uuid'       => Str::uuid(),
                'old_route_id'     => $data['old_route_id'],
                'new_route_id'     => $data['new_route_id'],
                'old_warehouse_id' => $oldWarehouseId,
                'new_warehouse_id' => $route->warehouse_id,
                'customer_ids'     => implode(',', $customerIds),

                'performed_by'     => $userId,
                'created_user'     => $userId,
                'transferred_at'   => now(),
            ]);

            // 🔹 Update customers
            AgentCustomer::whereIn('id', $customerIds)->update([
                'route_id'     => $data['new_route_id'],
                'warehouse'    => $route->warehouse_id,
                'updated_user' => $userId,
            ]);

            return [
                'uuid'        => $transfer->uuid,
                'customers_updated' => count($customerIds),
            ];
        });
    }


    // public function getHistory(array $filters = []): array
    // {
    //     $logPath = storage_path('logs/route_transfer.log');

    //     if (!File::exists($logPath)) {
    //         return [];
    //     }

    //     $rows = [];
    //     $routeIds = [];
    //     $warehouseIds = [];
    //     $customerIds = [];

    //     // 🔹 PASS 1: Parse logs & collect IDs
    //     foreach (File::lines($logPath) as $line) {

    //         preg_match(
    //             '/\[(ROUTE TRANSFER (START|CUSTOMER|COMPLETED))\].*(\{.*\})/',
    //             $line,
    //             $matches
    //         );

    //         if (!isset($matches[2], $matches[3])) {
    //             continue;
    //         }

    //         $payload = json_decode($matches[3], true);
    //         if (!is_array($payload)) {
    //             continue;
    //         }

    //         $rows[] = [
    //             'type'    => $matches[2],
    //             'payload' => $payload,
    //         ];

    //         // Collect IDs
    //         $routeIds[] = $payload['old_route_id'] ?? null;
    //         $routeIds[] = $payload['new_route_id'] ?? null;

    //         if (!empty($payload['old_warehouse_ids'])) {
    //             $warehouseIds = array_merge($warehouseIds, $payload['old_warehouse_ids']);
    //         }

    //         if (!empty($payload['new_warehouse_id'])) {
    //             $warehouseIds[] = $payload['new_warehouse_id'];
    //         }

    //         if (!empty($payload['customer_id'])) {
    //             $customerIds[] = $payload['customer_id'];
    //         }
    //     }

    //     // 🔹 Remove nulls & duplicates
    //     $routeIds     = array_unique(array_filter($routeIds));
    //     $warehouseIds = array_unique(array_filter($warehouseIds));
    //     $customerIds  = array_unique(array_filter($customerIds));

    //     // 🔹 PASS 2: Bulk fetch (ONLY 3 queries)
    //     $routes = Route::whereIn('id', $routeIds)
    //         ->get(['id', 'route_code', 'route_name'])
    //         ->keyBy('id');

    //     $warehouses = Warehouse::whereIn('id', $warehouseIds)
    //         ->get(['id', 'warehouse_code', 'warehouse_name'])
    //         ->keyBy('id');

    //     $customers = AgentCustomer::whereIn('id', $customerIds)
    //         ->get(['id', 'name', 'osa_code'])
    //         ->keyBy('id');

    //     // 🔹 PASS 3: Build response
    //     $transfers = [];

    //     foreach ($rows as $row) {

    //         $type    = $row['type'];
    //         $payload = $row['payload'];

    //         $key = ($payload['old_route_id'] ?? '')
    //             . '_' . ($payload['new_route_id'] ?? '')
    //             . '_' . ($payload['datetime'] ?? '');

    //         if (!isset($transfers[$key])) {

    //             $oldRoute = $routes[$payload['old_route_id']] ?? null;
    //             $newRoute = $routes[$payload['new_route_id']] ?? null;

    //             $transfers[$key] = [
    //                 'old_route' => $oldRoute ? [
    //                     'id'   => $oldRoute->id,
    //                     'code' => $oldRoute->route_code,
    //                     'name' => $oldRoute->route_name,
    //                 ] : null,

    //                 'new_route' => $newRoute ? [
    //                     'id'   => $newRoute->id,
    //                     'code' => $newRoute->route_code,
    //                     'name' => $newRoute->route_name,
    //                 ] : null,

    //                 'warehouse'         => [],
    //                 'customers'         => [],
    //                 'customers_updated' => 0,
    //                 'performed_by'      => $payload['performed_by'] ?? null,
    //                 'transferred_at'    => $payload['datetime'] ?? null,
    //             ];
    //         }

    //         if ($type === 'START') {

    //             $oldWarehouses = collect($payload['old_warehouse_ids'] ?? [])
    //                 ->map(fn($id) => $warehouses[$id] ?? null)
    //                 ->filter()
    //                 ->map(fn($w) => [
    //                     'id'   => $w->id,
    //                     'code' => $w->warehouse_code,
    //                     'name' => $w->warehouse_name,
    //                 ])->values();

    //             $newWarehouse = null;
    //             if (!empty($payload['new_warehouse_id']) && isset($warehouses[$payload['new_warehouse_id']])) {
    //                 $w = $warehouses[$payload['new_warehouse_id']];
    //                 $newWarehouse = [
    //                     'id'   => $w->id,
    //                     'code' => $w->warehouse_code,
    //                     'name' => $w->warehouse_name,
    //                 ];
    //             }

    //             $transfers[$key]['warehouse'] = [
    //                 'old_warehouses' => $oldWarehouses,
    //                 'new_warehouse'  => $newWarehouse,
    //             ];
    //         }

    //         if ($type === 'CUSTOMER') {

    //             $customer = $customers[$payload['customer_id']] ?? null;

    //             $transfers[$key]['customers'][] = [
    //                 'customer_id'   => $payload['customer_id'],
    //                 'customer_uuid' => $payload['customer_uuid'] ?? null,
    //                 'customer_name' => $payload['customer_name'] ?? ($customer->name ?? null),
    //                 'customer_code' => $payload['osa_code'] ?? ($customer->osa_code ?? null),
    //             ];
    //         }

    //         if ($type === 'COMPLETED') {
    //             $transfers[$key]['customers_updated'] = $payload['customers_updated'] ?? 0;
    //         }
    //     }

    //     $history = array_values($transfers);

    //     // 🔹 Filters (unchanged)
    //     $history = array_filter($history, function ($item) use ($filters) {

    //         if (!empty($filters['route_id'])) {
    //             if (
    //                 ($item['old_route']['id'] ?? null) != $filters['route_id'] &&
    //                 ($item['new_route']['id'] ?? null) != $filters['route_id']
    //             ) {
    //                 return false;
    //             }
    //         }

    //         if (
    //             !empty($filters['from_date']) &&
    //             strtotime($item['transferred_at']) < strtotime($filters['from_date'])
    //         ) {
    //             return false;
    //         }

    //         if (
    //             !empty($filters['to_date']) &&
    //             strtotime($item['transferred_at']) > strtotime($filters['to_date'] . ' 23:59:59')
    //         ) {
    //             return false;
    //         }

    //         return true;
    //     });

    //     return array_reverse(array_values($history));
    // }

    public function getHistory()
    {
        return RouteTransfer::query()
            ->whereNull('deleted_at')
            ->latest('transferred_at')
            ->get()
            ->map(function ($transfer) {

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
