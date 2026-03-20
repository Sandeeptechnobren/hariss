<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $table = 'logs';

    protected $fillable = [
        'menu_id',
        'sub_menu_id',
        'mode',
        'user_id',
        'previous_data',
        'current_data',
        'ip_address',
        'browser',
        'os',
        'user_agent',
        'user_role',
    ];

    protected $casts = [
        'previous_data' => 'array',
        'current_data'  => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id','id');
    }
}
