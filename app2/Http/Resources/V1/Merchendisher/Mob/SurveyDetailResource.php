<?php

namespace App\Http\Resources\V1\Merchendisher\Mob;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'header_id' => $this->header_id,
            'question_id' => $this->question_id,
            'answer' => $this->answer,
            'created_user' => $this->created_user,
            'updated_user' => $this->updated_user,
            'deleted_user' => $this->deleted_user,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
