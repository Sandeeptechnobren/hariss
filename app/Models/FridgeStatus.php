<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FridgeStatus extends Model
{
    protected $table = 'tbl_fridge_status';

    protected $fillable = [
        'name',
        'code',
        'status'
    ];

    public $timestamps = true;
}
