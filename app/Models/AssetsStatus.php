<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetsStatus extends Model
{
    protected $table = 'assets_status';

    protected $fillable = [
        'name',
        'is_active'
    ];

    public $timestamps = true;
}
