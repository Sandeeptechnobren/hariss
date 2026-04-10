<?php

namespace App\Models\Hariss_Transaction\Web;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\CompanyCustomer;
use App\Models\Warehouse;
use App\Models\Salesman;


class CreditNoteHeader extends Model
{
    protected $table = 'credit_note_headers';

    protected $fillable = [
        'uuid',
        'credit_note_no',
        'purchase_invoice_id',
        'supplier_id',
        'customer_id',
        'salesman_id',
        'distributor_id',
        'total_amount',
        'reason',
        'status',
    ];

    // 🔥 Auto UUID generate
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function details()
    {
        return $this->hasMany(CreditNoteDetail::class, 'credit_note_id');
    }

    public function purchaseInvoice()
    {
        return $this->belongsTo(HTInvoiceHeader::class, 'purchase_invoice_id');
    }

    public function supplier()
    {
        return $this->belongsTo(HTInvoiceHeader::class, 'supplier_id');
    }
    public function customer()
    {
        return $this->belongsTo(CompanyCustomer::class, 'customer_id');
    }    

    public function salesman()
    {
        return $this->belongsTo(Salesman::class, 'salesman_id');
    }

    public function distributor()
    {
        return $this->belongsTo(Warehouse::class, 'distributor_id');
    }
    public function creditNoteDetails()
    {
        return $this->hasMany(CreditNoteDetail::class, 'credit_note_id');
    }
}