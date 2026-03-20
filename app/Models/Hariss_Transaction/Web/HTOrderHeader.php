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
use App\Models\Hariss_Transaction\Web\HTOrderDetail;

class HTOrderHeader extends Model
{
    use HasFactory, SoftDeletes, Blames;

    protected $table = 'ht_order_header';

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
        'vat',
        'discount',
        'net_amount',
        'total',
        'order_flag',
        'excise',
        'pre_vat',
        'warehouse_id',
        'company_id',
    ];

protected $casts = [
    'order_date' => 'date',
    'delivery_date' => 'date',
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
        return $this->hasMany(HTOrderDetail::class, 'header_id');
    }

    public function customer()
    {
        return $this->belongsTo(CompanyCustomer::class, 'customer_id','id');
    }

    public function countries()
    {
        return $this->belongsTo(Country::class, 'country_id ');
    }

    public function salesman()
    {
        return $this->belongsTo(Salesman::class, 'salesman_id');
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
