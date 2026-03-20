<?php

namespace App\Http\Resources\V1\Merchendisher\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class SurveyQuestionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'survey_question_code' => $this->survey_question_code,
            'survey_id' => $this->survey_id,
            'question' => $this->question,
            'question_type' => $this->question_type,
            'question_based_selected' => $this->question_based_selected,
            'created_user' => $this->created_user,
            'updated_user' => $this->updated_user,
            'deleted_user' => $this->deleted_user,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            
        ];
    }
}