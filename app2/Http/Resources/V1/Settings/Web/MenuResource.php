<?php

namespace App\Http\Resources\V1\Settings\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class MenuResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'uuid'          => $this->uuid,
            'osa_code'      => $this->osa_code,
            'name'          => $this->name,
            'icon'          => $this->icon,
            'url'           => $this->url,
            'display_order' => $this->display_order,
            'is_visible'    => $this->is_visible,
            'status'    => $this->status,
        ];
    }
}
