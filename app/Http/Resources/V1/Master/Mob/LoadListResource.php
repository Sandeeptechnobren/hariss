<?php

namespace App\Http\Resources\V1\Master\Mob;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoadListResource extends JsonResource
{
     public function toArray($request)
    {
        return [
            'id'           => $this->id,
            'osa_code'     => $this->osa_code,
            'uuid'          => $this->uuid,
            'warehouse' => [
                'id' => $this-> warehouse_id,
                'name'=> $this -> warehouse->warehouse_name ?? null,
            ],
            'route' => [
                'id' => $this-> route_id,
                'name'=> $this -> route->route_name ?? null,
            ],
            'salesman' => [
                'id' => $this-> salesman_id,
                'name'=> $this -> salesman->name ?? null,
            ],
            // 'salesman_sign' => $this->salesman_sign,
            'accept_time'   => $this->accept_time,
            // 'latitude'      => $this->latitude,
            // 'longitude'     => $this->longtitude, 
            // 'load_id'       => $this->load_id,
            // 'sync_time'     => $this->sync_time,
            'is_confirmed'  => $this->is_confirmed,
            'created_at'     => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'details'      => LoadDetailResource::collection($this->details),
        ];
    }
}