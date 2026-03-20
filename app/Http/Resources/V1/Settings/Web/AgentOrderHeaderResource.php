<?php

namespace App\Http\Resources\V1\Settings\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentOrderHeaderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agent_id' => $this->agent_id,
            'sap_order_id' => $this->sap_order_id,
            'order_number' => $this->order_number,
            'order_date' => $this->order_date,
            'delivery_date' => $this->delivery_date,
            'payment_term' => $this->payment_term,
            'price_list_id' => $this->price_list_id,
            'currency' => $this->currency,
            'gross_total' => $this->gross_total,
            'excise' => $this->excise,
            'vat' => $this->vat,
            'pre_vat' => $this->pre_vat,
            'discount' => $this->discount,
            'net_total' => $this->net_total,
            'total_amount' => $this->total_amount,
            'order_status' => $this->order_status,
            'reject_reason' => $this->reject_reason,
            'order_comment' => $this->order_comment,
            'sales_backoffice_comment' => $this->sales_backoffice_comment,
            'signature_img' => $this->signature_img,
            'sap_return_message' => $this->sap_return_message,
            'is_delivered' => (bool) $this->is_delivered,
            'status' => (bool) $this->status,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
