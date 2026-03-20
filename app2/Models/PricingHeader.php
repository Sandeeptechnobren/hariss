<?php

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PricingHeader extends Model
{
    use SoftDeletes, Blames;

    protected $fillable = [
        'uuid',
        'name',
        'code',
        'description',
        'start_date',
        'end_date',
        'apply_on',
        'warehouse_id',
        'item_type',
        'status',
        'created_user',
        'updated_user',

        // 🔹 New foreign key fields
        'company_id',
        'region_id',
        'area_id',
        'route_id',
        'item_id',
        'item_category_id',
        'customer_id',
        'customer_category_id',
        // 'customer_type_id',
        'outlet_channel_id',
    ];

    protected $casts = [
        'description' => 'array',
        'created_date' => 'datetime',
        'updated_date' => 'datetime',
        'deleted_at'   => 'datetime',
    ];

    // 🔹 Relationships
    public function details()
    {
        return $this->hasMany(PricingDetail::class, 'header_id');
    }

    public function itemType()
    {
        return $this->belongsTo(ItemCategory::class, 'item_type');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_user');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_user');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id');
    }

    public function itemCategory()
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }


    public function customerCategory()
    {
        return $this->belongsTo(CustomerCategory::class, 'customer_category_id');
    }

    public function customerType()
    {
        return $this->belongsTo(CustomerType::class, 'customer_type_id');
    }

    public function outletChannel()
    {
        return $this->belongsTo(OutletChannel::class, 'outlet_channel_id');
    }
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
    public function customer()
    {
        return $this->belongsTo(AgentCustomer::class, 'customer_id');
    }
}
