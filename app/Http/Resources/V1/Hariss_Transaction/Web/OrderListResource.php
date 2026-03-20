<?php

namespace App\Http\Resources\V1\Hariss_Transaction\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'uuid'                => $this->uuid,
            'order_code'          => $this->order_code,

            'customer_id'         => $this->customer_id,
            'customer_code'       => $this->customer->osa_code ?? null,
            'customer_town'       => $this->customer->town ?? null,
            'customer_contact'    => $this->customer->contact_number ?? null,

            'salesman_id'         => $this->salesman_id,
            'salesman_code'       => $this->salesman->osa_code ?? null,
            'salesman_name'       => $this->salesman->name ?? null,

            'country_id'          => $this->country_id,
            'country_code'        => $this->countries->country_code ?? null,
            'country_name'        => $this->countries->country_name ?? null,

            'company_id'          => $this->company_id,
            'company_code'        => $this->company->company_code ?? null,
            'company_name'        => $this->company->company_name ?? null,

            'warehouse_id'        => $this->warehouse_id,
            'warehouse_code'      => $this->warehouse->warehouse_code ?? null,
            'warehouse_name'      => $this->warehouse->warehouse_name ?? null,


            'delivery_date'       => $this->delivery_date?->format('Y-m-d'),
            'comment'             => $this->comment,
            'status'              => $this->status,
            'currency'            => $this->currency,
            'gross_total'         => (float) $this->gross_total,
            'pre_vat'             => (float) $this->pre_vat,
            'discount'            => (float) $this->discount,
            'net_amount'          => (float) $this->net_amount,
            'total'               => (float) $this->total,
            'excise'              => (float) $this->excise,
            'vat'                 => (float) $this->vat,

            'sap_id'              => $this->sap_id,
            'sap_msg'             => $this->sap_msg,
            'doc_type'            => $this->doc_type,

            'po_id'               => $this->po_id,
            'order_date'          => $this->order_date,
            
            'previous_uuid'       => $this->previous_uuid ?? null,
            'next_uuid'           => $this->next_uuid ?? null,

            'created_at'          => $this->created_at,
        ];
    }
}
