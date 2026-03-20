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
use App\Models\Hariss_Transaction\Web\HTDeliveryHeader;

class HTInvoiceHeader extends Model
{
    use HasFactory, SoftDeletes, Blames;

    protected $table = 'ht_invoice_header';

    protected $fillable = [
        'uuid',
        'invoice_code',
        'company_id',
        'warehouse_id',
        'currency_id',
        'currency_name',
        'order_number',
        'delivery_number',
        'customer_id',
        'salesman_id',
        'latitude',
        'longitude',
        'ura_invoice_id',
        'ura_invoice_no',
        'ura_antifake_code',
        'ura_qr_code',
        'invoice_date',
        'invoice_time',
        'vat',
        'pre_vat',
        'net',
        'excise',
        'total',
        'purchaser_name',
        'purchaser_contact',
        'created_user',
        'updated_user',
        'deleted_user',
        'invoice_number',
        'status',
        'invoice_type',
        'po_id',
        'order_id',
        'delivery_id',
        'sap_id',
        'doc_type'
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
        return $this->hasMany(HTInvoiceDetail::class, 'header_id');
    }

    public function customer()
    {
        return $this->belongsTo(CompanyCustomer::class, 'customer_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id ');
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

        public function delivery()
    {
        return $this->belongsTo(HTDeliveryHeader::class, 'delivery_id');
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
