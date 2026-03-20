<?php

namespace App\Models\Agent_Transaction;

use App\Models\Agent_Transaction\LoadHeader;
use App\Models\Item;
use App\Models\ItemUOM;
use App\Models\Uom;
use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LoadDetail extends Model
{
    use SoftDeletes, Blames;

    protected $table = 'tbl_load_details';

    protected $fillable = [
        'uuid',
        'osa_code',
        'header_id',
        'item_id',
        'uom',
        'qty',
        'price',
        'status',
        'unload_status',
        'created_user',
        'updated_user',
        'deleted_user'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function header()
    {
        return $this->belongsTo(LoadHeader::class, 'header_id');
    }
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
    public function itemUom()
    {
        return $this->belongsTo(ItemUOM::class, 'uom', 'id');
    }
    public function itemUOMS()
    {
        return $this->belongsTo(ItemUOM::class, 'uom', 'uom_id')
            ->where('item_id', $this->item_id);
    }
    public function Uom()
    {
        return $this->belongsTo(Uom::class, 'uom', 'id');
    }
    public function unloadDetails()
    {
        return $this->hasMany(UnloadDetail::class, 'load_detail_id');
    }

    public function invoiceDetails()
    {
        return $this->hasMany(InvoiceDetail::class, 'load_detail_id');
    }
}
