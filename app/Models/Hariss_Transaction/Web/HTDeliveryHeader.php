<?php

namespace App\Models\Hariss_Transaction\Web;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\Blames;
use App\Models\CompanyCustomer;
use App\Models\Country;
use App\Models\Salesman;
use App\Models\Company;
use App\Models\Warehouse;
use App\Models\Hariss_Transaction\Web\PoOrderHeader;
use App\Models\Hariss_Transaction\Web\HTOrderHeader;

class HTDeliveryHeader extends Model
{
    use HasFactory, SoftDeletes, Blames;

    protected $table = 'ht_delivery_header';

    protected $fillable = [
        'uuid',
        'delivery_code',
        'customer_id',
        'currency',
        'country_id',
        'salesman_id',
        'gross_total',
        'discount',
        'vat',
        'pre_vat',
        'net',
        'excise',
        'total',
        'delivery_date',
        'comment',
        'status',
        'created_user',
        'updated_user',
        'deleted_user',
        'po_id',
        'order_id',
        'warehouse_id',
        'company_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = Str::uuid();
            }
        });
    }

    public function details()
    {
        return $this->hasMany(HTDeliveryDetail::class, 'header_id');
    }

    public function customer()
    {
        return $this->belongsTo(CompanyCustomer::class, 'customer_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id','id');
    }

    public function salesman()
    {
        return $this->belongsTo(Salesman::class, 'salesman_id');
    }

        public function order()
    {
        return $this->belongsTo(HTOrderHeader::class, 'order_id');
    }

    public function poorder()
    {
        return $this->belongsTo(PoOrderHeader::class, 'po_id');
    }

        public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

        public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
}
