<?php

namespace App\Http\Resources\V1\Master\Mob;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
        'id' => $this->id,
        'uuid' => $this->uuid,
        'osa_code' => $this->osa_code,
        'file_name'=> $this->file_name,
        'created_user' => $this->created_user,
        'updated_user' => $this->updated_user,
        'deleted_user' =>$this->deleted_user,
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,
    ];
    }
}