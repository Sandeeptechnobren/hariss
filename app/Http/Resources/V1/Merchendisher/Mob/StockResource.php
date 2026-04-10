<?php
namespace App\Http\Resources\V1\Merchendisher\Mob;

use Illuminate\Http\Resources\Json\JsonResource;

class StockResource extends JsonResource
{
public function toArray($request)
{
    return [
        'id'               => $this->id,
        'code'             => $this->code,
        'activity_name'    => $this->activity_name,
        'date_from'        => $this->date_from,
        'date_to'          => $this->date_to,
        'assign_customers' => $this->assign_customers,
        'details' => $this->inventories->map(function ($detail) {
            return [
                'id'      => $detail->id,
                'item_id' => $detail->item_id,
                'item_uom' => $detail->item_uom,
                'capacity' => $detail->capacity,
            ];
        }),
    ];
}
}