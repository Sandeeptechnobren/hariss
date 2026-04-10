<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DiscountSetting extends Model
{
    protected $table = 'discount_setting';

    protected $fillable = [
        'id',
        'uuid',
        'name',
        'discount_amt',
        'qty',
        'status',
        'created_user',
        'updated_user'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
