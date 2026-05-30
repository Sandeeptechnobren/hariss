<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2CUserOtp extends Model
{
    protected $table = 'b2c_user_otp';

    public $timestamps = false;

    protected $fillable = [
        'phone',
        'otp',
        'expires_at',
        'created_at'
    ];
}
