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
        'fridge_code',
        'serial_number',
        'asset_number',
        'model_number',
        'description',
        'acquisition',
        'vender_details',
        'manufacturer',
        'country_id',
        'type_name',
        'sap_code',
        'status',
        'is_assign',
        'customer_id',
        'agreement_id',
        'document_type',
        'document_id'
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vender_details');
    }
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}
