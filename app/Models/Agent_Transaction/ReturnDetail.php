<?php

namespace App\Models\Agent_Transaction;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Item;
use App\Models\Discount;
use App\Models\Uom;
use App\Models\PromotionHeader;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\Blames;
use Illuminate\Support\Str;
use App\Models\ItemUOM;

class ReturnDetail extends Model
{
    use HasFactory, SoftDeletes,Blames;

    protected $table="return_details";
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';


    protected $fillable = [
        'uuid',
        'header_id',
        'header_code',
        'item_id',
        'uom_id',
        'discount_id',
        'promotion_id',
        'parent_id',
        'item_price',
        'item_quantity',
        'return_type',
        'return_reason',
        'vat',
        'discount',
        'gross_total',
        'net_total',
        'total',
        'is_promotional',
        'status',
    ];

     protected $casts = [
        'uuid' => 'string',
        'item_price' => 'float',
        'item_quantity' => 'integer',
        'vat' => 'float',
        'discount' => 'float',
        'gross_total' => 'float',
        'net_total' => 'float',
        'total' => 'float',
        'is_promotional' => 'boolean',
        'status' => 'integer',
    ];

   protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    public function header(): BelongsTo
    {
        return $this->belongsTo(InvoiceHeader::class, 'header_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class, 'uom_id','id');
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class, 'discount_id');
    }
    public function returntype(): BelongsTo
    {
        return $this->belongsTo(ReturnType::class, 'return_type');
    }
    public function returnreason(): BelongsTo
    {
        return $this->belongsTo(ResonType::class, 'return_reason');
    }

    // public function parent(): BelongsTo
    // {
    //     return $this->belongsTo(InvoiceDetail::class, 'parent_id');
    // }

    // public function children(): HasMany
    // {
    //     return $this->hasMany(InvoiceDetail::class, 'parent_id');
    // }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(PromotionHeader::class, 'promotion_id');
    }
        public function returnHeader()
    {
        return $this->belongsTo(ReturnHeader::class, 'header_id', 'id');
    }
    public function itemUom()
    {
        return $this->belongsTo(ItemUOM::class, 'uom_id', 'uom_id')
            ->where('item_id', $this->item_id);
    }

}
