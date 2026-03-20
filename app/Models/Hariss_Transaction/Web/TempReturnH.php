<?php

namespace App\Models\Hariss_Transaction\Web;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\Blames;
use App\Models\CompanyCustomer;
use App\Models\Hariss_Transaction\Web\HtReturnHeader;

class TempReturnH extends Model
{
    use HasFactory,Blames,SoftDeletes;

    protected $table = 'temp_return_header';

    protected $fillable = [
        'uuid',
        'return_code',
        'customer_id',
        'vat',
        'net',
        'amount',
        'truckname',
        'truckno',
        'contactno',
        'sap_id',
        'message',
        'return_reason',
        'return_type',
        'parent_id',
    ];

    public function details()
    {
        return $this->hasMany(TempReturnD::class, 'header_id', 'id');
    }

        public function customer()
    {
        return $this->belongsTo(CompanyCustomer::class, 'customer_id', 'id');
    }

    public function parent()
    {
        return $this->belongsTo(HtReturnHeader::class, 'parent_id','id');
    }
}
