<?php

namespace App\Models;
use App\Traits\Blames;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class VersionControll extends Model
{
    use SoftDeletes, Blames;
    protected $table = 'version_controlls';

    protected $fillable = [
        'uuid',
        'osa_code',
        'version',
        'created_user',
        'updated_user',
        'deleted_user',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
