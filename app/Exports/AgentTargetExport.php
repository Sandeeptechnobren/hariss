<?php

namespace App\Exports;

use App\Models\Warehouse;

use App\Models\Agent_Transaction\AgentTarget;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AgentTargetExport implements WithMultipleSheets
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        $sheets = [];
        $sheets[] = new AgentTargetWarehouseSheet(null, $this->filters);
        if (!empty($this->filters['warehouse_id'])) {
            $sheets[] = new AgentTargetWarehouseSheet(
                $this->filters['warehouse_id'],
                $this->filters
            );
            return $sheets;
        }
        $warehouseIds = AgentTarget::select('warehouse_id')
            ->distinct()
            ->pluck('warehouse_id')
            ->toArray();
        $warehouses = Warehouse::whereIn('id', $warehouseIds)
            ->orderBy('warehouse_code', 'asc')
            ->pluck('id');
        foreach ($warehouses as $warehouseId) {
            $sheets[] = new AgentTargetWarehouseSheet($warehouseId, $this->filters);
        }
        return $sheets;
    }
}
