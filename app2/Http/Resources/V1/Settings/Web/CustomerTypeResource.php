<?php

namespace App\Http\Resources\V1\Settings\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerTypeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'code'         => $this->code,
            'name'         => $this->name,
            'status'       => $this->status,
            'created_user' => $this->created_user,
            'updated_user' => $this->updated_user,
            'created_date' => $this->created_at,
            'updated_date' => $this->updated_at,
        ];
    }
}
