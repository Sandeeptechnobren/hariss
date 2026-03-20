<?php
namespace App\Http\Resources\V1\Settings\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseStockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'osa_code' => $this->osa_code,
             'warehouse' => $this->warehouse ? [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->warehouse_code ?? null,
                'name' => $this->warehouse->warehouse_name ?? null,
            ] : null,
             'item' => $this->item ? [
                'id' => $this->item->id,
                'code' => $this->item->code ?? null,
                'name' => $this->item->name ?? null,
            ] : null,
            'qty' => $this->qty,
            'status' => $this->status,
        ];
    }
}
