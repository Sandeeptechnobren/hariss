<?php

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetManufacturer extends Model
{
    use SoftDeletes, Blames;

    protected $table = 'am_manufacturer';

    protected $fillable = [
        'uuid',
        'osa_code',
        'name',
        'asset_type',
        'status',
        'created_user',
        'updated_user',
        'deleted_user',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function assetType()
    {
        return $this->belongsTo(AssetType::class, 'asset_type');
    }
}
