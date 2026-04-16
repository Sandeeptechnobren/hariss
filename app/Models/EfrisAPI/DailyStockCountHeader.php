<?php

namespace App\Models\EfrisAPI;

use App\Models\Warehouse;

use Illuminate\Database\Eloquent\Model;

class DailyStockCountHeader extends Model
{
    protected $connection = 'efris_pgsql';
    protected $table = 'daily_stock_count_header';

    protected $fillable = [
        'warehouse_id',
        'customer_id',
        'date',
        'total_good_stock_amount',
        'total_bad_stock_amount'
    ];

    public function details()
    {
        return $this->hasMany(DailyStockCountDetail::class, 'header_id');
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
}
