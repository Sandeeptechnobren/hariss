<?php

namespace App\Http\Resources\V1\Merchendisher\Web;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V1\Merchendisher\Web\SurveyQuestionResource;

class SurveyShowResource extends JsonResource
{
    public function toArray($request): array
    {
      return [
                'id'           => $this->id,
                'survey_code'  => $this->survey_code,
                'uuid'         => $this->uuid,
                'survey_type' => $this->survey_type,
                'survey_name'  => $this->survey_name,
                'start_date'   => $this->start_date->toDateString(),
                'end_date'     => $this->end_date->toDateString(),
                'status'       => $this->status_label, 
                'status_value' => $this->status,      
                'merchandishers'=> $this->merchandishers,
                'customers'     => $this->customers,
                'assets'        => $this->assets,
                'questions' => SurveyQuestionResource::collection(
                 $this->whenLoaded('questions') ),   
     ];
    }
}