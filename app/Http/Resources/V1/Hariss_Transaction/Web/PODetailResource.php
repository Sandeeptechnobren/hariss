<?php

namespace App\Http\Resources\V1\Hariss_Transaction\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PODetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'uuid'          => $this->uuid,
            'item_id'       => $this->item_id,
            'item_code'     => $this->item->code ?? null,
            'item_name'     => $this->item->name ?? null,
            'erp_code'      => $this->item->erp_code ?? null,

            'uom_id'        => $this->uom_id,
            'uom_name'      => $this->uom->name ?? null,

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

            'created_at'    => $this->created_at,
        ];
    }
}
