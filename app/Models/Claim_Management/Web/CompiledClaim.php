<?php

namespace App\Models\Claim_Management\Web;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\Warehouse;
use App\Traits\Blames;

class CompiledClaim extends Model
{
    use HasFactory, SoftDeletes, Blames;

    protected $table = 'tbl_compiled_claim';

    protected $fillable = [
        'uuid',
        'osa_code',
        'claim_period',
        'warehouse_id',

        'approved_qty_cse',
        'approved_claim_amount',
        'rejected_qty_cse',
        'rejected_amount',

        'area_sales_supervisor',
        'regional_sales_manager',

        'month_range',
        'promo_count',
        'promo_qty',
        'promo_amount',
        'reject_qty',
        'rejecte_amount',

        'agent_id',
        'agent_actiondate',
        'supervisor_id',
        'asm_actiondate',
        'manager_id',
        'manger_actiondate',
        'rejected_reason',

        'start_date',
        'end_date',
        'status',
        'verifier_id',
        'reject_comment',

        'created_user',
        'updated_user',
        'deleted_user',

        'asm_comment',
        'rm_comment',
        'agent_comment',
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
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }
}
