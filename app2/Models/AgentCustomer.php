<?php

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgentCustomer extends Model
{
    use SoftDeletes, Blames;
    protected $table = 'agent_customers';

    protected $fillable = [
        'uuid',
        'osa_code',
        'name',
        // 'business_name',
        'customer_type',
        'route_id',
        'owner_name',
        'is_whatsapp',
        'whatsapp_no',
        'email',
        'language',
        'warehouse',
        'contact_no',
        'contact_no2',
        'buyerType',
        'street',
        'town',
        'vat_no',
        'landmark',
        'district',
        'payment_type',
        'creditday',
        'tin_no',
        'accuracy',
        'outlet_channel_id',
        'category_id',
        'subcategory_id',
        'credit_limit',
        // 'region_id',
        // 'area_id',
        'longitude',
        'latitude',
        // 'threshold_radius',
        'status',
    ];

    public function customertype()
    {
        return $this->belongsTo(CustomerType::class, 'customer_type');
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }
    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }
    public function outlet_channel()
    {
        return $this->belongsTo(OutletChannel::class, 'outlet_channel_id');
    }
    public function category()
    {
        return $this->belongsTo(CustomerCategory::class, 'category_id');
    }
    public function subcategory()
    {
        return $this->belongsTo(CustomerSubCategory::class, 'subcategory_id');
    }
    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_user');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_user');
    }
    public function getWarehouse(){
        return $this->belongsTo(Warehouse::class, 'warehouse');
    }
}
