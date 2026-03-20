<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames;
use Illuminate\Support\Str;

class PromotionDetail extends Model
{
    use SoftDeletes, Blames;
    protected $table = 'tbl_promotion_details';


    protected $fillable = [
        'header_id',
        'from_qty',
        'to_qty',
        'free_qty',
        'offer_item_id',
        'offer_uom',
        'percentage_item_id',
        'percentage_item_category',
        'percentage',
        'created_user',
        'updated_user',
        'deleted_user',
    ];

    protected $dates = ['deleted_at'];

    public function promotionHeader()
    {
        return $this->belongsTo(
            PromotionHeader::class,
            'header_id',
            'id'
        );
    }

    public function offerItems()
    {
        return $this->hasMany(PromotionOfferItem::class, 'promotion_detail_id');
    }
    
}
