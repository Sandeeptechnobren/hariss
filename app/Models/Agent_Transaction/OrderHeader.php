<?php

namespace App\Models\Agent_Transaction;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Warehouse;
use App\Models\Route;
use App\Models\Salesman;
use App\Models\AgentCustomer;
use App\Traits\Blames;
use App\Models\User;
use App\Models\Country;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderHeader extends Model
{
    use HasFactory, SoftDeletes,Blames;

    protected $table = 'agent_order_headers';


    public function getRouteKeyName()
    {
        return 'uuid';
    }

    protected $fillable = [
        'uuid',
        'currency',
        'country_id',
        'order_code',
        'warehouse_id',
        'route_id',
        'customer_id',
        'salesman_id',
        'delivery_date',
        'gross_total',
        'vat',
        'net_amount',
        'total',
        'discount',
        'status',
        'order_flag',
        'comment',
        'latitude',
        'longitude',
        'created_user',
        'updated_user',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'gross_total' => 'decimal:2',
        'vat' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'discount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

 protected static function boot()
{
    parent::boot();

    static::creating(function ($model) {

        if (empty($model->uuid)) {
            $model->uuid = \Str::uuid()->toString();
        }

        if (empty($model->order_code)) {

            $prefix = 'ORD';
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

            $model->order_code =
                $prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }
    });
}

    public function details(): HasMany
    {
        return $this->hasMany(OrderDetail::class, 'header_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

        public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class, 'route_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(AgentCustomer::class, 'customer_id');
    }

    public function salesman(): BelongsTo
    {
        return $this->belongsTo(Salesman::class, 'salesman_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_user');
    }
}
