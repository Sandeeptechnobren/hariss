<?php

namespace App\Models;

use App\Traits\Blames;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BulkTransferRequest extends Model
{
    use SoftDeletes, Blames;

    protected $table = 'tbl_bulk_transfer_request';

    protected $fillable = [
        'uuid',
        'osa_code',
        'region_id',
        'area_id',
        'warehouse_id',
        'model_id',
        'requestes_asset',
        'available_stock',
        'created_user',
        'updated_user',
        'deleted_user',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];


    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_user');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_user');
    }
    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }
    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
    public function model_number()
    {
        return $this->belongsTo(AsModelNumber::class, 'model_id');
    }
}
