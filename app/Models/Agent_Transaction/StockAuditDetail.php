<?php

namespace App\Models\Agent_Transaction;

use App\Models\Item;
use App\Models\ItemUOM;

use Illuminate\Database\Eloquent\Model;

class StockAuditDetail extends Model
{
    protected $table = 'tbl_stockaudit_details';

    protected $fillable = [
        'uuid',
        'header_id',
        'item_id',
        'uom_id',
        'warehouse_stock',
        'physical_stock',
        'variance',
        'saleon_otc',
        'remarks',
        'created_user',
        'updated_user'
    ];

    // ✅ Relations
    public function header()
    {
        return $this->belongsTo(StockAuditHeader::class, 'header_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function uom()
    {
        return $this->belongsTo(ItemUOM::class, 'uom_id', 'uom_id');
    }
}
