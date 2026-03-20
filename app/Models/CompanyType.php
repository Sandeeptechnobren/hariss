<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CompanyType extends Model
{
    use SoftDeletes;

    protected $table = 'company_types';

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'status',
        'created_user',
        'updated_user'
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
