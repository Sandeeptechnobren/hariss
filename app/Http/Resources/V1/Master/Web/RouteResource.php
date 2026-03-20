<?php

namespace App\Http\Resources\V1\Master\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class RouteResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'uuid'          => $this->uuid,
            'route_code'  => $this->route_code,
            'route_name'  => $this->route_name,
            // 'route_type'  => $this->route_type,
            'vehicle' => $this->vehicle ? [
                'id' => $this->vehicle->id,
                'code' => $this->vehicle->vehicle_code,
                'capacity' => $this->vehicle->capacity
            ] : null,
            'warehouse' => $this->warehouse ? [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->warehouse_code,
                'name' => $this->warehouse->warehouse_name
            ] : null,
            'getrouteType' => $this->getrouteType ? [
                'id' => $this->getrouteType->id,
                'code' => $this->getrouteType->route_type_code,
                'name' => $this->getrouteType->route_type_name
            ] : null,
            'customer_count' => $this->customers_count ?? 0,
            // 'customers' => $this->whenLoaded('customers', function () {
            //     return $this->customers->map(function ($customer) {
            //         return [
            //             'id'   => $customer->id,
            //             'code' => $customer->osa_code,
            //             'name' => $customer->name,
            //             'route_id' => $customer->route_id,
            //         ];
            //     });
            // }),
            // 'vehicle'     => $this->vehicle,
            'status'      => $this->status,
            // 'warehouse'   => $this->warehouse,
            // 'route_Type' => $this->getrouteType,
            'createdBy'   => $this->createdBy,
            'updatedBy'   => $this->updatedBy,
        ];
    }
}
