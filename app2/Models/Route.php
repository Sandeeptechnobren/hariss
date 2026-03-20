<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $table = 'tbl_route';

    protected $fillable = [
        'route_code',
        'route_name',
        'description',
        'warehouse_id',
        'route_type',
        'vehicle_id',
        'status',
        'created_user',
        'updated_user'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->route_code)) {
                $lastId = self::max('id') ?? 0;
                $nextId = $lastId + 1;
                $model->route_code = 'RT' . str_pad($nextId, 2, '0', STR_PAD_LEFT);
            }
        });
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_user');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_user');
    }
    public function getrouteType()
    {
        return $this->belongsTo(RouteType::class,'route_type');
    }
    
}
