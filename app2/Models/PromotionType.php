<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionType extends Model
{
    protected $table = 'promotion_types';

    protected $fillable = [
        'code',
        'name',
        'status',
        'created_user',
        'updated_user',
    ];

    public $timestamps = false;

    protected $casts = [
        'status' => 'integer',
        'created_user' => 'integer',
        'updated_user' => 'integer',
        'created_date' => 'datetime',
        'updated_date' => 'datetime',
    ];
    public function createdBy(){
        return $this->belongsTo(User::class,'created_user');
    }
    public function updatedBy(){
        return $this->belongsTo(User::class,'created_user');
    }

}
