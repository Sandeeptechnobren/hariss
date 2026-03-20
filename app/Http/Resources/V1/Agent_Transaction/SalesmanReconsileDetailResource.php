<?php

namespace App\Http\Resources\V1\Agent_Transaction;

use Illuminate\Http\Resources\Json\JsonResource;

class SalesmanReconsileDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'item_id'     => $this->item_id,
            'item_name'   => optional($this->item)->name,
            'erp_code'    => optional($this->item)->erp_code,
            'load_qty'    => (int) $this->load_qty,
            'unload_qty'  => (int) $this->unload_qty,
            'invoice_qty' => (int) $this->invoice_qty,
        ];
    }
}
