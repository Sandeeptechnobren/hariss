<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferDetail extends Model
{
    protected $table = 'tbl_stock_transfer_details';

    protected $fillable = [
        'uuid',
        'header_id',
        'item_id',
        'transfer_qty',
        'created_user',
        'updated_user',
        'deleted_user',
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(
            StockTransferHeader::class,
            'header_id',
            'id'
        );
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }
}
