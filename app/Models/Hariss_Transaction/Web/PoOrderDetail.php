<?php

namespace App\Models\Hariss_Transaction\Web;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\Blames;
use App\Models\Item;
use App\Models\Uom;

class PoOrderDetail extends Model
{
    use HasFactory, SoftDeletes, Blames;

    protected $table = 'ht_po_order_detail';

    protected $fillable = [
        'uuid',
        'header_id',
        'item_id',
        'uom_id',
        'discount_id',
        'promotion_id',
        'parent_id',
        'item_price',
        'quantity',
        'discount',
        'gross_total',
        'promotion',
        'net',
        'excise',
        'pre_vat',
        'vat',
        'total',
        'created_user',
        'updated_user',
        'deleted_user',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = Str::uuid();
            }
        });
    }

    public function header()
    {
        return $this->belongsTo(PoOrderHeader::class, 'header_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class, 'uom_id');
    }
}
