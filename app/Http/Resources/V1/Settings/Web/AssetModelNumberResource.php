<?php

namespace App\Http\Resources\V1\Settings\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class AssetModelNumberResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'code'       => $this->code,
            'name'       => $this->name,
            'status'     => $this->status,
            'asset_type' => $this->assetType ? [
                'id' => $this->assetType?->id,
                'name' => $this->assetType?->name,
                'osa_code' => $this->assetType?->osa_code,
            ] : null,
            'manu_type' => $this->manuType ? [
                'id' => $this->manuType?->id,
                'name' => $this->manuType?->name,
                'osa_code' => $this->manuType?->osa_code,
            ] : null
        ];
    }
}
