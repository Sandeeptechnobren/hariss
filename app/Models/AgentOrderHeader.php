<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentOrderHeader extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'sap_order_id',
        'order_number',
        'order_date',
        'delivery_date',
        'payment_term',
        'price_list_id',
        'currency',
        'gross_total',
        'excise',
        'vat',
        'pre_vat',
        'discount',
        'net_total',
        'total_amount',
        'order_status',
        'reject_reason',
        'order_comment',
        'sales_backoffice_comment',
        'signature_img',
        'sap_return_message',
        'is_delivered',
        'status',
        'created_by',
        'updated_by',
    ];

    // Relationships
    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function priceList()
    {
        return $this->belongsTo(PriceList::class, 'price_list_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
