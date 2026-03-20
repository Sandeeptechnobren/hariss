<?php

namespace App\Http\Resources\V1\Master\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class RouteResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'route_code'  => $this->route_code,
            'route_name'  => $this->route_name,
            // 'route_type'  => $this->route_type,
            'vehicle' => $this->vehicle ? [
                'id' => $this->vehicle->id,
                'code' => $this->vehicle->vehicle_code
            ] : null,
            'warehouse' => $this->warehouse ? [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->warehouse_name,
                'name' => $this->warehouse->warehouse_code
            ] : null,
            'getrouteType' => $this->getrouteType ? [
                'id' => $this->getrouteType->id,
                'code' => $this->getrouteType->route_type_code,
                'name' => $this->getrouteType->route_type_name
            ] : null,
            // 'vehicle'     => $this->vehicle,
            'status'      => $this->status,
            // 'warehouse'   => $this->warehouse,
            // 'route_Type' => $this->getrouteType,
            'createdBy'   => $this->createdBy,
            'updatedBy'   => $this->updatedBy,
        ];
    }
}
