<?php

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use SoftDeletes, Blames;

    protected $table = 'tbl_vendor';

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'address',
        'contact',
        'email',
        'status'
    ];
}
