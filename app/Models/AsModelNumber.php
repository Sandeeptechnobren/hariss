<?php

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AsModelNumber extends Model
{
    use SoftDeletes, Blames;

    protected $table = 'as_model_number';

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'asset_type',
        'manu_type',
        'status',
        'created_user',
        'updated_user',
        'deleted_user',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = (string) \Illuminate\Support\Str::uuid();
        });
    }

     public function assetType()
    {
        return $this->belongsTo(AssetType::class, 'asset_type');
    }

     public function manuType()
    {
        return $this->belongsTo(AssetManufacturer::class, 'manu_type');
    }
}
