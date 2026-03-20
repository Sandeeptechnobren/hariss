<?php

namespace App\Http\Resources\V1\Settings\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class SubMenuResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'uuid'          => $this->uuid,
            'osa_code'      => $this->osa_code,
            'name'          => $this->name,
            'menu' => $this->menu ? [
                'id' => $this->menu->id,
                'code' => $this->menu->osa_code ?? null,
                'name' => $this->menu->name ?? null,
            ] : null,
            'parent' => $this->parent ? [
                'id' => $this->parent->id,
                'code' => $this->parent->osa_code ?? null,
                'name' => $this->parent->name ?? null,
            ] : null,
            'url'           => $this->url,
            'display_order' => $this->display_order,
            'action_type'   => $this->action_type,
            'is_visible'    => $this->is_visible,
        ];
    }
}
