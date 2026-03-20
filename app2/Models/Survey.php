<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames;

class Survey extends Model
{
    use HasFactory, SoftDeletes,Blames;

    protected $table = 'surveys';

    protected $fillable = [
        'survey_name',
        'start_date',
        'end_date',
        'status',
        'survey_code',
        'uuid',
        'created_user', 'updated_user', 'deleted_user'
    ];

    protected $casts  = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
        'deleted_at'  => 'datetime',
    ];

    public function getStatusLabelAttribute(): string
    {
        return $this->status == '1' ? 'active' : 'inactive';
    }

     public function createdUser()
    {
        return $this->belongsTo(User::class, 'created_user', 'id');
    }

    // Updated user relation
    public function updatedUser()
    {
        return $this->belongsTo(User::class, 'updated_user', 'id');
    }

    // Deleted user relation
    public function deletedUser()
    {
        return $this->belongsTo(User::class, 'deleted_user', 'id');
    }
}