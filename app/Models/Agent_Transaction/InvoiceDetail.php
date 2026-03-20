<?php

namespace App\Models\Agent_Transaction;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Item;
use App\Models\Approver;
use App\Models\Agent_Transaction\InvoiceHeader;
use App\Models\ItemUOM ;
use App\Models\Uom ;
use App\Models\PromotionHeader;
use App\Models\PricingDetail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\Blames;
use Illuminate\Support\Str;

class InvoiceDetail extends Model
{
    use HasFactory, SoftDeletes,Blames;

    protected $table="invoice_details";
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';


   protected $fillable = [
        'uuid',
        'header_id',
        'item_id',
        'uom',
        'quantity',
        'itemvalue',
        'vat',
        'pre_vat',
        'net_total',
        'item_total',
        'promotion_id',
        'parent',
        'approver_id',
        'approved_date',
        'rejected_by',
        'rm_approver_id',
        'rm_reject_id',
        'rmaction_date',
        'comment_for_rejection',
        'status',
    ];


    protected $casts = [
        'uuid' => 'string',
        'header_id' => 'integer',
        'item_id' => 'integer',
        'uom' => 'integer',
        'quantity' => 'float',
        'itemvalue' => 'float',
        'vat' => 'float',
        'pre_vat' => 'float',
        'net_total' => 'float',
        'item_total' => 'float',
        'promotion_id' => 'integer',
        'parent' => 'integer',
        'approver_id' => 'integer',
        'approved_date' => 'datetime',
        'rejected_by' => 'integer',
        'rm_approver_id' => 'integer',
        'rm_reject_id' => 'integer',
        'rmaction_date' => 'datetime',
        'status' => 'integer',
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
        return $this->belongsTo(InvoiceHeader::class, 'header_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function itemuom()
    {
        return $this->belongsTo(ItemUOM::class, 'uom','id');
    }
    public function uoms()
    {
        return $this->belongsTo(Uom::class, 'uom','id');
    }

    public function promotion()
    {
        return $this->belongsTo(PromotionHeader::class, 'promotion_id');
    }

    public function approver()
    {
        return $this->belongsTo(Approver::class, 'approver_id');
    }

    public function parentDetail()
    {
        return $this->belongsTo(InvoiceDetail::class, 'parent');
    }

    public function childDetails()
    {
        return $this->hasMany(InvoiceDetail::class, 'parent');
    }

    public function itemprice()
    {
       return $this->belongsTo(PricingDetail::class,'item_id','item_id');
    }
        public function itemUoms()
    {
        return $this->belongsTo(ItemUOM::class, 'item_id', 'item_id')
            ->where('uom_id', $this->uom);
    }
}
