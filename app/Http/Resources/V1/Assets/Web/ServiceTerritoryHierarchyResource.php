<?php

namespace App\Http\Resources\V1\Assets\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceTerritoryHierarchyResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'osa_code'       => $this->osa_code,

            // Technician meta (optional fields)
            'technician' => [
                'id'   => $this->technician_id,
                'name' => optional($this->technician)->name ?? null,
                'code' => optional($this->technician)->code ?? null,
            ],

            // Regions mapped
            'regions' => collect($this->hierarchy)->map(function ($region) {
                return [
                    'region_id'   => $region['region_id'],
                    'region_code' => $region['region_code'] ?? null,
                    'region_name' => $region['region_name'],

                    'areas' => collect($region['area'])->map(function ($area) {
                        return [
                            'area_id'   => $area['area_id'],
                            'area_code' => $area['area_code'] ?? null,
                            'area_name' => $area['area_name'],

                            'warehouses' => collect($area['warehouses'])->map(function ($w) {
                                return [
                                    'warehouse_id'   => $w['warehouse_id'],
                                    'warehouse_code' => $w['warehouse_code'],
                                    'warehouse_name' => $w['warehouse_name'],
                                ];
                            }),
                        ];
                    }),
                ];
            }),
        ];
    }
}
