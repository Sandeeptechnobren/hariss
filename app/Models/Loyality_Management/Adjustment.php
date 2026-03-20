<?php

namespace App\Models\Loyality_Management;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames; 
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\AgentCustomer;
use App\Models\Route;
use App\Models\Warehouse;

class Adjustment extends Model
{
    use HasFactory,SoftDeletes, Blames;
    protected $table = 'tbl_adjustment';

    protected $fillable = [
        'uuid',
        'osa_code',
        'warehouse_id',
        'route_id',
        'customer_id',
        'currentreward_points',
        'adjustment_points',
        'closing_points',
        'adjustment_symbol',
        'description',
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
                $prefix = 'ADJ';
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

public function warehouse()
{
    return $this->belongsTo(Warehouse::class, 'warehouse_id','id');
}

public function route()
{
    return $this->belongsTo(Route::class, 'route_id','id');
}

public function customer()
{
    return $this->belongsTo(AgentCustomer::class, 'customer_id','id');
}
}