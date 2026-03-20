<?php

namespace App\Http\Resources\V1\Master\Mob;

use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                     => $this->id,
            'erp_code'               => $this->erp_code,
            'code'                   => $this->code,
            'name'                   => $this->name,
            'description'            => $this->description,
            'image'                  => $this->image,
            'category_id'            => $this->category_id,
            'sub_category_id'        => $this->sub_category_id,
            'shelf_life'             => $this->shelf_life,
            'status'                 => $this->status,
            'brand'                  => $this->brand,
            'item_weight'            => $this->item_weight,
            'volume'                 => $this->volume,
            'is_promotional'         => $this->is_promotional,
            'is_taxable'             => $this->is_taxable,
            'has_excies'             => $this->has_excies,
            'commodity_goods_code'   => $this->commodity_goods_code,
            'excise_duty_code'       => $this->excise_duty_code,
            'customer_code'          => $this->customer_code,
            'base_uom_vol'           => $this->base_uom_vol,
            'alter_base_uom_vol'     => $this->alter_base_uom_vol,
            // 'item_category'          => $this->item_category,
            'distribution_code'      => $this->distribution_code,
            'barcode'                => $this->barcode,
            'net_weight'             => $this->net_weight,
            'tax'                    => $this->tax,
            'vat'                    => $this->vat,
            'excise'                 => $this->excise,
            'uom_efris_code'         => $this->uom_efris_code,
            'altuom_efris_code'      => $this->altuom_efris_code,
            // 'item_group'             => $this->item_group,
            // 'item_group_desc'        => $this->item_group_desc,
            'caps_promo'             => $this->caps_promo,
            'sequence_no'            => $this->sequence_no,
            'rewards'                => $this->rewards,
            'volumes'                => $this->volumes,
            'uom' => $this->itemUoms->map(function ($u) {
                return [
                    'id'        => $u->id,
                    'name'      => $u->name,
                    'uom_type'  => $u->uom_type,
                    'upc'       => $u->upc,
                    'price'     => $u->price,
                    'uom_id'    => $u->uom_id,
                ];
            }),
        ];
    }
}
