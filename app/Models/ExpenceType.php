<?php

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ExpenceType extends Model
{
    use SoftDeletes, Blames;

    protected $table = 'tbl_expence_type';

    protected $fillable = [
        'uuid',
        'osa_code',
        'name',
        'status',
        'created_user',
        'updated_user',
        'deleted_user'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_user');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_user');
    }
}
