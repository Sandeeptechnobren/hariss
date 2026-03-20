<?php

namespace App\Http\Resources\V1\Assets\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class IROHeaderResource extends JsonResource
{
    private function mapStatus($status): string
    {
        return [
            1 => "Waiting for Creating IR",
            2 => "IR Created",
            3 => "Technician Accepted",
            4 => "Technician Rejected",
            5 => "Reschedule by Technician",
            6 => "Request for Close",
            7 => "Closed",
        ][$status] ?? "Unknown";
    }
    public function toArray($request): array
    {
        return [
            'id'       => $this->id,
            'uuid'       => $this->uuid,
            'osa_code'   => $this->osa_code,

            // 'status_id'  => $this->status,
            'status'     => $this->mapStatus($this->status),
            'created_user' => $this->createdBy ? [
                'id' => $this->createdBy->id,
                'username' => $this->createdBy->username ?? null,
            ] : null,
            'updated_user' => $this->updatedBy ? [
                'id' => $this->updatedBy->id,
                'username' => $this->createdBy->username ?? null,
            ] : null,

            'details' => IRODetailResource::collection($this->details),
        ];
    }
}
