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
use Illuminate\Support\Str;


class ReturnHeader extends Model
{
    use HasFactory, SoftDeletes,  Blames;
    protected $table = 'return_header';

    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

protected static function boot()
{
    parent::boot();

    static::creating(function ($model) {
        if (empty($model->uuid)) {
            $model->uuid = (string) \Illuminate\Support\Str::uuid();
        }
        if (empty($model->osa_code)) {
            $lastRecord = static::orderBy('id', 'desc')->first();

            if ($lastRecord && preg_match('/RETNHD-(\d+)/', $lastRecord->osa_code, $matches)) {
                $lastNumber = (int) $matches[1];
            } else {
                $lastNumber = 0;
            }

            $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
            $model->osa_code = 'RETNHD-' . $nextNumber;
        }
    });
}

    protected $fillable = [
        'uuid',
        'osa_code',
        'currency',
        'country_id',
        'order_id',
        'invoice_id',
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
        'uuid',
        'status',
    ];

    protected $casts = [
        'uuid' => 'string',
        'gross_total' => 'float',
        'vat' => 'float',
        'net_amount' => 'float',
        'total' => 'float',
        'discount' => 'float',
        'status' => 'integer',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function order()
    {
        return $this->belongsTo(AgentOrderHeader::class, 'order_id','id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function delivery()
    {
        return $this->belongsTo(AgentDeliveryHeaders::class, 'delivery_id');
    }
    public function invoice()
    {
        return $this->belongsTo(InvoiceHeader::class, 'invoice_id');
    }
    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id');
    }

    public function customer()
    {
        return $this->belongsTo(AgentCustomer::class, 'customer_id','id');
    }

    public function salesman()
    {
        return $this->belongsTo(Salesman::class, 'salesman_id');
    }

     public function details()
    {
        return $this->hasMany(ReturnDetail::class, 'header_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by'); 
    }

    public function updatedBy()
    { 
        return $this->belongsTo(User::class, 'updated_by');
    }

}