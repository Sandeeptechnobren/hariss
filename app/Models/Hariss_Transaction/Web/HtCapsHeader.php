<?php

namespace App\Models\Hariss_Transaction\Web;

use App\Models\Hariss_Transaction\Web\HtCapsDetail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\Blames;
use Illuminate\Support\Facades\DB;
use App\Models\Warehouse;
use App\Models\Driver;

class HtCapsHeader extends Model
{
    use HasFactory, SoftDeletes, Blames;

    protected $table = 'ht_caps_header';

    protected $fillable = [
        'uuid',
        'osa_code',
        'warehouse_id',
        'driver_id',
        'truck_no',
        'contact_no',
        'claim_no',
        'claim_date',
        'claim_amount',
        'status',
    ];

protected static function boot()
{
    parent::boot();

    static::creating(function ($model) {

        if (empty($model->uuid)) {
            $model->uuid = Str::uuid()->toString();
        }

        if (empty($model->osa_code)) {
            DB::beginTransaction();
            try {
                $prefix = 'CAPSH';  
                $year   = now()->year;

                $counter = DB::table('code_counters')
                    ->where('prefix', $prefix)
                    ->where('year', $year)
                    ->lockForUpdate()
                    ->first();

                if (!$counter) {
                    DB::table('code_counters')->insert([
                        'prefix'        => $prefix,
                        'current_value' => 1,
                        'year'          => $year,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);

                    $nextNumber = 1;

                } else {

                    $nextNumber = $counter->current_value + 1;

                    DB::table('code_counters')
                        ->where('id', $counter->id)
                        ->update([
                            'current_value' => $nextNumber,
                            'updated_at'    => now(),
                        ]);
                }

                $model->osa_code = $prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

                DB::commit();

            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        }
    });
}

    public function details()
    {
        return $this->hasMany(HtCapsDetail::class, 'header_id');
    }

    public function driverinfo()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

}
