<?php

namespace App\Http\Resources\V1\Settings\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class SalesmanTypeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                      => $this->id,
            'salesman_type_code'      => $this->salesman_type_code,
            'salesman_type_name'      => $this->salesman_type_name,
            'salesman_type_status'    => $this->salesman_type_status,
            'createdBy'               => $this->createdBy,
            'updatedBy'               => $this->updatedBy, 
            'created_date'   => $this->salesman_created_date,
            'updated_date'   => $this->salesman_updated_date,
        ];
    }
}
