<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $table = 'tbl_areas';

    protected $fillable = [
        'area_code',
        'area_name',
        'region_id',
        'status',
        'created_user',
        'updated_user',
        'created_date',
        'updated_date'
    ];

    public $timestamps = false;

    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id');
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
