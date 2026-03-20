<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames; 
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class Tier extends Model
{
    use HasFactory,SoftDeletes, Blames;
    protected $table = 'tbl_tiers';

    protected $fillable = [
        'uuid',
        'osa_code',
        'name',
        'period',
        'minpurchase',
        'maxpurchase',
        'period_category',
        'expiry_period',
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
                $prefix = 'TER';
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

}