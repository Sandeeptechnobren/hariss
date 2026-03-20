<?php

namespace App\Http\Resources\V1\Assets\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class IRHeaderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'osa_code' => $this->osa_code,
            // 'iro_id' => $this->iro_id,
            'iro' => $this->iroHeader ? [
                'id'   => $this->iroHeader->id,
                'code' => $this->iroHeader->osa_code,
                'warehouses' => $this->iroHeader->details
                    ->pluck('warehouse')
                    ->filter()
                    ->map->only(['id', 'warehouse_code', 'warehouse_name'])
                    ->values(),
            ] : null,
            'salesman' => $this->salesman ? [
                'id'       => $this->salesman->id,
                'code'     => $this->salesman->osa_code ?? null,
                'name'     => $this->salesman->name ?? null,
            ] : null,
            'schedule_date' => $this->schedule_date,
            'status' => $this->formatTechnicianStatus($this->status),
            'details' => IRDetailResource::collection($this->details)
        ];
    }
    private function formatTechnicianStatus($status): string
    {
        $map = [
            1 => 'Waiting for confirmation from Technician',
            2 => 'Technician Accepted',
            3 => 'Technician Rejected',
            4 => 'Technician Reschedule',
            5 => 'Request for Close',
            6 => 'Closed',
        ];

        return $map[$status] ?? 'Unknown';
    }
}
