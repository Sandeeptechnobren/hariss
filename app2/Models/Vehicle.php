<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_vehicle';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'vehicle_code',
        'number_plat',
        'vehicle_chesis_no',
        'description',
        'capacity',
        'vehicle_type',
        'vehicle_brand',
        'owner_type',
        'fuel_reading',
        'warehouse_id',
        'valid_from',
        'valid_to',
        'opening_odometer',
        'status',
        'created_user',
        'updated_user'
    ];

    protected $dates = [
        'valid_from',
        'valid_to',
        'deleted_at',
        'created_at',
        'updated_at',
    ];
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_user');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_user');
    }
}
