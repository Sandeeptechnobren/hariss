<?php

namespace App\Http\Resources\V1\Assets\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class IRDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'        => $this->id,
            // 'fridge_id' => $this->fridge_id,

            'asset' => $this->fridge ? [
                'id'            => $this->fridge->id,
                'uuid'            => $this->fridge->uuid,
                'osa_code'      => $this->fridge->osa_code ?? null,
                'serial_number' => $this->fridge->serial_number ?? null,
                'assets_type'   => $this->fridge->assets_type ?? null,

                'asset_number' => $this->fridge->assetsCategory ? [
                    'id'   => $this->fridge->assetsCategory->id,
                    'name' => $this->fridge->assetsCategory->name,
                ] : null,

                'acf_number' => $this->fridge->customerUpdate ? [
                    'id'   => $this->fridge->customerUpdate->id,
                    'uuid'   => $this->fridge->customerUpdate->uuid,
                    'osa_code'   => $this->fridge->customerUpdate->osa_code,
                ] : null,

                'model' => $this->fridge->modelNumber ? [
                    'id'   => $this->fridge->modelNumber->id,
                    'name' => $this->fridge->modelNumber->name,
                ] : null,

                'warehouse' => $this->fridge->warehouse ? [
                    'id'   => $this->fridge->warehouse->id,
                    'code' => $this->fridge->warehouse->warehouse_code,
                    'name' => $this->fridge->warehouse->warehouse_name,
                ] : null,

                'status' => $this->formatTechnicianStatus($this->status),

            ] : null,

            'agreement_id' => $this->agreement_id,
            'crf' => $this->crf ? [
                'id'   => $this->crf->id,
                'uuid' => $this->crf->uuid,
                'code' => $this->crf->osa_code,
            ] : null,
            // 'crf_id'       => $this->crf_id,
        ];
    }
    private function formatTechnicianStatus($status): string
    {
        $map = [
            0 => 'Waiting For Installed',
            1 => 'Installed',
            2 => 'Rejected By Customer',
        ];

        return $map[$status] ?? 'Waiting For Installed';
    }
}
