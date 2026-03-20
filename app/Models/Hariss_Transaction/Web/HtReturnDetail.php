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

class HtReturnDetail extends Model
{
    use HasFactory,Blames,SoftDeletes;

    protected $table = 'ht_return_details';

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
        'return_type',
        'return_reason',
        'batch_no',
        'actual_expiry_date',
        'remark',
        'invoice_sap_id',
        'return_date',
    ];
    
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    public function header()
    {
        return $this->belongsTo(HtReturnHeader::class, 'header_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function uomdetails()
    {
        return $this->belongsTo(Uom::class, 'uom', 'id');
    }
}
