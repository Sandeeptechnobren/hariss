<?php

namespace App\Models\EfrisAPI;

use Illuminate\Database\Eloquent\Model;

class EfrisStockAdjustmentLog extends Model
{
    protected $table = 'tbl_efris_stock_logs';

    protected $fillable = [
        'warehouse_id',
        'item_id',
        'request_payload',
        'response_payload',
        'return_code',
        'return_message'
    ];

    public $timestamps = false;
}
