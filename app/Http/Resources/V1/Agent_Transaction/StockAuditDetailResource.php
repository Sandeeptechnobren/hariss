<?php

namespace App\Http\Resources\V1\Agent_Transaction;

use Illuminate\Http\Resources\Json\JsonResource;

class StockAuditDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'item' => $this->item ? [
                'id' => $this->item->id,
                'name' => $this->item->name,
                'erp_code' => $this->item->erp_code,
                'uom' => $this->item->primaryUom ? [
                    'uom_id' => $this->item->primaryUom->uom_id,
                    'name'   => $this->item->primaryUom->name,
                ] : null,
            ] : null,
            // 'uom_id'          => $this->uom_id,
            'warehouse_stock' => (float) $this->warehouse_stock,
            'physical_stock'  => (float) $this->physical_stock,
            'variance'        => (float) $this->variance,
            'remarks'         => $this->remarks,
        ];
    }
}
