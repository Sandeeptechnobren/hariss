<?php

namespace App\Models;

use App\Traits\Blames;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransferHeader extends Model
{
    use SoftDeletes, Blames;
    protected $table = 'tbl_stock_transfer_header';

    protected $fillable = [
        'uuid',
        'osa_code',
        'source_warehouse',
        'destiny_warehouse',
        'transfer_date',
        'status',
        'created_user',
        'updated_user',
        'deleted_user',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(
            StockTransferDetail::class,
            'header_id',
            'id'
        );
    }
    public function sourceWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse');
    }
    public function destinyWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'destiny_warehouse');
    }
}
