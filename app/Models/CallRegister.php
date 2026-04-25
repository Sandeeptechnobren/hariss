<?php

namespace App\Models;

use App\Traits\Blames;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CallRegister extends Model
{
    use SoftDeletes, Blames;

    protected $table = 'tbl_call_register';

    protected $fillable = [
        'uuid',
        'osa_code',
        'ticket_type',
        'ticket_date',
        'technician_id',
        'sales_valume',
        'ctc_status',
        'chiller_serial_number',
        'asset_number',
        'model_number',
        'chiller_code',
        'branding',
        'assigned_customer_id',
        // 'outlet_name',
        // 'owner_name',
        // 'road_street',
        // 'town',
        // 'landmark',
        // 'outlet_code',
        'customer_id',
        'fridge_id',
        // 'district',
        // 'contact_no1',
        // 'contact_no2',
        'current_outlet_code',
        'current_outlet_name',
        'current_owner_name',
        'current_road_street',
        'current_town',
        'current_landmark',
        'current_district',
        'current_contact_no1',
        'current_contact_no2',
        'current_warehouse',
        'current_asm',
        'current_rm',
        'nature_of_call',
        'follow_up_action',
        'followup_status',
        'status',
        'call_category',
        'reason_for_cancelled',
        'created_date',
        'completion_date',
        'created_user',
        'updated_user',
        'deleted_user'
    ];

    public $timestamps = true;

    protected $dates = ['deleted_at'];

    public function technician()
    {
        return $this->belongsTo(Salesman::class, 'technician_id');
    }
    public function assignedCustomer()
    {
        return $this->belongsTo(AgentCustomer::class, 'assigned_customer_id', 'id');
    }
    // public function currentCustomer()
    // {
    //     return $this->belongsTo(AgentCustomer::class, 'current_outlet_name', 'id');
    // }
    public function currentCustomer()
    {
        return $this->belongsTo(
            AgentCustomer::class,
            'current_outlet_code', // local column
            'osa_code'             // foreign column
        );
    }
    public function modelNumber()
    {
        return $this->belongsTo(AsModelNumber::class, 'model_number');
    }
    public function brand()
    {
        return $this->belongsTo(AssetBranding::class, 'branding');
    }
    public function asset()
    {
        return $this->belongsTo(AddChiller::class, 'chiller_serial_number', 'serial_number');
    }

    public function acf_data()
    {
        return $this->belongsTo(FrigeCustomerUpdate::class, 'chiller_serial_number', 'serial_no');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'current_warehouse');
    }
    public function serviceVisits()
    {
        return $this->hasMany(
            ServiceVisit::class,
            'nature_of_call_id', // foreign key in tbl_service_visit
            'id'                 // local key in tbl_call_register
        );
    }
    public function asm()
    {
        return $this->belongsTo(User::class, 'asm_id');
    }

    public function rm()
    {
        return $this->belongsTo(User::class, 'rm_id');
    }
}
