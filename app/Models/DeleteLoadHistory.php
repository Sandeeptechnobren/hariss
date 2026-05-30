<?php

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeleteLoadHistory extends Model
{
    use SoftDeletes, Blames;
    protected $table = 'tbl_delete_load_history';

    protected $fillable = [
        'load_id',
        'load_code',
        'salesman_id',
        'route_id',
        'warehouse_id'
    ];
}
