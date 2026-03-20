<?php

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Brand extends Model
{
    use SoftDeletes, Blames;

    protected $table = 'tbl_brands';

    protected $fillable = [
        'uuid',
        'osa_code',
        'name',
    ];

    protected $casts = [
        'uuid' => 'string',
    ];


protected static function boot()
{
    parent::boot();

    static::creating(function ($model) {
        // Always assign UUID
        $model->uuid = $model->uuid ?? (string) Str::uuid();

        // ✅ If osa_code provided manually, use it directly
        if (!empty($model->osa_code)) {
            return; // skip auto generation
        }
       
        // ✅ Otherwise auto-generate next code
        $prefix = 'BRND';
        $latestOsaCode = self::withTrashed()->orderBy('id', 'desc')->value('osa_code');

        if ($latestOsaCode && preg_match('/\d+$/', $latestOsaCode, $m)) {
            $nextNumber = intval($m[0]) + 1;
        } else {
            $nextNumber = 1;
        }

        $model->osa_code = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    });
}

}