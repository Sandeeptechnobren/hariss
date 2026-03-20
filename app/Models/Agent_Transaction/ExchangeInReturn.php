<?php

namespace App\Models\Agent_Transaction;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\Blames;
use App\Models\Item;
use App\Models\Uom;
use App\Models\ItemUOM;
use App\Models\PromotionHeader;
use App\Models\Discount;
use App\Models\User;
use App\Models\AgentOrderHeader;
use App\Models\Agent_Transaction\ExchangeInInvoice;
use App\Models\Agent_Transaction\ExchangeHeader;
use App\Models\Agent_Transaction\ExchangeInReturn;
use Illuminate\Support\Str;


class ExchangeInReturn extends Model
{
    use HasFactory,Blames,SoftDeletes;

    protected $table = 'exchange_in_returns';

    protected $fillable = [
        'uuid',
        'header_id',
        'exchange_code',
        'item_id',
        'uom_id',
        'parent_id',
        'promotion_id',
        'discount_id',
        'item_price',
        'item_quantity',
        'VAT',
        'discount',
        'gross_total',
        'net_total',
        'total',
        'is_promotional',
        'status',
        'region',
        'return_type',
    ];

    protected $casts = [
        'is_promotional' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }


    public function header()
    {
        return $this->belongsTo(ExchangeHeader::class, 'header_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    // public function uom()
    // {
    //     return $this->belongsTo(ItemUom::class, 'uom_id');
    // }

    public function promotion()
    {
        return $this->belongsTo(PromotionHeader::class, 'promotion_id');
    }

    public function discount()
    {
        return $this->belongsTo(Discount::class, 'discount_id');
    }

    public function parent()
    {
        return $this->belongsTo(ExchangeInReturn::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ExchangeInReturn::class, 'parent_id');
    }

        public function uom()
    {
        return $this->belongsTo(ItemUOM::class, 'uom_id');
    }

    public function uoms()
    {
        return $this->belongsTo(Uom::class, 'uom_id','id');
    }
}