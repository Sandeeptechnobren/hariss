<?php

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Uom extends Model
{
    use SoftDeletes, Blames;

    protected $table = 'uom';
    protected $fillable = ['uuid', 'name', 'osa_code'];
}
