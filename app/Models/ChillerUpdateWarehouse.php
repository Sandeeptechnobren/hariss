<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames; 

class ChillerUpdateWarehouse extends Model
{
    use SoftDeletes, Blames;
    protected $table = 'updatewarehouse_chiller';

    protected $fillable = [
        'uuid',
        'fridge_id',
        'chiller_id',
        'chiller_request_id',
        'from_warehouse_id',
        'to_warehouse_id',
    ];
    protected $casts = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function fridge()
    {
        return $this->belongsTo(AddChiller::class, 'chiller_id','id');
    }

    public function chiller()
    {
        return $this->belongsTo(FrigeCustomerUpdate::class, 'fridge_id','id');
    }

    public function chillerrequest()
    {
        return $this->belongsTo(ChillerRequest::class, 'chiller_request_id','id');
    }    

}
