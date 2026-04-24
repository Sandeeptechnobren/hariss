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
        'location',
    ];
    protected $casts = [
        'location' => 'array', // ✅ auto json encode/decode
    ];

    public function salesman()
    {
        return $this->belongsTo(Salesman::class, 'salesman_id');
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
}
