<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesmanType extends Model
{
    use HasFactory;

    protected $table = 'salesman_types';

    protected $fillable = [
        'salesman_type_code',
        'salesman_type_name',
        'salesman_type_status',
        'salesman_created_user',
        'salesman_updated_user',
    ];

    public $timestamps = false;

    protected $casts = [
        'salesman_created_date' => 'datetime',
        'salesman_updated_date' => 'datetime',
    ];
    public function createdBy(){
        return $this->belongsTo(User::class,'salesman_created_user');
    }
    public function updatedBy(){
        return $this->belongsTo(User::class,'salesman_updated_user');
    }


}
