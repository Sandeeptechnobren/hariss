<?php

namespace App\Http\Resources\V1\Hariss_Transaction\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class TempReturnHResource extends JsonResource
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
            'customer_town'       => $this->customer->town ?? null,
            // 'company_id'          => $this->company_id,
            // 'company_code'        => $this->company->company_code ?? null,
            // 'company_name'        => $this->company->company_name ?? null,

            // 'warehouse_id'        => $this->warehouse_id,
            // 'warehouse_code'      => $this->warehouse->warehouse_code ?? null,
            // 'warehouse_name'      => $this->warehouse->warehouse_name ?? null,

            'vat'           => $this->vat,
            'net'           => $this->net,
            'amount'        => $this->amount,
            'truckname'     => $this->truckname,
            'truckno'       => $this->truckno,
            'contactno'     => $this->contactno,
            'sap_id'        => $this->sap_id,
            'message'       => $this->message,
            'reason'        => $this->return_reason	,
            'reason_type'   => $this->return_type,
            'parent_id'     => $this->parent_id,
        ];
    }
}
