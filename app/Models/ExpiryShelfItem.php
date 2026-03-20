<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames;

class ExpiryShelfItem extends Model
{
    use HasFactory, SoftDeletes,Blames;

    protected $table = 'expiry_shelf_items';

    protected $fillable = [
        'date',
        'merchandisher_id',
        'customer_id',        
        'item_id',
        'qty',
        'expiry_date',
        'shelf_id',
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