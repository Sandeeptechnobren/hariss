<?php

namespace App\Http\Resources\V1\Merchendisher\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class SurveyResource extends JsonResource
{
    public function toArray($request): array
    {
      return [
                'id'           => $this->id,
                'survey_code'  => $this->survey_code,
                'uuid'         => $this->uuid,
                'survey_name'  => $this->survey_name,
                'start_date'   => $this->start_date->toDateString(),
                'end_date'     => $this->end_date->toDateString(),
                'status'       => $this->status_label, 
                'status_value' => $this->status,      
                'created_user' => $this->created_user,
                'updated_user' => $this->updated_user,
                'deleted_user' => $this->deleted_user,
                'created_at'   => $this->created_at,
                'updated_at'   => $this->updated_at,
                'deleted_at'   => $this->deleted_at,
     ];
    }
}