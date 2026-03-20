<?php

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Label extends Model
{
    use SoftDeletes, Blames;

    protected $fillable = [
        'uuid',
        'osa_code',
        'name',
        'status',
        'created_user',
        'updated_user',
        'deleted_user'
    ];
}
