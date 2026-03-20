<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Region extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_region';
    protected $fillable = [
        'region_code',
        'region_name',
        'company_id',
        'status',
        'created_user',
        'updated_user',
        'created_date',
        'updated_date',
    ];
    public $timestamps = true;

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';

    protected $dates = ['deleted_at'];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_user');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_user');
    }

    public function getRmUser()
    {
        return User::where('role', 92) // RM
            ->whereJsonContains('region', $this->id)
            ->first();
    }
}
