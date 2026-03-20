<?php

namespace App\Http\Resources\V1\Settings\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request|mixed  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id'       => $this->id,
            'uuid'       => $this->uuid,
            'osa_code'   => $this->osa_code,
            'name'   => $this->name
        ];
    }
}
