<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames;
use App\Models\Agent_Transaction\InvoiceHeader;

class PromotionHeader extends Model
{
    use SoftDeletes, Blames;

    protected $table = 'tbl_promotion_headers';

    protected $fillable = [
        'uuid',
        'osa_code',
        'promotion_name',
        'promotion_type',
        'bundle_combination',
        'from_date',
        'to_date',
        'status',

        // 'offer_item_id',
        // 'offer_uom',
        'sales_team_type',
        'project_list',
        'uom',
        'items',
        'item_category',
        'location',
        'customer',
        'key_location',
        'key_customer',
        'key_item',
        // 'percentage_item_id',
        // 'percentage_item_category',
        // 'percentage',
        'created_user',
        'updated_user',
        'deleted_user',
    ];

    protected $dates = ['deleted_at'];

    public function promotionDetails()
    {
        return $this->hasMany(
            PromotionDetail::class,
            'header_id',
            'id'
        );
    }

    public function offerItems()
    {
        return $this->hasMany(PromotionOfferItem::class, 'promotion_header_id');
    }

    public function promotionalSlabs()
    {
        return $this->hasMany(
            PromotionalSlab::class,
            'promotion_header_id',
            'id'
        );
    }

    public function freeItems()
    {
        return $this->hasMany(PromotionOfferItem::class, 'promotion_header_id', 'id');
    }

    public function slabs()
    {
        return $this->hasMany(PromotionalSlab::class, 'promotion_header_id');
    }
     public function promotion_data()
    {
        return $this->belongsTo(InvoiceHeader::class, 'promotion_id');
    }
    public function invoices()
{
    return $this->hasMany(InvoiceHeader::class, 'promotion_id');
}
}
