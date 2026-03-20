<?php

namespace App\Http\Resources\V1\Merchendisher\Mob;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyHeaderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
return [
    'id'            => $this->id,
    'merchandiser'  => [
        'id' => $this->merchandiser_id, // raw column
        // 'details' => $this->whenLoaded('merchandiser', fn() => [
        //     'id'   => $this->merchandiser->id,
        //     'name' => $this->merchandiser->name,
        // ]),
    ],
    'survey'        => [
        'id' => $this->survey_id, // raw column
        'details' => $this->whenLoaded('survey', fn() => [
            'name' => $this->survey->survey_name,
        ]),
    ],
    'date'          => $this->date,
    'answerer_name' => $this->answerer_name,
    'address'       => $this->address,
    'phone'         => $this->phone,
    'uuid'          => $this->uuid,
    'created_user'  => $this->created_user,
    'updated_user'  => $this->updated_user,
    'deleted_user'  => $this->deleted_user,
    'created_at'    => $this->created_at,
    'updated_at'    => $this->updated_at,
    'deleted_at'    => $this->deleted_at,
];
    }
}