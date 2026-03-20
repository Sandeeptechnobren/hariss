<?php

namespace App\Http\Resources\V1\Agent_Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'uuid'           => $this->uuid,
            'collection_no'  => $this->collection_no,
            'invoice_id'     => $this->invoice_id,
            'invoice_code'   => $this->invoice->invoice_code ?? null,
            'customer_id'    => $this->customer_id,
            'customer_name'  => $this->customer->name ?? null,
            'customer_code'  => $this->customer->osa_code ?? null,
            'salesman_id'    => $this->salesman_id,
            'salesman_name'  => $this->salesman->name ?? null,
            'salesman_code'  => $this->salesman->osa_code ?? null,
            'route_id'       => $this->route_id,
            'route_code'     => $this->route->route_code ?? null,
            'route_name'     => $this->route->route_name ?? null,
            'warehouse_id'   => $this->warehouse_id,
            'warehouse_code' => $this->warehouse->warehouse_code ?? null,
            'warehouse_name' => $this->warehouse->warehouse_name ?? null,
            'amount'         => $this->amount,
            'outstanding'    => $this->outstanding,
            'status'         => $this->status,
            ];
    }
}