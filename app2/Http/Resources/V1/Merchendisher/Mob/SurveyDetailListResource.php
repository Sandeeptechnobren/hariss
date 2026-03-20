<?php

namespace App\Http\Resources\V1\Merchendisher\Mob;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyDetailListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'header_id'   => $this->header_id,
            'question_id' => $this->question_id,
            'question'    => $this->question?->question,
            'answer'      => $this->answer,
        ];
    }
}
