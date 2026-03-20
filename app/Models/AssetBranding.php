<?php

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetBranding extends Model
{
    use SoftDeletes, Blames;
    
    protected $table = 'assets_branding';

    protected $fillable = [
        'id',
        'osa_code',
        'name',
        'status',
        'created_user',
        'updated_user',
        'deleted_user',
    ];
}
