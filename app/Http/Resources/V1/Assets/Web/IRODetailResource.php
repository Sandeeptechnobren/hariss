<?php

namespace App\Http\Resources\V1\Assets\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class IRODetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'header_id'    => $this->header_id,
            'customer' => $this->customer ? [
                'id' => $this->customer->id,
                'code' => $this->customer->osa_code ?? null,
                'name' => $this->customer->name ?? null,
                'contact_no' => $this->customer->contact_no ?? null,
                'location' => $this->customer->district ?? null,
            ] : null,
            'chillerRequest' => $this->chillerRequest ? [
                'id'   => $this->chillerRequest->id,
                'uuid' => $this->chillerRequest->uuid ?? null,
                'code' => $this->chillerRequest->osa_code,

                'model' => $this->chillerRequest->modelNumber ? [
                    'id'   => $this->chillerRequest->modelNumber->id,
                    'code' => $this->chillerRequest->modelNumber->code,
                    'name' => $this->chillerRequest->modelNumber->name,
                ] : null,

            ] : null,
            'status' => $this->formatTechnicianStatus($this->installation_status),
            'warehouse' => $this->warehouse ? [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->warehouse_code,
                'name' => $this->warehouse->warehouse_name ?? null,
            ] : null,
            'created_at' => $this->created_at,
        ];
    }

    private function formatTechnicianStatus($installation_status): string
    {
        $map = [
            0 => 'Waiting For Installed',
            1 => 'Installed',
            2 => 'Rejected By Customer',
        ];

        return $map[$installation_status] ?? 'Waiting For Installed';
    }
}
