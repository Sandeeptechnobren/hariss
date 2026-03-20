<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockInStorePost extends Model
{
    use HasFactory;

    protected $table = 'stock_in_store_post';
    /**
     * Mass assignable fields
     */
    protected $fillable = [
        'uuid',
        'stock_id',
        'date',
        'salesman_id',
        'customer_id',
        'item_id',
        'good_salabale',
        'refill_qty',
        'out_of_stock',
        'fill_qty',
        'deleted_at',
    ];

    /**
     * Casts for proper data types
     */
    protected $casts = [
        'uuid' => 'string',
        'date' => 'date',
        // 'good_salabale' => 'numeric',
        'out_of_stock' => 'boolean',
        'refill_qty' => 'integer',
        'fill_qty' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    public function customer()
    {
        return $this->belongsTo(CompanyCustomer::class, 'customer_id', 'id');
    }
    public function stock()
    {
        return $this->belongsTo(StockInStore::class, 'stock_id', 'id');
    }
    public function salesman()
    {
        return $this->belongsTo(Salesman::class, 'salesman_id', 'id');
    }
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }
}
