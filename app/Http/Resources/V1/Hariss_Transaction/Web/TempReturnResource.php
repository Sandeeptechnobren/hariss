<?php

namespace App\Http\Resources\V1\Hariss_Transaction\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class TempReturnResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'uuid'          => $this->uuid,
            'return_code'   => $this->return_code,
            'customer_id'   => $this->customer_id,
            'customer_code'       => $this->customer->osa_code ?? null,
            'customer_name'       => $this->customer->name ?? null,
            'customer_email'      => $this->customer->email ?? null,
            'customer_town'       => $this->customer->town ?? null,
            'customer_street'     => $this->customer->street ?? null,
            'customer_contact'    => $this->customer->contact_no ?? null,
            'vat'           => $this->vat,
            'net'           => $this->net,
            'amount'        => $this->amount,
            'truckname'     => $this->truckname,
            'truckno'       => $this->truckno,
            'contactno'     => $this->contactno,
            'sap_id'        => $this->sap_id,
            'message'       => $this->message,
            'reason'        => $this->reason,
            'reason_type'   => $this->reason_type, 

            'details'       => TempReturnDResource::collection($this->whenLoaded('details')),
        ];
    }
}
