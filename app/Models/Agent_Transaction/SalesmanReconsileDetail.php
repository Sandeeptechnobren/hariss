<?php

namespace App\Models\Agent_Transaction;
use App\Models\Item;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesmanReconsileDetail extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_salesman_reconsile_detail';

    protected $fillable = [
        'header_id',
        'item_id',
        'load_qty',
        'unload_qty',
        'invoice_qty',
    ];

    protected $casts = [
        'load_qty'    => 'integer',
        'unload_qty'  => 'integer',
        'invoice_qty' => 'integer',
    ];

    /**
     * Detail → Header
     */
    public function header()
    {
        return $this->belongsTo(
            SalesmanReconsileHeader::class,
            'header_id',
            'id'
        );
    }

    /**
     * Detail → Item
     */
    public function item()
    {
        return $this->belongsTo(
            Item::class,
            'item_id',
            'id'
        );
    }
}
