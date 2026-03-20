<?php

namespace App\Models;
use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscountDetail extends Model
{
    use SoftDeletes,Blames;

    protected $table = 'tbl_discount_details';

    protected $fillable = [
        'header_id',
        'item_id',
        'category_id',
        'uom',
        'percentage',
        'amount',
        'created_user',
        'updated_user'
    ];

    public function header()
    {
        return $this->belongsTo(DiscountHeader::class, 'header_id');
    }
}
