<?php

namespace App\Http\Resources\V1\Hariss_Transaction\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class HtReturnDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                    => $this->id,
            'uuid'                  => $this->uuid,
            'header_id'             => $this->header_id,
            'posnr'                 => $this->posnr,
            'item_id'               => $this->item_id,
            'item_code'             => $this->item->code ?? null,
            'item_name'             => $this->item->name ?? null,
            'item_value'            => $this->item_value,
            'vat'                   => $this->vat,
            'uom'                   => $this->uom,
            'uom_name'              => $this->uomdetails->name ?? null,
            // 'uom_code'              => $this->o
            'qty'                   => $this->qty,
            'net'                   => $this->net,
            'total'                 => $this->total,
            'reason'                => $this->return_reason,
            'return_type'           => $this->return_type,
            'batch_no'              => $this->batch_no,
            'actual_expiry_date'    => $this->actual_expiry_date,
            'remark'                => $this->remark,
            'invoice_sap_id'        => $this->invoice_sap_id,
            'return_date'           => $this->return_date,
        ];
    }
}
