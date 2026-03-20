<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Blames;


class SalesmanLocation extends Model
{
    use HasFactory, Blames;

    protected $table = 'tbl_salesman_location';

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'uuid',
        'salesman_id',
        'warehouse_id',
        'route_id',
        'latitude',
        'longitude',
    ];

}
