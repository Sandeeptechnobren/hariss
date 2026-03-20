<?php

namespace App\Http\Resources\V1\Settings\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class AssetBrandingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'osa_code'      => $this->osa_code,
            'name'          => $this->name,
            'status'        => $this->status
        ];
    }
}
