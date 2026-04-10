<?php

namespace App\Models\EfrisAPI;

use Illuminate\Database\Eloquent\Model;

class EfrisInvoiceLog extends Model
{
    protected $table = 'tbl_efris_invoice_logs';

    protected $fillable = [
        'invoice_id',
        'warehouse_id',
        'request_payload',
        'response_payload',
        'is_success',
        'error_message',
        'synced_at'
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array'
    ];
}
