<?php

namespace App\Models\Hariss_Transaction\Web;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\Blames;
use App\Models\CompanyCustomer;
use App\Models\Company;
use App\Models\Warehouse;
use App\Models\Driver;

class HtReturnHeader extends Model
{
    use HasFactory, Blames, SoftDeletes;

    protected $table = 'ht_return_header';

    protected $fillable = [
        'uuid',
        'return_code',
        'customer_id',
        'vat',
        'net',
        'amount',
        'driver_id',
        'truck_no',
        'contact_no',
        'sap_id',
        'message',
        'company_id',
        'warehouse_id',
        'delivery_date',
        'status',
        'turnman',
        'return_no',
        'order_code',
        'total',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    public function details()
    {
        return $this->hasMany(HtReturnDetail::class, 'header_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(CompanyCustomer::class, 'customer_id', 'id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }
}
