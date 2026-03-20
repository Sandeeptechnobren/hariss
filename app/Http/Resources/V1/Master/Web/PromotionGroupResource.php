<?php

namespace App\Http\Resources\V1\Master\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Item;

class PromotionGroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'osa_code' => $this->osa_code,
            'status' => $this->status,
            'item_id' => $this->item,
            'items' => collect(explode(',', $this->item))->map(function($id) {
             $item = Item::find($id);
             if ($item) {
                return [
                    'id' => $item->id,
                    'item_code' => $item->code,
                    'item_name' => $item->name,
                ];
            }
            return null;  
        })->filter()->values(),  
          
        ];
    }
}