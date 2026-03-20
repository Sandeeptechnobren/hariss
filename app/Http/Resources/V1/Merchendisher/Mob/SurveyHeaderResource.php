<?php

namespace App\Http\Resources\V1\Merchendisher\Mob;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V1\Merchendisher\Mob\SurveyDetailResource;

class SurveyHeaderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,

            'date' => $this->date,
            'answerer_name' => $this->answerer_name,
            'address' => $this->address,
            'phone' => $this->phone,

            'merchandiser' => [
                'id' => $this->merchandiser_id,
                'details' => $this->whenLoaded('merchandiser', fn () => [
                    'id' => $this->merchandiser->id,
                    'name' => $this->merchandiser->name,
                ]),
            ],

            'survey' => [
                'id' => $this->survey_id,
                'details' => $this->whenLoaded('survey', fn () => [
                    'id'   => $this->survey->id,
                    'name' => $this->survey->survey_name,
                ]),
            ],

            // 👇 Survey details based on header_id
            'details' => SurveyDetailResource::collection(
                $this->whenLoaded('surveyDetails')
            ),
        ];
    }
}
