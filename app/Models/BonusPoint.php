<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames; 
use App\Models\Item;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class BonusPoint extends Model
{
    use HasFactory,SoftDeletes, Blames;
    protected $table = 'tbl_bonus';

    protected $appends = ['remaining_days', 'expiry_status'];
    protected $fillable = [
        'uuid',
        'osa_code',
        'item_id',
        'volume',
        'bonus_points',
        'reward_basis',
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
                $prefix = 'BNP';
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

    public function item()
{
    return $this->belongsTo(Item::class, 'item_id', 'id');
}

public function getRemainingDaysAttribute()
{
    if (!$this->expiry_date) {
        return null;
    }

    // negative = expiry passed
    return now()->diffInDays($this->expiry_date, false);
}

public function getExpiryStatusAttribute()
{
    return $this->is_expired == 1 ? 'Expired' : 'Active';
}

}