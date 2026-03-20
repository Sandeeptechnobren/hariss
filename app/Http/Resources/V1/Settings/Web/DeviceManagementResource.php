<?php

namespace App\Http\Resources\V1\Settings\Web;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceManagementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'osa_code' => $this->osa_code,
            'manufacturing_id' => $this->manufacturing_id,
            'device_name' => $this->device_name,
            'modelno' => $this->modelno,
            'IMEI_1' => $this->IMEI_1,
            'IMEI_2' =>$this->IMEI_2,
        ];
    }

}