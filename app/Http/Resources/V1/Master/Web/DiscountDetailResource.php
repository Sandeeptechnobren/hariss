<?php

namespace App\Http\Resources\V1\Master\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'item_id'     => $this->item_id,
            'category_id' => $this->category_id,
            'uom'         => $this->uom,
            'percentage'  => $this->percentage,
            'amount'      => $this->amount,
        ];
    }
}
