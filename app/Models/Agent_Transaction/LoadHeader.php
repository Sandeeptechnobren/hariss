<?php

namespace App\Models\Agent_Transaction;

use App\Models\Route;
use App\Models\Salesman;
use App\Models\SalesmanType;
use App\Models\ProjectList;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LoadHeader extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_load_header';

    protected $fillable = [
        'uuid',
        'osa_code',
        'warehouse_id',
        'route_id',
        'salesman_id',
        'is_confirmed',
        'accept_time',
        'salesman_sign',
        'latitude',
        'longtitude',
        'salesman_type',
        'project_type',
        'status',
        'load_id',
        'sync_time',
        'created_user',
        'updated_user',
        'deleted_user'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function details()
    {
        return $this->hasMany(LoadDetail::class, 'header_id');
    }
    public function salesman()
    {
        return $this->belongsTo(Salesman::class, 'salesman_id');
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id');
    }
    public function salesmantype()
    {
        return $this->belongsTo(SalesmanType::class, 'salesman_type');
    }
    public function projecttype()
    {
        return $this->belongsTo(ProjectList::class, 'project_type');
    }
}
