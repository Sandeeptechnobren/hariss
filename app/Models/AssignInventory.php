<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames;

class AssignInventory extends Model
{
    use SoftDeletes, Blames;
    protected $table = 'assign_inventory';

    protected $fillable = [
        'uuid',
        'item_id',
        'item_uom',
        'header_id',
        'capacity',
    ];

        public function item()
    {
        return $this->belongsTo(Item::class,'item_id', 'id');
    }
        public function uom()
    {
        return $this->belongsTo(Uom::class,'item_uom', 'id');
    }
        public function StockInStore()
    {
        return $this->belongsTo(StockInStore::class,'header_id', 'id');
    }
    public function alluom()
    {
        return $this->hasMany(ItemUOM::class,'item_id', 'item_id');
    }
}
