<?php

namespace App\Http\Resources\V1\Assets\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class InstallationOrderHeaderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'       => $this->id,
            'uuid'       => $this->uuid,
            'osa_code'   => $this->osa_code,
            'name'   => $this->name,
            'status'     => $this->status,
            'created_user' => $this->createdBy ? [
                'id' => $this->createdBy->id,
                'firstname' => $this->createdBy->firstname ?? null,
                'lastname' => $this->createdBy->lastname ?? null,
                'username' => $this->createdBy->username ?? null,
            ] : null,
            'updated_user' => $this->updatedBy ? [
                'id' => $this->updatedBy->id,
                'firstname' => $this->updatedBy->firstname ?? null,
                'lastname' => $this->updatedBy->lastname ?? null,
                'username' => $this->createdBy->username ?? null,
            ] : null,
        ];
    }
}
