<?php

namespace App\Models;

use App\Traits\Blames;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscountHeader extends Model
{
    use SoftDeletes, Blames;

    protected $table = 'tbl_discount_headers';

    protected $fillable = [
        'uuid',
        'osa_code',
        'discount_name',
        'discount_apply_on',
        'discount_type',
        'bundle_combination',
        'from_date',
        'to_date',
        'status',
        'sales_team_type',
        'project_list',
        'order_amount',
        'discount_amount_percentage',
        'uom',
        'items',
        'item_category',
        'location',
        'customer',
        'key_location',
        'key_customer',
        'key_item',
        'created_user',
        'updated_user'
    ];

    // protected $casts = [
    //     'sales_team_type'  => 'array',
    //     'project_list'     => 'array',
    //     'items'            => 'array',
    //     'item_category'    => 'array',
    //     'location'         => 'array',
    //     'customer'         => 'array',
    //     'items'           => 'array',
    //     'item_category'    => 'array',

    //     'key_location'     => 'array',
    //     'key_customer'     => 'array',
    //     'key_item'         => 'array',
    // ];

    public function details()
    {
        return $this->hasMany(DiscountDetail::class, 'header_id');
    }
}
