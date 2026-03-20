<?php

namespace App\Http\Resources\V1\Settings\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'contact_number' => $this->contact_number,
            'profile_picture' => $this->profile_picture,
            'status' => $this->status,

            // ✅ Nested role object
            'role' => [
                'id' => $this->role,
                'name' => $this->roleData?->name,
            ],

            // ✅ Rest of the fields
            'company' => $this->company,
            'warehouse' => $this->warehouse,
            'route' => $this->route,
            'salesman' => $this->salesman,
            'region' => $this->region,
            'area' => $this->area,
            'outlet_channel' => $this->outlet_channel,
            'created_by' => $this->created_by,
            'updated_user' => $this->updated_user,
            'created_at' => $this->created_at,
        ];
    }
}
