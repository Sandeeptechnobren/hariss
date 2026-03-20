<?php

namespace App\Http\Resources\V1\Settings\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class TierResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'           => $this->id,
            'uuid'         => $this->uuid,
            'osa_code'     => $this->osa_code,
            'name'         => $this->name,
            'period'       => $this->period,
            'minpurchase'  => $this->minpurchase,
            'maxpurchase'  => $this->maxpurchase,
            'period_category' => $this->period_category,
            'expiray_period'  => $this->expiray_period,
        ];
    }
}