<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usertypes extends Model
{
    protected $table = 'user_types';
    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
        'status',
        'created_user',
        'updated_user',
        'created_date',
        'updated_date'
    ];
    public function createdBy(){
        return $this->belongsTo(User::class,'created_user');
    }
    public function updatedBy(){
        return $this->belongsTo(User::class,'created_user');
    }

}
