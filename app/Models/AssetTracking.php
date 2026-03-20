<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames; 

class AssetTracking extends Model
{
      use SoftDeletes, Blames;
    protected $table = 'asset_tracking';

    protected $fillable = [
        'asset_code',
        'uuid',
        'image',
        'title',
        'description',
        'from_date',
        'to_date',
        'model_name',
        'barcode',
        'category',
        'location',
        'area',
        'worker',
        'additional_worker',
        'team',
        'vendors',
        'customer_id',
        'purchase_date',
        'placed_in_service',
        'purchase_price',
        'warranty_expiration',
        'residual_price', 
        'useful_life',
        'additional_information',
    ];
}