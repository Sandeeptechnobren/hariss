<?php

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IRODetail extends Model
{
    use SoftDeletes;  // ADD THIS
    protected $table = 'tbl_iro_details';

    protected $fillable = [
        'header_id',
        'osa_code',
        'customer_id',
        'crf_id',
        'status',
        'warehouse_id',
        'created_date'
    ];

    public $timestamps = false;

    /**
     * Reverse relation: detail belongs to header
     */
    public function header()
    {
        return $this->belongsTo(IROHeader::class, 'header_id');
    }

    public function chillerRequest()
    {
        return $this->belongsTo(ChillerRequest::class, 'crf_id', 'id');
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
    public function customer()
    {
        return $this->belongsTo(AgentCustomer::class, 'customer_id');
    }
    public function modelInfo()
    {
        return $this->belongsTo(AsModelNumber::class, 'model_id');
    }
}
