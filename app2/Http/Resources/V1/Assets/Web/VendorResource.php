<?php

namespace App\Http\Resources\V1\Assets\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class VendorResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'uuid'            => $this->uuid,
            'code'            => $this->code,
            'name'            => $this->name,
            'email'            => $this->email,
            'contact'         => $this->contact,
            'address'            => $this->address,
            'status'            => $this->status
        ];
    }
}
