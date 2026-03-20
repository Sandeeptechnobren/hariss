<?php

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChillerRequest extends Model
{
    use SoftDeletes, Blames;

    protected $table = 'chiller_requests';

    protected $fillable = [
        'uuid',
        'osa_code',
        'owner_name',
        'contact_number',
        'customer_id',
        'warehouse_id',
        'outlet_id',
        'landmark',
        'agreement_id',
        'existing_coolers',
        'outlet_weekly_sale_volume',
        'display_location',
        'chiller_safty_grill',
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
        'stock_share_with_competitor',
        'salesman_id',
        'chiller_manager_id',
        'is_merchandiser',
        'status',
        'fridge_status',
        'iro_id',
        'remark',
        'created_user',
        'updated_user',
        'deleted_user',
    ];

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id');
    }

    public function salesman()
    {
        return $this->belongsTo(Salesman::class, 'salesman_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
    public function outlet()
    {
        return $this->belongsTo(OutletChannel::class, 'outlet_id');
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_user');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_user');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_user');
    }
}

