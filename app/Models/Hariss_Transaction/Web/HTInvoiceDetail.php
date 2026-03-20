<?php

namespace App\Models\Hariss_Transaction\Web;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\Blames;
use App\Models\Item;
use App\Models\ItemUOM;
use App\Models\Uom;

class HTInvoiceDetail extends Model
{
    use HasFactory, SoftDeletes, Blames;

    protected $table = 'ht_invoice_detail';

    protected $fillable = [
        'uuid',
        'header_id',
        'item_id',
        'uom_id',
        'discount_id',
        'promotion_id',
        'parent_id',
        'item_price',
        'quantity',
        'discount',
        'gross_total',
        'promotion',
        'net',
        'excise',
        'pre_vat',
        'vat',
        'total',
        'approver_id',
        'approved_date',
        'rejected_by',
        'rm_approver_id',
        'rm_reject_id',
        'rmaction_date',
        'comment_for_rejection',
        'status',
        'created_user',
        'updated_user',
        'deleted_user',
        'item_category',
        'item_category_dll',
        'batch_number',
        'item_sap_id',
        'batch_expiry_date',
        'inv_position_no',
        'batch_manuf_date'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = Str::uuid();
            }
        });
    }

    public function header()
    {
        return $this->belongsTo(HTInvoiceHeader::class, 'header_id');
    }

     public function itemuom()
    {
        return $this->belongsTo(ItemUOM::class, 'uom_id');
    }
         public function uoms()
    {
        return $this->belongsTo(Uom::class, 'uom_id');
    }
    
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
