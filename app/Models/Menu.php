<?php

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    use SoftDeletes, Blames;

    protected $fillable = [
        'uuid',
        'osa_code',
        'name',
        'icon',
        'url',
        'display_order',
        'is_visible',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function created_user()
    {
        return $this->belongsTo(User::class, 'created_user');
    }

    public function updated_user()
    {
        return $this->belongsTo(User::class, 'updated_user');
    }

    public function deleted_user()
    {
        return $this->belongsTo(User::class, 'deleted_user');
    }
}
