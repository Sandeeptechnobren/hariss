<?php

namespace App\Http\Resources\V1\Merchendisher\Mob;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignInformationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
       return [
    'id' => $this->id,
    'uuid' => $this->uuid,
    'code' => $this->code,
    // 'date_time' => $this->date_time,
    'merchandiser_id' => $this->merchandiser_id,
    'merchandiser' => [
        'name' => $this->merchandiser ? $this->merchandiser->name : null,
        'osa_code' => $this->merchandiser ? $this->merchandiser->osa_code : null,
    ],
   'customer_id' => $this->customer_id,
    'customer' => [
        'customer_code' => $this->customer ? $this->customer->osa_code : null,
        'owner_name' => $this->customer ? $this->customer->business_name : null,
    ],

    'feedback' => $this->feedback,
    'images' => $this->images,
    'created_at' => $this->created_at,
];
    }
}