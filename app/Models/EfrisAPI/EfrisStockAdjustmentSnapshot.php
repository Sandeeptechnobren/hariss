<?php

namespace App\Models\EfrisAPI;

use Illuminate\Database\Eloquent\Model;

class EfrisStockAdjustmentSnapshot extends Model
{
    protected $table = 'tbl_efris_stock_snapshot';

    protected $fillable = [
        'warehouse_id',
        'item_id',
        'goods_code',
        'goods_name',
        'uom',
        'unit_price',
        'efris_stock',
        'warehouse_stock',
        'adjustment',
        'adjustment_type',
        'last_synced_at'
    ];

    public $timestamps = false;
}
