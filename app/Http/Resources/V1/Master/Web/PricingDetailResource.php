<?php

namespace App\Http\Resources\V1\Master\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class PricingDetailResource extends JsonResource
{
    // public function toArray($request): array
    // {
    //     return [
    //         'id' => $this->id,
    //         'uuid' => $this->uuid,
    //         'osa_code' => $this->osa_code,
    //         'header_id' => $this->header_id,
    //         'item_id' => $this->item_id,
    //         'name' => $this->name,
    //         'buom_ctn_price' => $this->buom_ctn_price,
    //         'auom_pc_price' => $this->auom_pc_price,
    //         'status' => $this->status,
    //         'created_user' => $this->created_user,
    //         'updated_user' => $this->updated_user,
    //         'created_at' => $this->created_at,
    //         'updated_at' => $this->updated_at,
    //         'deleted_at' => $this->deleted_at,

    //         'header' => $this->header ? [
    //             'id' => $this->header->id,
    //             'uuid' => $this->header->uuid,
    //             'code' => $this->header->code,
    //             'name' => $this->header->name,
    //             'description' => $this->header->description,
    //             'start_date' => $this->header->start_date,
    //             'end_date' => $this->header->end_date,
    //             'apply_on' => $this->header->apply_on,
    //             'warehouse_id' => $this->header->warehouse_id,
    //             'item_type' => $this->header->item_type,
    //             'status' => $this->header->status,
    //         ] : null,

    //         'item' => $this->item ? [
    //             'id' => $this->item->id,
    //             'code' => $this->item->code,
    //             'name' => $this->item->name,
    //             'description' => $this->item->description,
    //             'price' => $this->item->price,
    //             'status' => $this->item->status,
    //         ] : null,
    //     ];
    // }

    public function toArray($request): array
    {
        // All UOMs (array form)
        $uoms = optional($this->item)
            ->itemUoms
            ->map(function ($itemUom) {
                return [
                    'id'   => optional($itemUom->uom)->id,
                    'name' => optional($itemUom->uom)->name,
                    'code' => optional($itemUom->uom)->osa_code,
                ];
            }) ?? collect([]);

        return [
            'id'             => $this->id,
            'uuid'           => $this->uuid,
            'name'           => $this->name,

            'item_id'        => $this->item_id,
            'item_name'      => optional($this->item)->name,
            'item_code'      => optional($this->item)->erp_code,

            // 🔥 UOM ARRAY OBJECT
            'uom'           => $uoms->toArray(),

            'buom_ctn_price' => $this->buom_ctn_price,
            'auom_pc_price'  => $this->auom_pc_price,
            'status'         => $this->status,
        ];
    }
}
