<?php

namespace App\Http\Resources\V1\Master\Mob;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoadDetailResource extends JsonResource
{
     public function toArray($request)
    {
        return [
            'osa_code' => $this->osa_code,
            'id' => $this-> item_id,
            'name'=> $this -> item->name ?? null,
            'uom'     => $this->uom,
            'qty'     => $this->qty,
            'price'   => $this->price,
        ];
    }
}