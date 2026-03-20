<?php

namespace App\Models\Agent_Transaction;

use App\Models\Item;
use App\Models\ItemUOM;
use App\Models\Uom;
use App\Traits\Blames;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AgentDeliveryDetails extends Model
{
    use HasFactory, SoftDeletes, Blames;

    protected $table = 'agent_delivery_details';

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
        'vat',
        'discount',
        'gross_total',
        'net_total',
        'total',
        'is_promotional',
        'created_user',
        'updated_user',
        'deleted_user',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function header()
    {
        return $this->belongsTo(AgentDeliveryHeaders::class, 'header_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

        public function itemUom()
    {
        return $this->belongsTo(ItemUOM::class, 'uom_id');
    }
    public function Uom()
    {
        return $this->belongsTo(Uom::class, 'uom_id','id');
    }
    public function ItemUOMS()
    {
        return $this->belongsTo(ItemUOM::class, 'uom_id', 'uom_id')
            ->where('item_id', $this->item_id);
    }
}
