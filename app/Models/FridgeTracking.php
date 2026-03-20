<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames; 

class FridgeTracking extends Model
{
      use SoftDeletes, Blames;
    protected $table = 'tbl_fridge_tracking_report';

    protected $fillable = [
        'osa_code',
        'route_id',
        'salesman_id',
        'customer_id',
        'serial_no',
        'fridge_scan_tracking',
        'have_fridge',
        'image',
        'latitude',
        'longitude',
        'outlet_name',
        'outlet_location',
        'outlet_contact',
        'outlet_photo',
        'outlet_asm_id',
        'last_visit_time',
        'inform_asm',
        'cooller_condition',
        'complaint_type',
        'comments',
    ];
}