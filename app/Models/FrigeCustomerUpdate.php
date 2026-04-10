<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use App\Traits\Blames;
use App\Models\OutletChannel;


class FrigeCustomerUpdate extends Model
{
    use HasFactory, Blames;

    protected $table = 'frige_customer_update';

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'uuid',
        'osa_code',
        'outlet_name',
        'owner_name',
        'contact_number',
        'landmark',
        'outlet_type',
        'existing_coolers',
        'outlet_weekly_sale_volume',
        'display_location',
        'chiller_safty_grill',
        'agent',
        'manager_sales_marketing',
        'national_id',
        'outlet_stamp',
        'model',
        'hil',
        'ir_reference_no',
        'installation_done_by',
        'date_lnitial',
        'date_lnitial2',
        'contract_attached',
        'machine_number',
        'brand',
        'asset_number',
        'lc_letter',
        'trading_licence',
        'password_photo',
        'outlet_address_proof',
        'chiller_asset_care_manager',

        'national_id_file',
        'password_photo_file',
        'outlet_address_proof_file',
        'trading_licence_file',
        'lc_letter_file',
        'outlet_stamp_file',
        'sign__customer_file',

        'national_id1_file',
        'password_photo1_file',
        'outlet_address_proof1_file',
        'trading_licence1_file',
        'lc_letter1_file',
        'outlet_stamp1_file',

        'sales_marketing_director',
        'warehouse_id',
        'area_manager',
        'name_contact_of_the_customer',
        'chiller_size_requested',
        'outlet_weekly_sales',
        'stock_share_with_competitor',
        'specify_if_other_type',
        'location',
        'postal_address',
        'customer_id',

        'sales_excutive',
        'salesman_id',
        'route_id',
        'sign_salesman_file',
        'serial_no',
        'fridge_scan_img',
        'fridge_office_id',
        'fridge_maanger_id',

        'status',
        'request_document_status',
        'agreement_id',
        'fridge_status',

        'remark',
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
        'uuid' => 'string',

        'manager_sales_marketing' => 'integer',
        'chiller_asset_care_manager' => 'integer',
        'agent_id' => 'integer',
        'salesman_id' => 'integer',
        'route_id' => 'integer',
        'fridge_office_id' => 'integer',
        'fridge_maanger_id' => 'integer',
        'status' => 'integer',
        'request_document_status' => 'integer',
        'agreement_id' => 'integer',
        'fridge_status' => 'integer',

        'created_date' => 'datetime',
    ];

    /**
     * Automatically generate UUID if not provided
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function salesman()
    {
        return $this->belongsTo(Salesman::class, 'salesman_id');
    }

    public function customer()
    {
        return $this->belongsTo(AgentCustomer::class, 'customer_id');
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id');
    }
    public function modelNumber()
    {
        return $this->belongsTo(AsModelNumber::class, 'model', 'id');
    }
    public function outletType()
    {
        return $this->belongsTo(OutletChannel::class, 'outlet_type', 'id',);
    }
    public function outletChannel()
    {
        return $this->belongsTo(OutletChannel::class, 'outlet_type');
    }
    public function fridges()
    {
        return $this->belongsTo(AddChiller::class, 'serial_no', 'serial_number');
    }
}
