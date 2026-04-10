<?php

namespace App\Models\EfrisAPI;

use Illuminate\Database\Eloquent\Model;

class EfrisInvoiceFlag extends Model
{
    protected $table = 'tbl_efris_invoice_flags';

    protected $fillable = [
        'item_id',
        'warehouse_id',
        'invoice_id',
        'is_synced'
    ];
}
