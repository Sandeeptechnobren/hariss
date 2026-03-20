<?php
namespace App\Http\Resources\V1\Master\Web;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'country_code'  => $this->country_code,
            'country_name'  => $this->country_name,
            'currency'      => $this->currency,
            'status'        => $this->status,
            'created_user'  => $this->created_user,
            'updated_user'  => $this->updated_user,
            'created_date'  => $this->created_date,
            'updated_date'  => $this->updated_date,
            'companies'     => $this->whenLoaded('companies'),
            'region'        => $this->whenLoaded('region'),
        ];
    }
}
