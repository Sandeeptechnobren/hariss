<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OutletChannel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'outlet_channel';
    protected $primaryKey = 'id';
    public $timestamps = false; // because we are using created_date & updated_date manually

    protected $fillable = [
        'outlet_channel_code',
        'outlet_channel',
        'status',
        'created_user',
        'updated_user',
        'created_date',
        'updated_date',
    ];
    protected $casts = [
        'deleted_at'   => 'datetime',
    ];
    public function createdBy(){
        return $this->belongsTo(User::class,'created_user');
    }
    public function updatedBy(){
        return $this->belongsTo(User::class,'created_user');
    }

}
