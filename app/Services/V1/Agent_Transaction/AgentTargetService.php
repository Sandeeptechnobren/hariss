<?php

namespace App\Services\V1\Agent_Transaction;

use App\Models\Warehouse;
use App\Models\Item;
use App\Models\Agent_Transaction\AgentTarget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AgentTargetService
{
    public function importData(array $rows): array
    {
        DB::beginTransaction();

        try {
            $insertData = [];
            $errors = [];

            $fixedColumns = ['item_code', 'item_name', 'category', 'month', 'year'];

            $firstRow = $rows[0];

            $warehouseCodes = collect(array_keys($firstRow))->diff($fixedColumns);

            $warehouseCodesUpper = $warehouseCodes->map(fn($code) => strtoupper($code));

            $warehouses = Warehouse::whereIn('warehouse_code', $warehouseCodesUpper)->pluck('id', 'warehouse_code');

            $itemCodes = collect($rows)->pluck('item_code')->filter()->unique();

            $items = Item::whereIn('erp_code', $itemCodes)->pluck('id', 'erp_code');

            foreach ($rows as $row) {

                $itemId = $items[$row['item_code']] ?? null;

                if (!$itemId) {
                    $errors[] = [
                        'item_code' => $row['item_code'],
                        'reason' => 'Invalid item'
                    ];
                    continue;
                }

                foreach ($warehouseCodes as $warehouseCode) {

                    $qty = $row[$warehouseCode] ?? 0;

                    if ($qty <= 0) continue;

                    $warehouseCodeUpper = strtoupper($warehouseCode);

                    $warehouseId = $warehouses[$warehouseCodeUpper] ?? null;

                    if (!$warehouseId) {
                        $errors[] = [
                            'warehouse_code' => $warehouseCodeUpper,
                            'reason' => 'Invalid warehouse'
                        ];
                        continue;
                    }

                    $insertData[] = [
                        'uuid' => Str::uuid(),
                        'warehouse_id' => $warehouseId,
                        'item_id' => $itemId,
                        'target_month' => $row['month'] ?? '0',
                        'target_year' => $row['year'] ?? '0',
                        'qty' => $qty,
                        'created_user' => auth()->id() ?? 1,
                        'updated_user' => auth()->id() ?? 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (!empty($insertData)) {
                AgentTarget::insert($insertData);
            }

            DB::commit();

            return ['inserted' => count($insertData), 'errors' => $errors];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getList($request)
    {
        $query = AgentTarget::with([
            'warehouse:id,warehouse_name,warehouse_code',
            'item:id,name,erp_code'
        ])
            ->select(
                'warehouse_id',
                'target_month',
                'target_year',
                'item_id',
                DB::raw('SUM(qty) as total_qty')
            )
            ->groupBy('warehouse_id', 'target_month', 'target_year', 'item_id')
            ->orderBy('warehouse_id', 'asc');

        // 🔹 Filters
        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->target_month) {
            $query->where('target_month', $request->target_month);
        }

        if ($request->target_year) {
            $query->where('target_year', $request->target_year);
        }

        $data = $query->get();

        // ✅ Structured Response (Grouped)
        $grouped = $data->groupBy(function ($item) {
            return $item->warehouse_id . '-' . $item->target_month . '-' . $item->target_year;
        })->map(function ($records) {

            $first = $records->first();

            return [
                'warehouse' => [
                    'id' => $first->warehouse?->id,
                    'name' => $first->warehouse?->warehouse_name,
                    'code' => $first->warehouse?->warehouse_code,
                ],
                'target_month' => $first->target_month,
                'target_year' => $first->target_year,
                'qty' => (float) $records->sum('total_qty'),

                // 'items' => $records->map(function ($item) {
                //     return [
                //         'id' => $item->item?->id,
                //         'name' => $item->item?->name,
                //         'code' => $item->item?->erp_code,
                //         'total_qty' => (float) $item->total_qty,
                //     ];
                // })->values()
            ];
        })->values();

        return $grouped;
    }

    public function getSingle($request)
    {
        $query = AgentTarget::with([
            'warehouse:id,warehouse_name,warehouse_code',
            'item:id,name,erp_code'
        ])
            ->select(
                'warehouse_id',
                'target_month',
                'target_year',
                'item_id',
                DB::raw('SUM(qty) as total_qty')
            )
            ->where('warehouse_id', $request->warehouse_id)
            ->where('target_month', $request->target_month)
            ->where('target_year', $request->target_year)
            ->groupBy('warehouse_id', 'target_month', 'target_year', 'item_id');

        $data = $query->get();

        if ($data->isEmpty()) {
            throw new \Exception('No data found');
        }

        $first = $data->first();

        return [
            'warehouse' => [
                'id' => $first->warehouse?->id,
                'name' => $first->warehouse?->warehouse_name,
                'code' => $first->warehouse?->warehouse_code,
            ],
            'target_month' => $first->target_month,
            'target_year' => $first->target_year,
            'qty' => (float) $data->sum('total_qty'),
            'items' => $data->map(function ($item) {
                return [
                    'id' => $item->item?->id,
                    'name' => $item->item?->name,
                    'code' => $item->item?->erp_code,
                    'qty' => (float) $item->total_qty,
                ];
            })->values()
        ];
    }


    public function globalFilter($filter)
    {
        $warehouseIds = explode(',', $filter['warehouse_id']);
        $query = AgentTarget::with([
            'warehouse:id,warehouse_name,warehouse_code',
            'item:id,name,erp_code'
        ])
            ->select(
                'warehouse_id',
                'target_month',
                'target_year',
                'item_id',
                DB::raw('SUM(qty) as total_qty')
            )
            ->whereIn('warehouse_id', $warehouseIds) // ✅ change here
            ->where('target_month', $filter['target_month'])
            ->where('target_year', $filter['target_year'])
            ->groupBy('warehouse_id', 'target_month', 'target_year', 'item_id');
        $data = $query->get();

        if ($data->isEmpty()) {
            throw new \Exception('No data found');
        }

        $first = $data->first();

        return [
            'warehouse' => [
                'id' => $first->warehouse?->id,
                'name' => $first->warehouse?->warehouse_name,
                'code' => $first->warehouse?->warehouse_code,
            ],
            'target_month' => $first->target_month,
            'target_year' => $first->target_year,
            'qty' => (float) $data->sum('total_qty'),
        ];
    }



    public function updateTarget($request)
    {
        DB::beginTransaction();

        try {
            $warehouseId = $request->warehouse_id;
            $month = $request->month;
            $year = $request->year;

            // ✅ Normalize single / multiple
            if (empty($request->item)) {
                throw new \Exception('No items provided');
            }

            $items = is_array($request->item) && isset($request->item[0])
                ? $request->item
                : [$request->item];

            foreach ($items as $item) {

                if (!isset($item['item_id']) || !isset($item['item_qty'])) {
                    throw new \Exception('Invalid item structure');
                }

                $itemId = $item['item_id'];

                if (!Item::where('id', $itemId)->exists()) {
                    throw new \Exception("Invalid item_id: {$itemId}");
                }

                AgentTarget::updateOrCreate(
                    [
                        'warehouse_id' => $warehouseId,
                        'target_month' => $month,
                        'target_year' => $year,
                        'item_id' => $itemId,
                    ],
                    [
                        'qty' => $item['item_qty'],
                        'updated_user' => auth()->id(),
                    ]
                );
            }

            // ✅ SAME RESPONSE AS getSingle
            $data = AgentTarget::with([
                'warehouse:id,warehouse_name,warehouse_code',
                'item:id,name,erp_code'
            ])
                ->select(
                    'warehouse_id',
                    'target_month',
                    'target_year',
                    'item_id',
                    DB::raw('SUM(qty) as total_qty')
                )
                ->where('warehouse_id', $warehouseId)
                ->where('target_month', $month)
                ->where('target_year', $year)
                ->groupBy('warehouse_id', 'target_month', 'target_year', 'item_id')
                ->get();

            if ($data->isEmpty()) {
                throw new \Exception('No data found');
            }

            $first = $data->first();

            DB::commit();

            return [
                'warehouse' => [
                    'id' => $first->warehouse?->id,
                    'name' => $first->warehouse?->warehouse_name,
                    'code' => $first->warehouse?->warehouse_code,
                ],
                'target_month' => $first->target_month,
                'target_year' => $first->target_year,
                'qty' => (float) $data->sum('total_qty'),
                'items' => $data->map(function ($item) {
                    return [
                        'id' => $item->item?->id,
                        'name' => $item->item?->name,
                        'code' => $item->item?->erp_code,
                        'qty' => (float) $item->total_qty,
                    ];
                })->values()
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
