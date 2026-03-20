<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Traits\Blames;

class ItemCategory extends Model
{
    use HasFactory, SoftDeletes, Blames;

    protected $table = 'item_categories';

    protected $fillable = [
        'category_code',
        'category_name',
        'status',
        'created_user',
        'updated_user',
    ];

    // Use correct timestamps
    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';
    const DELETED_AT = 'deleted_date'; // if your table uses deleted_date

    protected $casts = [
        'created_date' => 'datetime',
        'updated_date' => 'datetime',
        'deleted_date' => 'datetime',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_user');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_user');
    }
}
