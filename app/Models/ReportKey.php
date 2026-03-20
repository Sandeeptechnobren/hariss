<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportKey extends Model
{
    protected $table = 'report_keys';
    protected $fillable = [
        'user_id',
        'api_key',
        'is_active',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}