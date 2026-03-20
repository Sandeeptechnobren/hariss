<?php

namespace App\Http\Resources\V1\Assets\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class BulkTransferRequestResource extends JsonResource
{
    private function status($statusId)
    {
        return [
            0  => "Pending",
            1  => "Accepted",
            2  => "Allocated",
            3  => "Rejected",
        ][$statusId] ?? "Unknown";
    }
    public function toArray($request)
    {
        return [
            'id'                 => $this->id,
            'uuid'               => $this->uuid,
            'osa_code'           => $this->osa_code,
            'region' => $this->region ? [
                'id' => $this->region->id,
                'code' => $this->region->region_code ?? null,
                'name' => $this->region->region_name ?? null,
            ] : null,
            'area' => $this->area ? [
                'id' => $this->area->id,
                'code' => $this->area->area_code ?? null,
                'name' => $this->area->area_name ?? null,
            ] : null,
            'warehouse' => $this->warehouse ? [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->warehouse_code ?? null,
                'name' => $this->warehouse->warehouse_name ?? null,
            ] : null,
            'model_number' => $this->model_number ? [
                'id' => $this->model_number->id,
                'code' => $this->model_number->code ?? null,
                'name' => $this->model_number->name ?? null,
            ] : null,
            'requestes_asset'        => $this->requestes_asset,
            'available_stock'    => $this->available_stock,
            'approved_qty'       => $this->approved_qty,
            'allocate_asset'     => $this->allocate_asset,
            'status'     => $this->status($this->status),
            // 'status' => $this->status(),
            // 'status'             => $this->status,
            'comment_reject'     => $this->comment_reject,
            'created_at'         => $this->created_at,
            'approval_status' => $this->approval_status ?? null,
            'current_step'    => $this->current_step ?? null,
            'request_step_id' => $this->request_step_id ?? null,
            'progress'        => $this->progress ?? null,
        ];
    }
}
