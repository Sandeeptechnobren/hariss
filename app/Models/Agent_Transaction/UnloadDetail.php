<?php

namespace App\Models\Agent_Transaction;

use App\Models\Item;
use App\Models\ItemUOM;
use App\Models\Uom;
use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnloadDetail extends Model
{
    use SoftDeletes, Blames;

    protected $table = 'tbl_unload_detail';
    protected $fillable = [
        'uuid',
        'osa_code',
        'header_id',
        'item_id',
        'uom',
        'qty',
        'status'
    ];

    public function header()
    {
        return $this->belongsTo(UnloadHeader::class, 'header_id');
    }
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function itemuom()
    {
        return $this->belongsTo(ItemUOM::class, 'uom','id');
    }
    public function uoms()
    {
        return $this->belongsTo(Uom::class, 'uom','id');
    }
        public function itemUOMS()
    {
        return $this->belongsTo(ItemUOM::class, 'uom', 'uom_id')
            ->where('item_id', $this->item_id);
    }
}
