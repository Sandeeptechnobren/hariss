<?php

namespace App\Http\Resources\V1\Assets\Web;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Warehouse;
use App\Models\Region;
use App\Models\Area;

class ServiceTerritoryResource extends JsonResource
{
    public function toArray($request)
    {
        // ✅ Convert CSV → array
        $warehouseIds = $this->warehouse_id
            ? array_map('intval', explode(',', $this->warehouse_id))
            : [];

        $regionIds = $this->region_id
            ? array_map('intval', explode(',', $this->region_id))
            : [];

        $areaIds = $this->area_id
            ? array_map('intval', explode(',', $this->area_id))
            : [];

        // ✅ Fetch related data safely
        $warehouses = Warehouse::whereIn('id', $warehouseIds)
            ->get(['id', 'warehouse_code', 'warehouse_name']);

        $regions = Region::whereIn('id', $regionIds)
            ->get(['id', 'region_code', 'region_name']);

        $areas = Area::whereIn('id', $areaIds)
            ->get(['id', 'area_code', 'area_name']);

        return [
            'id'       => $this->id,
            'uuid'     => $this->uuid,
            'osa_code' => $this->osa_code,

            // ✅ Proper expanded data
            'warehouses' => $warehouses->map(fn($w) => [
                'id'   => $w->id,
                'code' => $w->warehouse_code,
                'name' => $w->warehouse_name,
            ]),

            'regions' => $regions->map(fn($r) => [
                'id'   => $r->id,
                'code' => $r->region_code,
                'name' => $r->region_name,
            ]),

            'areas' => $areas->map(fn($a) => [
                'id'   => $a->id,
                'code' => $a->area_code,
                'name' => $a->area_name,
            ]),

            // ✅ Technician is still a real FK
            'technician' => $this->technician ? [
                'id'   => $this->technician->id,
                'code' => $this->technician->osa_code,
                'name' => $this->technician->name,
            ] : null,
        ];
    }
}
