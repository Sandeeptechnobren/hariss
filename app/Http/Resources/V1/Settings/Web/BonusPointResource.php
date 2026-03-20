<?php

namespace App\Http\Resources\V1\Settings\Web;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BonusPointResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'osa_code' => $this->osa_code,
            'item_id' => $this->item_id,
            'item_code' => $this->item->code,
            'item_name' => $this->item->name,
            'volume' => $this->volume,
            'bonus_points' => $this->bonus_points,
            'reward_basis' => $this->reward_basis,
        ];
    }

}