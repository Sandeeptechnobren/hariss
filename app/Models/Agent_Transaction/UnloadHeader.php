<?php

namespace App\Models\Agent_Transaction;

use App\Models\Route;
use App\Models\Salesman;
use App\Models\SalesmanType;
use App\Models\ProjectList;
use App\Models\Warehouse;
use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnloadHeader extends Model
{
    use SoftDeletes, Blames;
    public $timestamps = true;

    protected $table = 'tbl_unload_header';
    protected $fillable = [
        'uuid',
        'osa_code',
        'unload_no',
        'unload_date',
        'unload_time',
        'sync_date',
        'sync_time',
        'warehouse_id',
        'route_id',
        'salesman_id',
        'salesman_type',
        'project_type',
        'latitude',
        'longtitude',
        'unload_from',
        'load_date',
        'status'
    ];

    public function details()
    {
        return $this->hasMany(UnloadDetail::class, 'header_id');
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
    public function projecttype()
    {
        return $this->belongsTo(ProjectList::class, 'project_type');
    }
    public function salesmantype()
    {
        return $this->belongsTo(SalesmanType::class, 'salesman_type');
    }
}
