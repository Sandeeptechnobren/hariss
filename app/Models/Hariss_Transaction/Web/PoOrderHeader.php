<?php

namespace App\Models\Hariss_Transaction\Web;

use App\Models\Hariss_Transaction\Web\PoOrderDetail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\Blames;
use App\Models\CompanyCustomer;
use App\Models\Salesman;
use Illuminate\Support\Facades\DB;
use App\Models\Warehouse;
use App\Models\Company;

class PoOrderHeader extends Model
{
    use HasFactory, SoftDeletes, Blames;

    protected $table = 'ht_po_order_header';

    protected $fillable = [
        'uuid',
        'customer_id',
        'delivery_date',
        'comment',
        'order_code',
        'status',
        'created_user',
        'updated_user',
        'deleted_user',
        'currency',
        'country_id',
        'salesman_id',
        'gross_total',
        'pre_vat',
        'discount',
        'net',
        'total',
        'order_flag',
        'excise',
        'vat',
        'po_id',
        'sap_id',
        'sap_msg',
        'order_date',
        'company_id',
        'warehouse_id',
    ];
protected $casts = [
    'order_date'    => 'date',
    'delivery_date' => 'date',
];
protected static function boot()
{
    parent::boot();

    static::creating(function ($model) {

        if (empty($model->uuid)) {
            $model->uuid = Str::uuid()->toString();
        }

        if (empty($model->order_code)) {
            DB::beginTransaction();
            try {
                $prefix = 'POHD';  
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

                $model->order_code = $prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

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
        return $this->hasMany(PoOrderDetail::class, 'header_id');
    }

    public function customer()
    {
        return $this->belongsTo(CompanyCustomer::class, 'customer_id');
    }

    public function salesman()
    {
        return $this->belongsTo(Salesman::class, 'salesman_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
