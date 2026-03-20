<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Blames;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ServiceVisit extends Model
{
    use SoftDeletes, Blames;

    protected $table = 'tbl_service_visit';

    protected $fillable = [
        'uuid',
        'osa_code',
        'ticket_type',
        'ticket_status',
        'time_in',
        'time_out',
        'ct_status',
        'model_no',
        'asset_no',
        'serial_no',
        'branding',
        'chiller_id',
        'scan_image',
        'outlet_code',
        'outlet_name',
        'owner_name',
        'landmark',
        'location',
        'town_village',
        'street',
        'district',
        'contact_no',
        'contact_no2',
        'contact_person',
        'longitude',
        'latitude',
        'technician_id',
        'is_machine_in_working',
        'cleanliness',
        'condensor_coil_cleand',
        'gaskets',
        'branding_no',
        'light_working',
        'branding_no',
        'propper_ventilation_available',
        'leveling_positioning',
        'stock_availability_in',
        'is_machine_in_working_img',
        'cleanliness_img',
        'condensor_coil_cleand_img',
        'gaskets_img',
        'light_working_img',
        'branding_no_img',
        'propper_ventilation_available_img',
        'leveling_positioning_img',
        'stock_availability_in_img',
        'cooler_image',
        'cooler_image2',
        'complaint_type',
        'comment',
        'cts_comment',
        'spare_part_used',
        'pending_other_comment',
        'any_dispute',
        'current_voltage',
        'amps',
        'cabin_temperature',
        'work_status',
        'wrok_status_pending_reson',
        'spare_request',
        'work_done_type',
        'spare_details',
        'type_details_photo1',
        'type_details_photo2',
        'technical_behavior',
        'service_quality',
        'customer_signature',
        'nature_of_call_id',
        'created_user',
        'updated_user',
        'deleted_user'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function technician()
    {
        return $this->belongsTo(Salesman::class, 'technician_id');
    }

    public function natureOfCall()
    {
        return $this->belongsTo(CallRegister::class, 'nature_of_call_id');
    }
    public function customer()
    {
        return $this->belongsTo(AgentCustomer::class, 'outlet_code', 'osa_code');
    }
    public function fridge()
    {
        return $this->belongsTo(AddChiller::class, 'serial_no', 'serial_number');
    }
}
