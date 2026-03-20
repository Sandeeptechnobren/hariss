<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames; 
use App\Models\Vehicle;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DeviceManagement extends Model
{
    use HasFactory,SoftDeletes, Blames;
    protected $table = 'device_managements';

    protected $fillable = [
        'uuid',
        'osa_code',
        'manufacturing_id',
        'device_name',
        'modelno',
        'IMEI_1',
        'IMEI_2',
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
                $prefix = 'DEVMN';
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

//     public function vehicle()
// {
//     return $this->belongsTo(Vehicle::class, 'vehicle', 'id');
// }

// public function deviceno()
// {
//     return $this->belongsTo(Salesman::class, 'merchendiser_id', 'id');
// }
}