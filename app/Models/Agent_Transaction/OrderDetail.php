<?php

namespace App\Models\Agent_Transaction;

// use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\Blames;
use App\Models\Item;
use App\Models\Discount;
use App\Models\ItemUOM;
use App\Models\Uom;
use App\Models\PromotionHeader;
use App\Models\DiscountHeader;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderDetail extends Model
{
    use HasFactory, SoftDeletes,Blames;
    protected $table="agent_order_details";

    protected $fillable = [
        'header_id',
        'uuid',
        'item_id',
        'item_price',
        'quantity',
        'vat',
        'uom_id',
        'discount',
        'discount_id',
        'gross_total',
        'net_total',
        'total',
        'is_promotional',
        'parent_id',
        'promotion_id',
        'promotion',
    ];

    protected $casts = [
        'item_price' => 'decimal:2',
        'quantity' => 'decimal:3',
        'vat' => 'decimal:2',
        'discount' => 'decimal:2',
        'gross_total' => 'decimal:2',
        'net_total' => 'decimal:2',
        'total' => 'decimal:2',
        'is_promotional' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
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


    public function header(): BelongsTo
    {
        return $this->belongsTo(OrderHeader::class, 'header_id', 'id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class, 'discount_id');
    }
        public function discounts(): BelongsTo
    {
        return $this->belongsTo(DiscountHeader::class, 'discount_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(ItemUOM::class, 'uom_id');
    }
    public function uoms(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'uom_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrderDetail::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(OrderDetail::class, 'parent_id');
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(PromotionHeader::class, 'promotion_id');
    }
        public function itemUom()
    {
        return $this->belongsTo(ItemUOM::class, 'uom_id', 'uom_id')
            ->where('item_id', $this->item_id);
    }
}
