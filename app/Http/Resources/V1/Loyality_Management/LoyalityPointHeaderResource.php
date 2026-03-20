<?php

namespace App\Http\Resources\V1\Loyality_Management;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoyalityPointHeaderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'uuid'          => $this->uuid,
            'osa_code'      => $this->osa_code,
            'customer_id'   => $this->customer_id,
            'customer_code' => $this->customer->osa_code ?? null,
            'customer_name' => $this->customer->name ?? null,
            'total_earning' => $this->total_earning,
            'total_spend'   => $this->total_spend,
            'total_closing' => $this->total_closing,
            'tier_id'       => $this->tier_id,
            'tier_code'     => $this->tier->osa_code ?? null,
            'tier_name'     => $this->tier->name ?? null,
        ]; 
    } 

}