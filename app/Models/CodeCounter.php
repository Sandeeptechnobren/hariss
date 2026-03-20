<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodeCounter extends Model
{
    use HasFactory;

    protected $table = 'code_counters';

    protected $fillable = [
        'prefix',
        'current_value',
        'year',
    ];

    protected $casts = [
        'current_value' => 'integer',
        'year' => 'integer',
    ];
}
