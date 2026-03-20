<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames;

class AcFridgeStatus extends Model
{
    use SoftDeletes, Blames;

    protected $table = 'ac_fridge_status';

    protected $fillable = [
        'uuid',
        'install_date',
        'remove_date',
        'salesman_id',
        'customer_id',
        'fridge_id',
        'agrement_id',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
        'deleted_at'         => 'datetime',
    ];

    public function chiller()
    {
        return $this->belongsTo(AddChiller::class, 'fridge_id', 'id');
    }
}
