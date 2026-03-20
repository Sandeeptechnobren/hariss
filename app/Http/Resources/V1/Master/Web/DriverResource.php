<?php

namespace App\Http\Resources\V1\Master\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'uuid'          => $this->uuid,
            'osa_code'   => $this->osa_code,
            'driver_name'  => $this->driver_name,
            'contactno'   => $this->contactno,
            'vehicle' => $this->vehicle_id,
            'vehicle_code' => $this->vehicle->vehicle_code,
            'vehicle_chesis_no'  => $this->vehicle->vehicle_chesis_no,
            'device_id'  => $this->device_id,
            'device_name' => $this->device->device_name,
        ];
    }
}
