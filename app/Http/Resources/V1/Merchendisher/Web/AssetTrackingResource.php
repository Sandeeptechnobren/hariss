<?php

namespace App\Http\Resources\V1\Merchendisher\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class AssetTrackingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'asset_code' => $this->asset_code,
            'image' => $this->image,
            'title' => $this->title,
            'description' => $this->description,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'model_name' => $this->model_name,
            'barcode' => $this->barcode,
            'category' => $this->category,
            'location' => $this->location,
            'area' => $this->area,
            'worker' => $this->worker,
            'additional_worker' => $this->additional_worker,
            'team' => $this->team,
            'vendors' => $this->vendors,
            'customer_id' => $this->customer_id,
            'purchase_date' => $this->purchase_date,
            'placed_in_service' => $this->placed_in_service,
            'purchase_price' => $this->purchase_price,
            'warranty_expiration' => $this->warranty_expiration,
            'residual_price' => $this->residual_price,
            'useful_life' => $this->useful_life,
            'additional_information' => $this->additional_information,
            'uuid' => $this->uuid,
            'created_by' => $this->created_user,
        ];
    }
}