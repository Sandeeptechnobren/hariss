<?php

namespace App\Models\Claim_Management\Web;
use App\Models\Warehouse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\Blames;

class PetitClaim extends Model
{
    use HasFactory, SoftDeletes, Blames;

    protected $table = 'tbl_petit_claims';

    protected $fillable = [
        'uuid',
        'osa_code',
        'claim_type',
        'warehouse_id',
        'petit_name',
        'fuel_amount',
        'rent_amount',
        'agent_amount',
        'month_range',
        'year',
        'status',
        'approver_id',
        'action_date',
        'customercare_id',
        'care_actiondate',
        'care_comment',
        'reject_reason',
        'claim_file',
        'created_user',
        'updated_user',
        'deleted_user'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
}
