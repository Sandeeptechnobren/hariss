<?php

namespace App\Http\Resources\V1\Hariss_Transaction\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'uuid'          => $this->uuid,
            'header_id'     => $this->header_id,
            'item_id'       => $this->item_id,
            'erp_code'     => $this->item->erp_code ?? null,
            'item_code'     => $this->item->code ?? null,
            'item_name'     => $this->item->name ?? null,
            'uom_id'        => $this->uom_id,
            'uom_name'      => $this->uoms->name ?? null,
            'item_price'    => (float) $this->item_price,
            'quantity'      => (float) $this->quantity,
            'discount'      => (float) $this->discount,
            'gross_total'   => (float) $this->gross_total,
            'promotion'     => (bool) $this->promotion,
            'net'           => (float) $this->net,
            'excise'        => (float) $this->excise,
            'pre_vat'       => (float) $this->pre_vat,
            'vat'           => (float) $this->vat,
            'total'         => (float) $this->total,
        ];
    }
}
