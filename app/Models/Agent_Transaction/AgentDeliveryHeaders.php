<?php

namespace App\Models\Agent_Transaction;

use App\Models\AgentCustomer;
use App\Models\Country;
use App\Models\Route;
use App\Models\Salesman;
use App\Models\Warehouse;
use App\Traits\Blames;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AgentDeliveryHeaders extends Model
{
    use HasFactory, SoftDeletes, Blames;

    protected $table = 'agent_delivery_headers';

    protected $fillable = [
        'uuid',
        'delivery_code', 
        'warehouse_id',
        'customer_id',
        'currency',
        'country_id',
        'route_id',
        'salesman_id',
        'gross_total',
        'vat',
        'discount',
        'net_amount',
        'total',
        'order_code',
        'comment',
        'status',
        'latitude',
        'longitude',
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

    public function details()
    {
        return $this->hasMany(AgentDeliveryDetails::class, 'header_id');
    }

     public function warehouse()
    {
        return $this->belongsTo(Warehouse ::class, 'warehouse_id');
    }
     public function customer()
    {
        return $this->belongsTo(AgentCustomer ::class, 'customer_id');
    }
     public function country()
    {
        return $this->belongsTo(Country ::class, 'country_id');
    }
     public function route()
    {
        return $this->belongsTo(Route ::class, 'route_id');
    }
     public function salesman()
    {
        return $this->belongsTo(Salesman ::class, 'salesman_id');
    }
}
