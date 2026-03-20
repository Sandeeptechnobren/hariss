<?php

namespace App\Http\Resources\V1\Hariss_Transaction\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceHeaderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'uuid'            => $this->uuid,
            'invoice_code'    => $this->invoice_code,
            'customer_id'     => $this->customer_id,
            'customer_code'       => $this->customer->osa_code ?? null,
            'customer_name'       => $this->customer->business_name ?? null,
            'customer_email'      => $this->customer->email ?? null,
            'customer_town'       => $this->customer->town ?? null,
            'customer_street'     => $this->customer->street ?? null,
            'customer_contact'    => $this->customer->contact_no ?? null,
            
            'salesman_id'         => $this->salesman_id,
            'salesman_code'       => $this->salesman->osa_code ?? null,
            'salesman_name'       => $this->salesman->name ?? null,

            'company_id'          => $this->company_id,
            'company_code'        => $this->company->company_code ?? null,
            'company_name'        => $this->company->company_name ?? null,

            'warehouse_id'        => $this->warehouse_id,
            'warehouse_code'      => $this->warehouse->warehouse_code ?? null,
            'warehouse_name'      => $this->warehouse->warehouse_name ?? null,

            'currency_name'       => $this->currency_name,
            'order_number'        => $this->order_number,
            'delivery_number'     => $this->delivery_number,
            'latitude'            => $this->latitude,
            'longitude'           => $this->longitude,
            'purchaser_name'      => $this->purchaser_name,
            'purchaser_contact'   => $this->purchaser_contact,
            'invoice_date'    => $this->invoice_date, 
            'invoice_time'    => $this->invoice_time,
            'net'             => $this->net,
            'vat'             => $this->vat,
            'excise'          => $this->excise,
            'total'           => $this->total,
            'delivery_id'   => $this->delivery->delivery_code ?? null,
            'po_id'         => $this->po_id,
            'po_code'       => $this->poorder->order_code ?? null,
            'order_id'      => $this->order_id,
            'order_code'    => $this->order->order_code ?? null,
            'status'          => $this->status,
            'previous_uuid'   => $this->previous_uuid,
            'next_uuid'       => $this->next_uuid,


            'details' => InvoiceDetailResource::collection($this->details)
        ];
    }
}
