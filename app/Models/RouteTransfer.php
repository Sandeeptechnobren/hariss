<?php

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class RouteTransfer extends Model
{
    use SoftDeletes, Blames;

    protected $table = 'tbl_route_transfer';

    protected $fillable = [
        'uuid',
        'old_route_id',
        'new_route_id',
        'old_warehouse_id',
        'new_warehouse_id',
        'customer_ids',
        'performed_by',
        'created_user',
        'updated_user',
        'deleted_user',
        'transferred_at',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
    ];
}
