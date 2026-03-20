<?php

namespace App\Http\Resources\V1\Agent_Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnloadDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
     public function toArray($request)
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'osa_code' => $this->osa_code,
            'item' => $this->item ? [
                'id' => $this->item->id,
                'code' => $this->item->code,
                'erp_code' => $this->item->erp_code,
                'name' => $this->item->name
            ] : null,
            'uom' => $this->uom,
            'uom_name' => $this->uoms->name ?? null,
            'item_uoms' => $this->itemUOMS ? [
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
