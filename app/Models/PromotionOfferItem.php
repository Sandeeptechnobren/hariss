<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames;

class PromotionOfferItem extends Model
{
    use SoftDeletes, Blames;

    /**
     * Table name
     */
    protected $table = 'tbl_promotional_items_detail';

    /**
     * Primary key
     */
    protected $primaryKey = 'id';

    /**
     * Mass assignable columns
     */
    protected $fillable = [
        'promotion_header_id',

        // Offer item fields
        'offer_item_id',
        'uom',

        // Percentage-based fields
        'percentage_item_id',
        'percentage_category_id',
        'percentage_uom',

        // Audit fields
        'created_user',
        'updated_user',
        'deleted_user',
    ];

    /**
     * Timestamp handling
     */
    public $timestamps = true;

    /**
     * Date fields
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /* -------------------------------------------------
     | Relationships
     |--------------------------------------------------
     */

    /**
     * Promotion Header relation
     */
    public function promotionHeader()
    {
        return $this->belongsTo(
            PromotionHeader::class,
            'promotion_header_id',
            'id'
        );
    }

    /**
     * Offer Item relation (if item master exists)
     */
    public function offerItem()
    {
        return $this->belongsTo(
            Item::class,
            'offer_item_id',
            'id'
        );
    }
  
      public function item()
    {
        return $this->belongsTo(Item::class, 'offer_item_id', 'id');
    }

    public function uomMaster()
    {
        return $this->belongsTo(Uom::class, 'uom', 'id');
    }
    /**
     * Percentage Item relation
     */
    public function percentageItem()
    {
        return $this->belongsTo(
            Item::class,
            'percentage_item_id',
            'id'
        );
    }

    /**
     * Percentage Category relation
     */
    public function percentageCategory()
    {
        return $this->belongsTo(
            ItemCategory::class,
            'percentage_category_id',
            'id'
        );
    }

    /* -------------------------------------------------
     | Query Scopes (Recommended)
     |--------------------------------------------------
     */

    /**
     * Active records only
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Filter by promotion header
     */
    public function scopeByPromotion($query, $promotionHeaderId)
    {
        return $query->where('promotion_header_id', $promotionHeaderId);
    }
}
