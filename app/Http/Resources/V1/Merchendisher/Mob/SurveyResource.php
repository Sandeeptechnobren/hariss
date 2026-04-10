<?php

namespace App\Http\Resources\V1\Merchendisher\Mob;

use Illuminate\Http\Resources\Json\JsonResource;

class SurveyResource extends JsonResource
{
    public function toArray($request): array
    {
        // Ensure 'questions' relationship is loaded
        $this->loadMissing('questions');

        return [
            'id'             => $this->id,
            'survey_code'    => $this->survey_code,
            'survey_type'    => $this->survey_type,
            'survey_name'    => $this->survey_name,
            'start_date'     => $this->start_date->toDateString(),
            'end_date'       => $this->end_date->toDateString(),
            'status'         => $this->status_label, 
            'status_value'   => $this->status,      
            'merchandishers' => $this->merchandisher_id ? explode(',', $this->merchandisher_id) : [],
            'customers'      => $this->customer_id ? explode(',', $this->customer_id) : [],
            'assets'         => $this->asset_id ? explode(',', $this->asset_id) : [],
            'questions'      => $this->questions->map(function ($question) {
                return [
                    'id'            => $question->id,
                    'survey_id'     => $question->survey_id,
                    'code'          => $question->survey_question_code,
                    'question_type' => $question->question_type,
                    'question'      => $question->question,
                    'options'       => $question->question_based_selected,
                ];
            }),
        ];
    }
}