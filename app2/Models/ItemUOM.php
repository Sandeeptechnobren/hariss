<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemUOM extends Model
{
    protected $table = 'item_uoms';

    protected $fillable = [
        'item_id',
        'name',
        'uom_type',
        'upc',
        'price',
        'is_stock_keeping',
        'keeping_quantity',
        'enable_for',
        'status',
    ];
}
