<?php

namespace App\Http\Resources\V1\Settings\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class DiscountSettingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'discount_amt' => $this->discount_amt,
            'qty' => $this->qty,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
