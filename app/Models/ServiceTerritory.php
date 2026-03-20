<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\Blames;

class ServiceTerritory extends Model
{
    use SoftDeletes, Blames;

    protected $table = 'tbl_service_territory';

    protected $fillable = [
        'uuid',
        'osa_code',
        'warehouse_id',
        'region_id',
        'area_id',
        'technician_id',
        'created_user',
        'updated_user',
        'deleted_user'
    ];

    protected $casts = [
        'warehouse_id' => 'string',
        'region_id'    => 'string',
        'area_id'      => 'string',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = $model->uuid ?? Str::uuid();
        });
    }

    // ✅ This relation is OK (single FK)
    public function technician()
    {
        return $this->belongsTo(Salesman::class, 'technician_id');
    }
}
