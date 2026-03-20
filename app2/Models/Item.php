<?php

// namespace App\Models;

// use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;
// use Illuminate\Support\Str;

// class Item extends Model
// {
//     use SoftDeletes;

//     protected $fillable = [
//             'uuid',
//             'erp_code',
//             'code',
//             'name',
//             'description',
//             'image',
//             'brand',
//             'category_id',
//             'sub_category_id',
//             'item_weight',
//             'shelf_life',
//             'volume',
//             'is_promotional',
//             'is_taxable',
//             'has_excies',
//             'commodity_goods_code',
//             'excise_duty_code',
//             'status', 
//             'created_user',
//             'updated_user',
//     ];

//     public function itemCategory()
//     {
//         return $this->belongsTo(ItemCategory::class, 'category_id');
//     }
//     public function itemSubCategory()
//     {
//         return $this->belongsTo(ItemSubCategory::class, 'sub_category_id');
//     }

//     public function createdUser()
//     {
//         return $this->belongsTo(User::class, 'created_user');
//     }

//     public function updatedUser()
//     {
//         return $this->belongsTo(User::class, 'updated_user');
//     }
//     public function itemUoms()
//     {
//         return $this->hasMany(ItemUOM::class, 'item_id');
//     }
// }

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Item extends Model
{
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
            'is_promotional',
            'is_taxable',
            'has_excies',
            'commodity_goods_code',
            'excise_duty_code',
            'status', 
            'created_user',
            'updated_user',
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
        return $this->hasMany(ItemUOM::class, 'item_id');
    }
} 