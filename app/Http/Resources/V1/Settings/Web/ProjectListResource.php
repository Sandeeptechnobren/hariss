<?php

namespace App\Http\Resources\V1\Settings\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'osa_code' => $this->osa_code,
            'name' => $this->name,
            // 'salesman_type_id' => $this->salesman_type_id,
            'status' => $this->status,
            // 'salesman_type' => 
            
            'salesmanType' => $this->salesmanType ? [
                'id' => $this->salesmanType->id,
                'code' => $this->salesmanType->salesman_type_code,
                'name' => $this->salesmanType->salesman_type_name,
            ] : null
        ];
    }
}
