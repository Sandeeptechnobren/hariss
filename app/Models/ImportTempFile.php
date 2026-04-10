<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ImportTempFile extends Model
{
    use SoftDeletes;

    protected $table = 'import_temp_files';

    protected $fillable = [
        'uuid', 'FileName'
    ];

    protected static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->uuid = Str::uuid()->toString();
        });
    }
}