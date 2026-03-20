<?php

namespace App\Models\Agent_Transaction;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Traits\Blames;
use App\Models\Bank;
use App\Models\CompanyCustomer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvancePayment extends Model
{
    use HasFactory,SoftDeletes,Blames;

    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $table = 'advance_payments';

    protected $fillable = [
        'uuid',
        'osa_code',
        'payment_type',
        'companybank_id',
        'amount',
        'recipt_no',
        'recipt_date',
        'recipt_image',
        'cheque_no',
        'cheque_date',
        'agent_id',
        'status',
    ];

       protected $casts = [
        'uuid' => 'string',
        'recipt_date' => 'date',
        'amount' => 'float',
        'cheque_date' => 'date',
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
                $prefix = 'ADVPAY'; 
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
                $model->osa_code = $prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        }
    });
}

    public function companyBank()
    {
        return $this->belongsTo(Bank::class, 'companybank_id');
    }

    public function agent()
    {
        return $this->belongsTo(CompanyCustomer::class, 'agent_id','id');
    }
}