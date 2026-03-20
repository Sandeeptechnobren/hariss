<?php

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AddChiller extends Model
{
    use SoftDeletes, Blames;

    protected $table = 'tbl_add_chillers';

    protected $fillable = [
        'uuid',
        'osa_code',
        'serial_number',
        'assets_category',
        'model_number',
        'is_assign',
        'acquisition',
        'vender',
        'manufacturer',
        'country_id',
        'customer_id',
        'warehouse_id',
        'assets_type',
        'sap_code',
        'status',
        'remarks',
        'branding',
        'trading_partner_number',
        'capacity',
        'manufacturing_year'
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vender');
    }
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
    public function assetsCategory()
    {
        return $this->belongsTo(AssetType::class, 'assets_category');
    }
    public function modelNumber()
    {
        return $this->belongsTo(AsModelNumber::class, 'model_number');
    }
    public function manufacture()
    {
        return $this->belongsTo(AssetManufacturer::class, 'manufacturer');
    }
    public function brand()
    {
        return $this->belongsTo(AssetBranding::class, 'branding');
    }
    public function customer()
    {
        return $this->belongsTo(AgentCustomer::class, 'customer_id');
    }
    public function fridgeStatus()
    {
        return $this->belongsTo(FridgeStatus::class, 'status');
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
    public function customerUpdate()
    {
        return $this->belongsTo(FrigeCustomerUpdate::class, 'serial_number', 'serial_no');
    }
}
