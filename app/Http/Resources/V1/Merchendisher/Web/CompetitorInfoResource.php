<?php

namespace App\Http\Resources\V1\Merchendisher\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class CompetitorInfoResource extends JsonResource
{
    public function toArray($request)
    {
       return [
    'id'              => $this->id,
    'uuid'            => $this->uuid,
    'code'            => $this->code,
    'company_name'    => $this->company_name,
    'brand'           => $this->brand,
    'merchendiser_id' => $this->merchendiser_id,
    'merchendiser_info' => $this->merchandiser ? [
        'name'     => $this->merchandiser->name,
        'osa_code' => $this->merchandiser->osa_code,
    ] : null,
    'item_name'       => $this->item_name,
    'price'           => $this->price,
    'promotion'       => $this->promotion,
    'notes'           => $this->notes,
    'image'           => $this->image,
    'created_by'      => $this->created_user,
    'created_at'      => $this->created_at,
];
    }
}