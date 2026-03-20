<?php

namespace App\Http\Resources\V1\Settings\Web;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'osa_code' => $this->osa_code,
            'bank_name' => $this->bank_name,
            'branch' => $this->branch,
            'city' => $this->city,
            'account_number' => $this->account_number,
            'status' => (int) $this->status,
        ];
    }

}