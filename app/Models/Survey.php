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
        'survey_type',
        'merchandisher_id',
        'customer_id',
        'asset_id',
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
    public function questions()
    {
        return $this->hasMany(SurveyQuestion::class, 'survey_id', 'id');
    }
public function getMerchandishersAttribute()
    {
        $ids = $this->merchandisher_id
            ? explode(',', $this->merchandisher_id)
            : [];
        return Salesman::whereIn('id', $ids)
            ->select('id', 'name', 'osa_code')
            ->get();
    }
public function getCustomersAttribute()
    {
        $ids = $this->customer_id
            ? explode(',', $this->customer_id)
            : [];

        return CompanyCustomer::whereIn('id', $ids)
            ->select('id', 'business_name', 'osa_code')
            ->get();
    }
public function getAssetsAttribute()
    {
        $ids = $this->asset_id
            ? explode(',', $this->asset_id)
            : [];
        return AddChiller::whereIn('id', $ids)
            ->select('id', 'serial_number', 'osa_code')
            ->get();
    }
}