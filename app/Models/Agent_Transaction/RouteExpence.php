<?php

namespace App\Models\Agent_Transaction;

use App\Models\ExpenceType;
use App\Models\Route;
use App\Models\Salesman;
use App\Models\Warehouse;
use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class RouteExpence extends Model
{
    use SoftDeletes, Blames;

    protected $table = 'tbl_route_expence';

    protected $fillable = [
        'uuid',
        'osa_code',
        'salesman_id',
        'warehouse_id',
        'route_id',
        'expence_type',
        'description',
        'image',
        'date',
        'amount',
        'status',
        'created_user',
        'updated_user',
        'deleted_user',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function expenceType()
    {
        return $this->belongsTo(ExpenceType::class, 'expence_type');
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
}
