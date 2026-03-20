<?php

namespace App\Http\Resources\V1\Agent_Transaction\Mob;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CapsCollectionDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'uuid'              => $this->uuid,
            'header_id'         => $this->header_id,
            'header_code'       => $this->header->code ?? null,

            'item_id'           => $this->item_id,
            'item_code'         => $this->item->code ?? null,
            'item_name'         => $this->item->name ?? null,

            'uom_id'            => $this->uom_id,
            'uom_name'          => $this->uom2->name ?? null,

            'collected_quantity'=> $this->collected_quantity,
            'status'            => $this->status
        ];
    }
}