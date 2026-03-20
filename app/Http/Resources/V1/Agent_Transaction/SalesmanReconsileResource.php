<?php

namespace App\Http\Resources\V1\Agent_Transaction;

use Illuminate\Http\Resources\Json\JsonResource;

class SalesmanReconsileResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'item_id'     => $this['item_id'] ?? null,
            'item_name'   => $this['item']['name'] ?? null,
            'erp_code'    => $this['item']['erp_code'] ?? null,
            'load_qty'    => (int) ($this['load_qty'] ?? 0),
            'unload_qty'  => (int) ($this['unload_qty'] ?? 0),
            'invoice_qty' => (int) ($this['invoice_qty'] ?? 0),
        ];
    }
}
