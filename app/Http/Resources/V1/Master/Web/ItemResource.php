<?php

// namespace App\Http\Resources\V1\Master\Web;

// use Illuminate\Http\Request;
// use Illuminate\Http\Resources\Json\JsonResource;

// class ItemResource extends JsonResource
// {
//     public function toArray(Request $request): array
//     {
//         return [
//             'id' => $this->id,

//             'erp_code' => $this->erp_code,
//             'item_code' => $this->code,
//             'name' => $this->name,
//             'description' => $this->description,
//             'uom' => $this->itemUoms,
//             'brand'=>$this->brand,
//             'image'=>$this->image,
//             'category' => $this->itemCategory ? [
//                 'id' => $this->itemCategory?->id,
//                 'name' => $this->itemCategory?->category_name,
//                 'code' => $this->itemCategory?->category_code,
//             ] : null,
//             'itemSubCategory' => $this->itemSubCategory ? [
//                 'id' => $this->itemSubCategory?->id,
//                 'name' => $this->itemSubCategory?->sub_category_name,
//                 'code' => $this->itemSubCategory?->sub_category_code,
//             ] : null,
//             'shelf_life' => $this->shelf_life,
//             'commodity_goods_code' => $this->commodity_goods_code,
//             'excise_duty_code' => $this->excise_duty_code,
//             'status' => $this->status,
//             'is_taxable'=>$this->is_taxable,
//             'has_excies'=>$this->has_excies,
//             'item_weight'=>$this->item_weight,
//             'volume'=>$this->volume
//         ];
//     }
// }
namespace App\Http\Resources\V1\Master\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'code' => $this->code,
            'erp_code' => $this->erp_code,
            'item_code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'image' => $this->image,
            'is_promotional' => $this->is_promotional,
            'caps_promo' => $this->caps_promo,
            'item_uoms' => $this->itemUoms->map(function ($uom) {
                return [
                    'uom' => $uom->uom_id,
                    'uom_price' => $uom->price,
                    'name'             => $uom->uom ? $uom->uom->name : null, // ✅ From uoms table
                    'upc' => $uom->upc,
                    'uom_type' => $uom->uom_type,
                    'is_stock_keeping' => $uom->is_stock_keeping,
                    'keeping_quantity' => $uom->keeping_quantity,
                    'enable_for' => $uom->enable_for,
                ];
            }),
            'brand' => $this->brandData,
            'brand' => $this->brandData ? [
                'id' => $this->brandData?->id,
                'name' => $this->brandData?->name
            ] : null,
            'image' => $this->image,
            'item_category' => $this->itemCategory ? [
                'id' => $this->itemCategory?->id,
                'category_name' => $this->itemCategory?->category_name,
                'code' => $this->itemCategory?->category_code,
            ] : null,
            'item_sub_category' => $this->itemSubCategory ? [
                'id' => $this->itemSubCategory?->id,
                'name' => $this->itemSubCategory?->sub_category_name,
                'code' => $this->itemSubCategory?->sub_category_code,
            ] : null,
            'pricing_detail' => $this->pricing_details ? [
                'buom_ctn_price' => $this->pricing_details?->buom_ctn_price,
                'auom_pc_price'  => $this->pricing_details?->auom_pc_price,
            ] : null,
            'shelf_life' => $this->shelf_life,
            'commodity_goods_code' => $this->commodity_goods_code,
            'excise_duty_code' => $this->excise_duty_code,
            'status' => $this->status,
            'is_taxable' => $this->is_taxable,
            'has_excies' => $this->has_excies,
            'item_weight' => $this->item_weight,
            'volume' => $this->volume,
            'warehouse_stocks' => $this->warehouse_stocks?->map(function ($stock) {
                return [
                    'osa_code' => $stock->osa_code,
                    // 'quantity' => (float) $stock->quantity,
                    'warehouse_id' => $stock->warehouse_id,
                    // 'item_id' => $stock->item_id,
                    'qty' => $stock->qty,
                ];
            }) ?? [],
        ];
    }
}
