<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginSession extends Model
{
    protected $fillable = [
        'user_id',
        'token_id',
        'device',
        'ip_address',
        'user_agent',
        'last_used_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

