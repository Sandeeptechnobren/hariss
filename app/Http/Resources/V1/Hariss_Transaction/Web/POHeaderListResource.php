<?php

namespace App\Http\Resources\V1\PO_Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class POHeaderListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'uuid'                => $this->uuid,
            'order_code'          => $this->order_code,

            'customer_id'         => $this->customer_id,
            'customer_code'       => $this->customer->osa_code ?? null,
            'customer_name'       => $this->customer->name ?? null,
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
            'company_email'       => $this->company->email ?? null,
            'company_contact' => $this->company->primary_contact ?? null,
            'company_address' => $this->company->address ?? null,


            'warehouse_id'        => $this->warehouse_id,
            'warehouse_code'      => $this->warehouse->warehouse_code ?? null,
            'warehouse_name'      => $this->warehouse->warehouse_name ?? null,
            'warehouse_contact' => $this->warehouse->warehouse_manager_contact ?? null,
            'warehouse_town' => $this->warehouse->town_village ?? null,



            'delivery_date'       => $this->delivery_date?->format('Y-m-d'),
            'comment'             => $this->comment,
            'status'              => $this->status,

            'gross_total'         => (float) $this->gross_total,
            'pre_vat'             => (float) $this->pre_vat,
            'discount'            => (float) $this->discount,
            'net'                 => (float) $this->net,
            'total'               => (float) $this->total,
            'excise'              => (float) $this->excise,
            'vat'                 => (float) $this->vat,

            'sap_id'              => $this->sap_id,
            'sap_msg'             => $this->sap_msg,

            'po_id'               => $this->po_id,

            // DETAILS (just like your OrderHeaderResource)
            'details'             => PODetailResource::collection($this->whenLoaded('details')),

            // Prev/Next
            'previous_uuid'       => $this->previous_uuid ?? null,
            'next_uuid'           => $this->next_uuid ?? null,

            'created_at'          => $this->created_at,
        ];
    }
}
