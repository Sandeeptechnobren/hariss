<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'tbl_country';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'country_code',
        'country_name',
        'currency',
        'status',
        'created_user',
        'updated_user',
        'created_date',
        'updated_date'
    ];

    public function companies()
    {
        return $this->hasMany(Company::class, 'country_id');
    }
    public function region()
    {
        return $this->hasMany(Region::class, 'country_id');
    }
    public function createdBy(){
        return $this->belongsTo(User::class,'created_user');
    }
    public function updatedBy(){
        return $this->belongsTo(User::class,'created_user');
    }


}
