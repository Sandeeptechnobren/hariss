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
use App\Models\Country;
use App\Models\User;
use App\Models\AgentOrderHeader;
use App\Models\Agent_Transaction\ExchangeInInvoice;
use App\Models\Agent_Transaction\ExchangeInReturn;
use Illuminate\Support\Str;

class ExchangeHeader extends Model
{
    use HasFactory,SoftDeletes,Blames;

    protected $table = 'exchange_headers';

    protected $fillable = [
        'uuid',
        'exchange_code',
        'currency',
        'country_id',
        'order_id',
        'delivery_id',
        'warehouse_id',
        'route_id',
        'customer_id',
        'salesman_id',
        'gross_total',
        'vat',
        'net_amount',
        'total',
        'discount',
        'status',
        'comment',
        'latitude',
        'longitude',
    ];

     protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }

            if (empty($model->exchange_code)) {
                $lastRecord = static::orderBy('id', 'desc')->first();

                if ($lastRecord && preg_match('/EXCHHD-(\d+)/', $lastRecord->exchange_code, $matches)) {
                    $lastNumber = (int) $matches[1];
                } else {
                    $lastNumber = 0;
                }

                $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
                $model->exchange_code = 'EXCHHD-' . $nextNumber;
            }
        });
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function delivery()
    {
        return $this->belongsTo(AgentDeliveryHeaders::class, 'delivery_id');
    }

    public function order()
    {
        return $this->belongsTo(AgentOrderHeader::class, 'order_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id');
    }

    public function customer()
    {
        return $this->belongsTo(AgentCustomer::class, 'customer_id');
    }

    public function salesman()
    {
        return $this->belongsTo(Salesman::class, 'salesman_id');
    }

    public function invoices()
    {
        return $this->hasMany(ExchangeInInvoice::class, 'header_id');
    }

    public function returns()
    {
        return $this->hasMany(ExchangeInReturn::class, 'header_id');
    }

        public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_user');
    }
    
   public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_user');
    }
    
}