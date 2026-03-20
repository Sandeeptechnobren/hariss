<?php

namespace App\Http\Resources\V1\Master\Mob;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoadHeaderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'           => $this->id,
            'uuid'         => $this->uuid,
            'osa_code'     => $this->osa_code,
            'warehouse'    => [
                'id' => $this-> warehouse_id,
                'name'=> $this -> warehouse->warehouse_name ?? null,
            ],
            'route'    => [
                'id' => $this-> route_id,
                'name'=> $this -> route->route_name ?? null,
            ],
            'salesman' => [
                'id' => $this-> salesman_id,
                'name'=> $this -> salesman->name ?? null,
            ],
            'is_confirmed' => $this->is_confirmed,
            'details'      => LoadDetailResource::collection($this->details),
        ];
    }
}