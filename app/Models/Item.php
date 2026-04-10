<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Item extends Model
{
    protected $connection = 'pgsql';
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'erp_code',
        'code',
        'name',
        'description',
        'image',
        'brand',
        'category_id',
        'sub_category_id',
        'item_weight',
        'shelf_life',
        'volume',
        'discount_status',
        'is_promotional',
        'is_taxable',
        'has_excies',
        'commodity_goods_code',
        'excise_duty_code',
        'status',
        'created_user',
        'updated_user',
        'base_uom',
        'alternate_uom',
        'customer_code',
        'upc',
        'base_uom_vol',
        'alter_base_uom_vol',
        'item_category',
        'distribution_code',
        'barcode',
        'net_weight',
        'base_uom_price',
        'base_alternate_uom_price',
        'tax',
        'vat',
        'excise',
        'uom_efris_code',
        'altuom_efris_code',
        'item_group',
        'item_group_desc',
        'rewards',
        'volumes',
    ];

    public function itemCategory()
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }
    public function itemSubCategory()
    {
        return $this->belongsTo(ItemSubCategory::class, 'sub_category_id');
    }

    public function createdUser()
    {
        return $this->belongsTo(User::class, 'created_user');
    }

    public function updatedUser()
    {
        return $this->belongsTo(User::class, 'updated_user');
    }
    public function itemUoms()
    {
        return $this->hasMany(ItemUOM::class, 'item_id')->with('uom:id,name');
    }

    public function warehouse_stocks()
    {
        return $this->hasMany(WarehouseStock::class, 'item_id', 'id');
    }

    public function pricingHeaders()
    {
        return $this->hasMany(PricingHeader::class, 'item_id', 'id');
    }
    public function brandData()
    {
        return $this->belongsTo(Brand::class, 'brand');
    }
    public function pricing_details()
    {
        return $this->hasOne(PricingDetail::class, 'item_id', 'id');
    }
    public function latestPricing(): HasOne
    {
        return $this->hasOne(PricingDetail::class, 'item_id')
            ->latestOfMany();
    }


    public function uoms()
    {
        return $this->hasMany(ItemUOM::class, 'item_id');
    }
}
