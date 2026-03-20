<?php

namespace App\Models\Agent_Transaction;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\Blames;
use App\Models\Warehouse;
use App\Models\Route;
use App\Models\AgentCustomer;
use App\Models\Salesman;
use App\Models\User;
use App\Models\Company;
use App\Models\Agent_Transaction\OrderHeader;
use App\Models\Agent_Transaction\AgentDeliveryHeaders;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;


class InvoiceHeader extends Model
{
    use HasFactory, SoftDeletes, Blames;
    protected $table = 'invoice_headers';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = \Str::uuid()->toString();
            }
            if (empty($model->invoice_code)) {
                DB::beginTransaction();
                try {
                    $prefix = 'INVHD';
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
                    $model->invoice_code = $prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
                    DB::commit();
                } catch (\Throwable $e) {
                    DB::rollBack();
                    throw $e;
                }
            }
        });
    }


    protected $fillable = [
        'uuid',
        'invoice_code',
        'warehouse_id',
        'company_id',
        'currency_id',
        'currency_name',
        'order_id',
        'delivery_id',
        'customer_id',
        'route_id',
        'salesman_id',
        'latitude',
        'longitude',
        'invoice_number',
        // 'invoice_mob_number',
        'ura_invoice_id',
        'ura_invoice_no',
        'ura_antifake_code',
        'ura_qr_code',
        'invoice_flag',
        'status',
        'invoice_date',
        'invoice_time',
        'gross_total',
        'vat',
        'pre_vat',
        'net_total',
        'promotion_id',
        'discount_id',
        'discount',
        'promotion_total',
        'total_amount',
        'purchaser_name',
        'purchaser_contact',
        'invoice_type',
    ];

    protected $casts = [
        'uuid' => 'string',
        'warehouse_id' => 'integer',
        'company_id' => 'integer',
        'currency_id' => 'integer',
        'customer_id' => 'integer',
        'route_id' => 'integer',
        'salesman_id' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'invoice_flag' => 'boolean',
        'status' => 'integer',
        'invoice_date' => 'date',
        'invoice_time' => 'datetime:H:i:s',
        'gross_total' => 'float',
        'vat' => 'float',
        'pre_vat' => 'float',
        'net_total' => 'float',
        'promotion_id' => 'integer',
        'discount_id' => 'integer',
        'discount' => 'float',
        'promotion_total' => 'float',
        'total_amount' => 'float',
    ];



    public function order()
    {
        return $this->belongsTo(OrderHeader::class, 'order_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function customer()
    {
        return $this->belongsTo(AgentCustomer::class, 'customer_id');
    }

    public function salesman()
    {
        return $this->belongsTo(Salesman::class, 'salesman_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function details()
    {
        return $this->hasMany(InvoiceDetail::class, 'header_id', 'id');
    }

    public function delivery()
    {
        return $this->belongsTo(AgentDeliveryHeaders::class, 'delivery_id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id');
    }

    public function returns()
    {
        return $this->hasMany(ReturnHeader::class, 'invoice_id');
    }
}
