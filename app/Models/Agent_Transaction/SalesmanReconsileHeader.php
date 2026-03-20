<?php

namespace App\Models\Agent_Transaction;
use App\Models\Warehouse;
use App\Models\Salesman;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesmanReconsileHeader extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_salesman_reconsile_header';

    protected $fillable = [
        'uuid',
        'warehouse_id',
        'salesman_id',
        'reconsile_date',
        'grand_total_amount',
        'cash_amount',
        'credit_amount',
        'created_user',
        'updated_user',
        'deleted_user',
    ];

    protected $casts = [
        'reconsile_date'     => 'date',
        'grand_total_amount' => 'decimal:2',
        'cash_amount'        => 'decimal:2',
        'credit_amount'      => 'decimal:2',
    ];

    /**
     * Header → Details
     */
    public function details()
    {
        return $this->hasMany(
            SalesmanReconsileDetail::class,
            'header_id',
            'id'
        );
    }
    public function salesman()
    {
        return $this->belongsTo(
            Salesman::class,
            'salesman_id',
            'id'
        );
    }
    public function warehouse()
    {
        return $this->belongsTo(
            Warehouse::class,
            'warehouse_id',
            'id'
        );
    }
}
