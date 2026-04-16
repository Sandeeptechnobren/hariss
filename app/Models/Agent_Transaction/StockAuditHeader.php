<?php

namespace App\Models\Agent_Transaction;

use App\Models\Warehouse;

use Illuminate\Database\Eloquent\Model;

class StockAuditHeader extends Model
{
    protected $table = 'tbl_stockaudit_header';

    protected $fillable = [
        'uuid',
        'osa_code',
        'warehouse_id',
        'auditer_name',
        'case_otc_invoice',
        'otc_invoice',
        'negative_balance_date',
        'created_user',
        'updated_user'
    ];

    // ✅ Relation
    public function details()
    {
        return $this->hasMany(StockAuditDetail::class, 'header_id');
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
}
