<?php

namespace App\Http\Resources\V1\Logs;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'menu_id'             => $this->menu_id,
            'sub_menu_id'         => $this->sub_menu_id,
            'user_id'             => $this->user_id,
            'user_name'           => $this->user->username ?? null,
            'mode'                => $this->mode,
            'ip_address'          => $this->ip_address,
            'browser'             => $this->browser,
            'os'                  => $this->os,
            'user_role'           => $this->user_role,
            'previous_data'       => $this->previous_data,
            'current_data'        => $this->current_data,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
