<?php

namespace App\Http\Resources\V1\Hariss_Transaction\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class CompensationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            // 'header_id'          => $this->header_id,
            'sap_id'             => $this->sap_id,

            'warehouse_code'     => $this->warehouse_code,
            'warehouse_name'     => $this->warehouse_name,

            'invoice_code'       => $this->invoice_code,
            'invoice_date'       => $this->invoice_date,

            'item_category_dll'  => $this->item_category_dll,
            'item_name'          => $this->name,
            'erp_code'           => $this->erp_code,

            // 'total_rejected_qty' => $this->total_rejected_qty,
            // 'total_approved_qty' => $this->total_approved_qty,

            // 'approved_count'     => $this->approved_count,
            // 'pending_count'      => $this->pending_count,

            // 🔥 Added new fields from your SQL query
            'base_uom_vol_calc'        => $this->base_uom_vol_calc,
            'alter_base_uom_vol_calc'  => $this->alter_base_uom_vol_calc,
            'quantity'  => $this->quantity,
            'total_amount'  => $this->total_amount,
        ];
    }
}
