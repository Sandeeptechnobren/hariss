<?php

namespace App\Models\Loyality_Management;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames; 
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\AgentCustomer;
use App\Models\Tier;

class CustomerLoyalityPoint extends Model
{
    use HasFactory,SoftDeletes, Blames;
    protected $table = 'customerloyality_points';

    protected $fillable = [
        'uuid',
        'osa_code',
        'customer_id',
        'total_earning',
        'total_spend',
        'total_closing',
        'tier_id',
    ];

    protected static function boot()
{
    parent::boot();
    static::creating(function ($model) {
        if (empty($model->uuid)) {
            $model->uuid = \Str::uuid()->toString();
        }
        if (empty($model->osa_code)) {
            DB::beginTransaction();
            try {
                $prefix = 'CSLOP';
                $year = now()->year;
                $counter = DB::table('code_counters')
                    ->where('prefix', $prefix)
                    ->where('year', $year)
                    ->lockForUpdate()
                    ->first();
                if (!$counter) {
                    DB::table('code_counters')->insert([
                        'prefix' => $prefix,
                        'current_value' => 1,
                        'year' => $year,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $nextNumber = 1;
                } else {
                    $nextNumber = $counter->current_value + 1;

                    DB::table('code_counters')
                        ->where('id', $counter->id)
                        ->update([
                            'current_value' => $nextNumber,
                            'updated_at' => now(),
                        ]);
                }
                $model->osa_code = $prefix . '-'. str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        }
    });
}

public function tier()
{
    return $this->belongsTo(Tier::class, 'tier_id','id');
}

public function customer()
{
    return $this->belongsTo(AgentCustomer::class, 'customer_id','id');
}

    public function details()
    {
        return $this->hasMany(CustomerLoyalityActivity::class, 'header_id');
    }
}