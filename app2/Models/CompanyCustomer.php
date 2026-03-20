<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyCustomer extends Model
{
    use HasFactory;

    protected $table = 'tbl_company_customer';

protected $fillable = [
    'sap_code',
    'customer_code',
    'business_name',
    'customer_type',
    'owner_name',
    'owner_no',
    'is_whatsapp',
    'whatsapp_no',
    'email',
    'language',
    'contact_no2',
    'buyerType',
    'road_street',
    'town',
    'landmark',
    'district',
    'balance',
    'payment_type',
    'bank_name',
    'bank_account_number',
    'creditday',
    'tin_no',
    'accuracy',
    'creditlimit',
    'guarantee_name',
    'guarantee_amount',
    'guarantee_from',
    'guarantee_to',
    'totalcreditlimit',
    'credit_limit_validity',
    'region_id',
    'area_id',
    'vat_no',
    'longitude',
    'latitude',
    'threshold_radius',
    'dchannel_id',
    'status',
    'created_user',
    'updated_user',
];
    public function getRegion(){
        return $this->belongsTo(Region::class,'region_id');
    }
    public function getArea(){
        return $this->belongsTo(Area::class,'area_id');
    }
    public function getOutletChannel(){
        return $this->belongsTo(OutletChannel::class,'dchannel_id','id');
    }
    public function customerType(){
        return $this->belongsTo(CustomerType::class,'customer_type');
    }
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }
    public function createdBy(){
        return $this->belongsTo(User::class,'created_user');
    }
    public function updatedBy(){
        return $this->belongsTo(User::class,'created_user');
    }


}
