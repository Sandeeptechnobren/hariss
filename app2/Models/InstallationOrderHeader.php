<?php

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstallationOrderHeader extends Model
{
    use SoftDeletes, Blames;

    protected $fillable = [
        'uuid',
        'osa_code',
        'name',
        'iro_id',
        'status',
        'created_user',
        'updated_user',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_user');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_user');
    }
}
