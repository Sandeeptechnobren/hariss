<?php

namespace App\Http\Resources\V1\Settings\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'           => $this->id,
            'uuid'         => $this->uuid,
            'code'         => $this->code,
            'name'         => $this->name,
            'created_at'   => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at'   => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
            'deleted_at'   => $this->deleted_at ? $this->deleted_at->toDateTimeString() : null,
            'create_user'  => $this->create_user,
            'update_user'  => $this->update_user,
            'deleted_user' => $this->deleted_user,
        ];
    }
}
