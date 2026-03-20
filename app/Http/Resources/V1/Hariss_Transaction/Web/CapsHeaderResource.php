<?php

namespace App\Http\Resources\V1\Hariss_Transaction\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class CapsHeaderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'      => $this->id,
            'uuid'          => $this->uuid,
            'osa_code'      => $this->osa_code,

            'warehouse_id'       => $this->warehouse_id,
            'warehouse_code'     => $this->warehouse->warehouse_code ?? null,
            'warehouse_name'     => $this->warehouse->warehouse_name ?? null,
            'warehouse_email'    => $this->warehouse->warehouse_email ?? null,

            'driver_id'            => $this->driver_id,
            'driver_code'          => $this->driverinfo->osa_code ?? null,
            'driver_name'          => $this->driverinfo->driver_name ?? null,
            'driver_contact'     => $this->driverinfo->contactno ?? null,

            'truck_no'    => $this->truck_no,
            'contact_no	' => $this->contact_no,
            'claim_no'    => $this->claim_no,
            'claim_date'  => $this->claim_date,
            'claim_amount'  => $this->claim_amount,
            // ==========================================
            // 🚀 APPROVAL (OLD STANDARD FORMAT)
            // ==========================================
            'approval_status'  => $this->approval_status ?? null,
            'current_step'     => $this->current_step ?? null,
            'request_step_id'  => $this->request_step_id ?? null,
            'progress'         => $this->progress ?? null,

            'details' => CapsDetailResource::collection($this->details)
        ];
    }
}
