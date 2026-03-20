<?php

namespace App\Http\Resources\V1\Settings\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class AssetManufacturerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'     => $this->id,
            'uuid'   => $this->uuid,
            'osa_code'   => $this->osa_code,
            'name'   => $this->name,
            'asset_type' => $this->assetType ? [
                'id' => $this->assetType?->id,
                'name' => $this->assetType?->name,
                'osa_code' => $this->assetType?->osa_code,
            ] : null,
            'status' => $this->status
        ];
    }
}
