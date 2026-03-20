<?php

namespace App\Models\Hariss_Transaction\Web;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\Blames;
use App\Models\Item;
use App\Models\Uom;

use Illuminate\Support\Facades\DB;

class TempReturnD extends Model
{
    use HasFactory,Blames,SoftDeletes;

    protected $table = 'temp_return_details';

    protected $fillable = [
        'uuid',
        'header_id',
        'posnr',
        'item_id',
        'item_value',
        'vat',
        'uom',
        'qty',
        'net',
        'total',
        'expiry_batch',
        'batchno',
        'actual_expiry_date',
        'remark',
        'invoice_sap_id',
    ];

    public function header()
    {
        return $this->belongsTo(TempReturnH::class, 'header_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class, 'uom', 'id');
    }
}
