<?php

namespace App\Models;
use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class setting extends Model
{
     use HasFactory,SoftDeletes,Blames;

    protected $fillable = [
        'uuid',
        'osa_code',
        'file_name',
        'created_user',
        'updated_user',
        'deleted_user',
    ];
    protected static function booted()
    {
        // Automatic UUID
        static::creating(function ($model) {
            $model->uuid = \Str::uuid();
        });

        // Automatic file deletion after model is deleted
        static::deleted(function ($model) {
            $filePath = storage_path('app/' . $model->file_name);
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        });
    }
}
