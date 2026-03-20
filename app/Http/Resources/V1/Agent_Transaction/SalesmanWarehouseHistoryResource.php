<?php

namespace App\Http\Resources\V1\Agent_Transaction;

use Illuminate\Http\Resources\Json\JsonResource;

class SalesmanWarehouseHistoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'salesman' => $this->whenLoaded('salesman', function () {
                return [
                    'id'   => $this->salesman->id,
                    'code' => $this->salesman->osa_code,
                    'name' => $this->salesman->name,
                    'role_id'   => optional($this->salesman->subtype)->id,
                    'role_code' => optional($this->salesman->subtype)->osa_code,
                    'role_name' => optional($this->salesman->subtype)->name,
                ];
            }),

            'warehouse' => $this->warehouse ? [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->warehouse_code,
                'name' => $this->warehouse->warehouse_name
            ] : null,
            'manager' => $this->manager ? [
                'id' => $this->manager->id,
                'code' => $this->manager->osa_code,
                'name' => $this->manager->name
            ] : null,
            'route' => $this->route ? [
                'id' => $this->route->id,
                'code' => $this->route->route_code,
                'name' => $this->route->route_name
            ] : null,
            'requested_time' => $this->requested_time,
            'requested_date' => $this->requested_date,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
