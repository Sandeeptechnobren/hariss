<?php

namespace App\Http\Resources\V1\Hariss_Transaction\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class HtReturnHeaderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'uuid'          => $this->uuid,
            'return_code'   => $this->return_code,
            'customer_id'   => $this->customer_id,
            'customer_code'       => $this->customer->osa_code ?? null,
            'customer_name'       => $this->customer->business_name ?? null,
            'customer_email'      => $this->customer->email ?? null,
            'customer_town'       => $this->customer->town ?? null,
            'customer_street'     => $this->customer->street ?? null,
            'customer_contact'    => $this->customer->contact_no ?? null,
            'company_id'          => $this->company_id,
            'company_code'        => $this->company->company_code ?? null,
            'company_name'        => $this->company->company_name ?? null,

            'warehouse_id'        => $this->warehouse_id,
            'warehouse_code'      => $this->warehouse->warehouse_code ?? null,
            'warehouse_name'      => $this->warehouse->warehouse_name ?? null,

            'driver_id'           => $this->driver_id,
            'driver_name'         => $this->driver->driver_name ?? null,
            'driver_code'         => $this->driver->osa_code ?? null,
            'driver_contactno'    => $this->driver->contactno ?? null,
 
            'vat'           => $this->vat,
            'net'           => $this->net,
            'amount'        => $this->total,
            'turnman'       => $this->turnman,
            'truck_no'      => $this->truck_no,
            'contact_no'    => $this->contact_no,
            'sap_id'        => $this->sap_id,
            'message'       => $this->message,
            'status'        => $this->status,

            'details'       => HtReturnDetailResource::collection($this->whenLoaded('details')),
        ];
    }
}
