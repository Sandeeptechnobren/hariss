<?php

namespace App\Models\Agent_Transaction;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;


class ResonType extends Model
{
  use Blames;
    protected $table = 'reson_type';
    // public $timestamps = false;
   
    protected $fillable = [
        'return_id',
        'reson',
        'created_at',
        'updated_at',
    ];
    public function returnType()
    {
        return $this->belongsTo(ReturnType::class, 'return_id', 'id');
    }

}