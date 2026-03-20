<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesmanWarehouseHistory extends Model
{
    protected $table = 'tbl_salesman_warehouse_history';

    protected $fillable = [
        'id',
        'salesman_id',
        'warehouse_id',
        'manager_id',
        'route_id',
        'requested_time',
        'requested_date',
    ];

    public $timestamps = true;

    public function salesman()
    {
        return $this->belongsTo(Salesman::class, 'salesman_id');
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
    public function manager()
    {
        return $this->belongsTo(Salesman::class, 'manager_id');
    }
    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id');
    }
}
