<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\Blames;

class Discount extends Model
{
    use SoftDeletes, Blames;

    protected $table = 'discounts';

    protected $fillable = [
        'osa_code',
        'uuid',
        'item_id',
        'category_id',
        'customer_id',
        'customer_channel_id',
        'discount_type',
        'discount_value',
        'min_quantity',
        'min_order_value',
        'start_date',
        'end_date',
        'status',
        'created_user',
        'deleted_user',
        'updated_user',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
    public function itemCategory()
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }
    public function itemSubCategory()
    {
        return $this->belongsTo(ItemSubCategory::class, 'category_id');
    }    
    public function discountType()
    {
        return $this->belongsTo(DiscountType::class, 'discount_type');
    }
    public function customerDetails(){
        return $this->belongsTo(AgentCustomer::class,'customer_id');
    }
    public function outletChannel(){
        return $this->belongsTo(OutletChannel::class,'customer_channel_id');
    }
    public function customer()
    {    
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
