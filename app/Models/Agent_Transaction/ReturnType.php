<?php

namespace App\Models\Agent_Transaction;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;

class ReturnType extends Model
{
    use  Blames;
    protected $table = 'return_type';
    // public $timestamps = false;
    
    protected $fillable = [
        'id',
        'return_type',
        'created_at',
        'updated_at',
    ];



}