<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames;

class ShelfItem extends Model
{
    use HasFactory, SoftDeletes,Blames;

    protected $table = 'shelf_items';

    protected $fillable = [
        'shelf_id',
        'product_id',
        'customer_id',
        'quantity',
        'status',
        'valid_from',
        'valid_to',
    ];

    // Dates for automatic casting
    protected $dates = [
        'valid_from',
        'valid_to',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

        public function shelf()
    {
        return $this->belongsTo(Shelve::class, 'shelf_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function customer()
    {
        return $this->belongsTo(CompanyCustomer::class, 'customer_id');
    }
}