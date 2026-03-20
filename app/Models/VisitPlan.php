<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames;

class VisitPlan extends Model
{
    use SoftDeletes, Blames;

    protected $table = 'visit_plan';

    protected $fillable = [
        'uuid',
        'salesman_id',
        'customer_id',
        'warehouse_id',
        'route_id',
        'latitude',
        'longitude',
        'visit_start_time',
        'visit_end_time',
        'shop_status',
        'remark',
        'created_user',
        'updated_user',
        'deleted_user'
    ];

    protected $casts = [
        'uuid' => 'string',
        'latitude' => 'float',
        'longitude' => 'float',
        'visit_start_time' => 'datetime',
        'visit_end_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // -------------------------
    // Relationships (Optional)
    // -------------------------

    public function salesman()
    {
        return $this->belongsTo(Salesman::class, 'salesman_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(AgentCustomer::class, 'customer_id', 'id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }
}
