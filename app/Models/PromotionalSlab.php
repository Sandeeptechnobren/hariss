<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromotionalSlab extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_promotional_slabs';

    protected $primaryKey = 'id';

    protected $fillable = [
        'promotion_header_id',
        'category',
        'item_id',
        'percentage',
        'created_user',
        'updated_user',
        'deleted_user',
    ];


    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];


    public $timestamps = true;

 
    public function promotionHeader()
    {
        return $this->belongsTo(
            PromotionHeader::class,
            'promotion_header_id',
            'id'
        );
    }


    public function categoryData()
    {
        return $this->belongsTo(
            ItemCategory::class,
            'category',
            'id'
        );
    }

    public function item()
    {
        return $this->belongsTo(
            Item::class,
            'item_id',
            'id'
        );
    }


    public function percentage()
    {
        return $this->belongsTo(
            PromotionPercentageDiscount::class,
            'percentage_id',
            'id'
        );
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }


    public function scopeByPromotion($query, $promotionHeaderId)
    {
        return $query->where('promotion_header_id', $promotionHeaderId);
    }
}
