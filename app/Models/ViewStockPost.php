<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames;

class ViewStockPost extends Model
{
    use HasFactory, SoftDeletes,Blames;

    protected $table = 'view_stock_post';

    protected $fillable = [
        'date',
        'merchandisher_id',
        'customer_id',        
        'item_id',
        'capacity',
        'good_salable',
        'out_of_stock',
        'shelf_id',
    ];

    // Dates for automatic casting
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function shelf()
    {
        return $this->belongsTo(Shelve::class, 'shelf_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id','id');
    }

    public function customer()
    {
        return $this->belongsTo(CompanyCustomer::class, 'customer_id');
    }
public function merchandisher()
    {
        return $this->belongsTo(Salesman::class, 'merchandisher_id');
    }
}