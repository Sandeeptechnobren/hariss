<?php

namespace App\Http\Resources\V1\Agent_Transaction;

use Illuminate\Http\Resources\Json\JsonResource;

class StockAuditResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'uuid'         => $this->uuid,
            'osa_code'         => $this->osa_code,
            // 'warehouse_id' => $this->warehouse_id,
            'warehouse' => $this->warehouse ? [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->warehouse_code,
                'name' => $this->warehouse->warehouse_name,
            ] : null,
            'auditer_name' => $this->auditer_name,
            'case_otc_invoice' => $this->case_otc_invoice,
            'otc_invoice' => $this->otc_invoice,
            'negative_balance_date' => $this->negative_balance_date,
            'created_at' => $this->created_at,

            'details' => StockAuditDetailResource::collection($this->whenLoaded('details'))
        ];
    }
}
