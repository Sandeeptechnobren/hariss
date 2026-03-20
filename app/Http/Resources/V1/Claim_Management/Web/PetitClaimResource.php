<?php

namespace App\Http\Resources\V1\Claim_Management\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class PetitClaimResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "uuid" => $this->uuid,
            "osa_code" => $this->osa_code,
            "claim_type" => $this->claim_type,
            // "warehouse_id" => $this->warehouse_id,
            'warehouse' => $this->warehouse ? [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->warehouse_code,
                'name' => $this->warehouse->warehouse_name
            ] : null,
            "petit_name" => $this->petit_name,
            "fuel_amount" => $this->fuel_amount,
            "rent_amount" => $this->rent_amount,
            "agent_amount" => $this->agent_amount,
            "month_range" => $this->month_range,
            "year" => $this->year,
            "claim_file" => $this->claim_file,
           'status' => $this->formatTechnicianStatus($this->status),
            'approval_status' => $this->approval_status ?? null,
            'current_step'    => $this->current_step ?? null,
            'request_step_id' => $this->request_step_id ?? null,
            'progress'        => $this->progress ?? null,
            // user action response
            'is_user_action_done' => $this->is_user_action_done ?? false,
            'user_action' => $this->user_action ?? null,
            'user_action_step' => $this->user_action_step ?? null
            // "created_at" => $this->created_at,
        ];
    }

    private function formatTechnicianStatus($status): string
    {
        $map = [
            1 => 'Waiting for Commercial Manager',
            2 => 'Rejected By Commercial Manager',
            3 => 'Waiting For Customer Care',
            4 => 'Rejected By Customer Care',
            5 => 'Completed',
        ];

        return $map[$status] ?? 'Unknown';
    }
}
