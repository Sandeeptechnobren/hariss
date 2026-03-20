<?php

namespace App\Http\Resources\V1\Merchendisher\Web;
use App\Models\CompanyCustomer;
use Illuminate\Http\Resources\Json\JsonResource;

class StockInStorePostResource extends JsonResource
{
   public function toArray($request)
{
    return [
        'id'              => $this->id,
        'stock_id'        => $this->stock_id,
        'stock_name'      => $this->stock ? $this->stock->activity_name : null,
        'stock_code'      => $this->stock ? $this->stock->code : null,
        'date'            => $this->date,
        'salesman_id'     => $this->salesman_id,
        'salesman_name'   => $this->salesman ? $this->salesman->name : null,
        'salesman_code'   => $this->salesman ? $this->salesman->osa_code : null,
        'customer_id'     => $this->customer_id,
        'customer_name'   => $this->customer ? $this->customer->business_name : null,
        'customer_code'   => $this->customer ? $this->customer->osa_code : null,
        'item_id'         => $this->item_id,
        'item_name'       => $this->item ? $this->item->name : null,
        'item_code'       => $this->item ? $this->item->code : null,
        'good_salabale'   => $this->good_salabale,
        'refill_qty'      => $this->refill_qty,
        'out_of_stock'    => $this->out_of_stock,
        'fill_qty'        => $this->fill_qty,
       
    ];
}
}