<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames;

class Agreement extends Model
{
    use SoftDeletes, Blames;

    protected $table = 'tbl_agreement';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'uuid',
        'osa_code',
        'ms',
        'ms_of',
        'address',
        'asset_number',
        'serial_number',
        'model_branding',

        'behaf_hariss_name_contact',
        'behaf_hariss_sign',
        'behaf_reciver_old_signature',
        'behaf_hariss_date',

        'behaf_reciver_name_contact',
        'behaf_reciver_sign',
        'behaf_reciver_date',

        'presence_sales_name',
        'presence_sales_contact',
        'presence_sign',

        'presence_lc_name',
        'presence_lc_contact',
        'presence_lc_sign',

        'presence_landloard_name',
        'presence_landloard_contact',
        'presence_landloard_sign',

        'salesman_id',
        'customer_id',
        'fridge_id',
        'ir_id',
        'add_chiller_id',

        'installed_img1',
        'installed_img2',
        'installed_img3',

        'created_user',
        'updated_user',
        'deleted_user'
    ];

    protected $casts = [
        'behaf_hariss_date'  => 'date',
        'behaf_reciver_date' => 'date',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
        'deleted_at'         => 'datetime',
    ];
}