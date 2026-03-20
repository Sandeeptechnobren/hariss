<?php

namespace App\Http\Resources\V1\Agent_Transaction;

use Illuminate\Http\Resources\Json\JsonResource;

class LoadDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'osa_code' => $this->osa_code,
            'header_id' => $this->header_id,
            // 'item_id' => $this->item_id,
            'item' => $this->item ? [
                'id' => $this->item->id,
                'code' => $this->item->code,
                'erp_code' => $this->item->erp_code,
                'name' => $this->item->name
            ] : null,
            'uom' => $this->uom,
            'uom_name' => $this->Uom->name ?? null,
            'item_uom' => $this->itemUOMS ? [
                'id' => $this->itemUOMS->id,
                'name' => $this->itemUOMS->name,
                'uom_type' => $this->itemUOMS->uom_type,
                'upc'      => $this->itemUOMS->upc,
                'uom_id'   => $this->itemUOMS->uom_id,
            ] : null,
            'qty' => $this->qty,
            'status' => $this->status,
        ];
    }
}
