<?php

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Customer extends Model
{
    use HasFactory, SoftDeletes, Blames;

    protected $table = 'tbl_customer';

    protected $fillable = [
        'uuid',
        'osa_code',
        'name',
        'customer_type',
        'route_id', // ✅ added
        'street',
        'customersequence',
        'owner_name',
        'email',
        'language',
        'buyerType',
        'ura_address',
        'address_1',
        'address_2',
        'phone_1',
        'phone_2',
        'balance',
        'customer_category',
        'customer_sub_category',
        'outlet_channel_id',
        'pricingkey',
        'promotionkey',
        'authorizeditemgrpkey',
        'paymentmethod',
        'payment_type',
        'bank_name',
        'bank_account_number',
        'creditday',
        'salesopt',
        'returnsopt',
        'surveykey',
        'customertype',
        'callfrequency',
        'city',
        'state',
        'customerzip',
        'invoicepriceprint',
        'enablepromotrxn',
        'trn_no',
        'accuracy',
        'creditlimit',
        'expirylimit',
        'exprunningvalue',
        'barcode',
        'division',
        'price_survey_id',
        'allowchequecollection',
        'region_id',
        'area_id',
        'vat_no',
        'longitude',
        'latitude',
        'threshold_radius',
        'salesman_id',
        'status',
        'print_status',
        'guarantee_name',
        'guarantee_amount',
        'guarantee_from',
        'guarantee_to',
        'givencreditlimit',
        'qrcode_image',
        'qr_value', // ✅ lowercase fixed
        'qr_latitude',
        'qr_longitude',
        'qr_accuracy',
        'capital_invest',
        'sap_id',
        'dchannel_id',
        'last_updated_serial_no',
        'credit_limit_validity',
        'invoice_code',
        'fridge_id',
        'installation_date',
        'is_fridge_assign',
        'serial_number_temp',
        'created_user',
        'updated_user',
    ];



    public function salesman()
    {
        return $this->belongsTo(Salesman::class, 'salesman_id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id');
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function fridgeRelation()
    {
        return $this->belongsTo(AddChiller::class, 'fridge_id');
    }

    public function customerTypeRelation()
    {
        return $this->belongsTo(CustomerType::class, 'customer_type');
    }


    public function customerCategory()
    {
        return $this->belongsTo(CustomerCategory::class, 'customer_category');
    }

    public function customerSubCategory()
    {
        return $this->belongsTo(CustomerSubCategory::class, 'customer_sub_category');
    }

    public function outletChannel()
    {
        return $this->belongsTo(OutletChannel::class, 'outlet_channel_id');
    }

    public function customerType()
    {
        return $this->belongsTo(CustomerType::class, 'customer_type');
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class);
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class);
    }
}
