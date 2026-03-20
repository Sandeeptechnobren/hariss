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
        'uom_id',
    ];

    public function uom()
    {
        return $this->belongsTo(Uom::class, 'uom_id', 'id');
    }
        public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

}
