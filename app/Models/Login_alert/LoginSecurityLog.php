<?php

namespace App\Models\Login_alert;

use Illuminate\Database\Eloquent\Model;

class LoginSecurityLog extends Model
{
    protected $table = 'login_security_logs';

    protected $fillable = [
        'user_id',
        'username',
        'email',
        'password_hash',
        'ip_address',
        'device',
        'browser',
        'location',
        'attempt_count',
        'attempt_time'
    ];
}
