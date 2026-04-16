<?php
namespace App\Models\Hariss_Transaction\Web;

use Illuminate\Database\Eloquent\Model;
use App\Models\Item;

class CreditNoteDetail extends Model
{
    protected $table = 'credit_note_details';

    public $timestamps = false; // kyuki updated_at nahi hai

    protected $fillable = [
        'credit_note_id',
        'item_id',
        'qty',
        'price',
        'total',
        'batch_no',
    ];

    // 🔥 Relations

    public function header()
    {
        return $this->belongsTo(CreditNoteHeader::class, 'credit_note_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoiceHeader::class, 'purchase_invoice_id');
    }
}