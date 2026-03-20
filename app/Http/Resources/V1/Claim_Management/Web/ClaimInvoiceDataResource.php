<?php

namespace App\Http\Resources\V1\Claim_Management\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class ClaimInvoiceDataResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            // "phtrange"            => $this->phtrange,
            "total_rejected_qty"  => $this->total_rejected_qty,
            "total_approved_qty"  => $this->total_approved_qty,
            "approved_count"      => $this->approved_count,
            "pending_count"       => $this->pending_count,
            "price"               => $this->price,

            "warehouse_id"        => $this->warehouse_id,
            "warehouse_code"      => $this->warehouse_code,
            "warehouse_name"      => $this->warehouse_name,

            // "item_name"           => $this->item_name,
        ];
    }
}
