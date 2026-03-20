<?php

namespace App\Http\Resources\V1\Agent_Transaction;

use Illuminate\Http\Resources\Json\JsonResource;

class StockTransferDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'item_id'   => $this->item_id,
            'item_name' => $this->item->name ?? null,
            'erp_code'  => $this->item->erp_code ?? null,
            'qty'       => $this->transfer_qty,
        ];
    }
}
