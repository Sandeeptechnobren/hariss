<?php

namespace App\Models\EfrisAPI;

use App\Models\Item;

use Illuminate\Database\Eloquent\Model;

class DailyStockCountDetail extends Model
{
    protected $connection = 'efris_pgsql';
    protected $table = 'daily_stock_count_details';

    protected $fillable = [
        'header_id',
        'item_id',
        'qty'
    ];

    public function header()
    {
        return $this->belongsTo(DailyStockCountHeader::class, 'header_id');
    }
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
