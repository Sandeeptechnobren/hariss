<?php

namespace App\Http\Resources\V1\Hariss_Transaction\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryHeaderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'uuid'          => $this->uuid,
            'delivery_code' => $this->delivery_code,
            'customer_id'   => $this->customer_id,
            'customer_code'       => $this->customer->osa_code ?? null,
            'customer_name'       => $this->customer->business_name ?? null,
            'customer_town'       => $this->customer->town ?? null,
            'customer_district'     => $this->customer->district ?? null,
            'customer_contact'    => $this->customer->contact_number ?? null,
            'country_id'          => $this->country_id,
            'country_code'        => $this->country->country_code ?? null,
            'country_name'        => $this->country->country_name ?? null,
            'currency'            => $this->country->currency ?? null,
            'salesman_id'         => $this->salesman_id,
            'salesman_code'       => $this->salesman->osa_code ?? null,
            'salesman_name'       => $this->salesman->name ?? null,
            'company_id'          => $this->company_id,
            'company_code'        => $this->company->company_code ?? null,
            'company_name'        => $this->company->company_name ?? null,
            'warehouse_id'        => $this->warehouse_id,
            'warehouse_code'      => $this->warehouse->warehouse_code ?? null,
            'warehouse_name'      => $this->warehouse->warehouse_name ?? null,
            'gross_total'   => $this->gross_total,
            'discount'      => $this->discount,
            'vat'           => $this->vat,
            'pre_vat'       => $this->pre_vat,
            'net'           => $this->net,
            'excise'        => $this->excise,
            'total'         => $this->total,
            'delivery_date' => $this->delivery_date,
            'comment'       => $this->comment,
            'status'        => $this->status,
            'po_id'         => $this->po_id,
            'po_code'       => $this->poorder->order_code ?? null,
            'order_id'      => $this->order_id,
            'order_code'    => $this->order->order_code ?? null,

            'details' => DeliveryDetailResource::collection($this->details)
        ];
    }
}
