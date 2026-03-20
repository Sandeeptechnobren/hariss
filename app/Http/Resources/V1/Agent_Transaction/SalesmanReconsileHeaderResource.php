<?php

namespace App\Http\Resources\V1\Agent_Transaction;

use App\Http\Resources\V1\Agent_Transaction\SalesmanReconsileDetailResource;

use Illuminate\Http\Resources\Json\JsonResource;

class SalesmanReconsileHeaderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'uuid'           => $this->uuid,
            'osa_code'       => $this->osa_code,

            'warehouse_id'   => $this->warehouse_id,
            'warehouse_name'   => optional($this->warehouse)->warehouse_name,
            'warehouse_code'    => optional($this->warehouse)->warehouse_code,
            'salesman_id'    => $this->salesman_id,
            'salesman_name'   => optional($this->salesman)->name,
            'salesman_code'    => optional($this->salesman)->osa_code,
            'cash_amount'        => $this->cash_amount,
            'credit_amount'        => $this->credit_amount,
            'grand_total_amount'   => (float) $this->total_amount,

            'reconsile_date' => $this->reconsile_date,

            // 🔴 THIS WAS MISSING
            'items' => SalesmanReconsileDetailResource::collection(
                $this->details
            ),
        ];
    }
}
