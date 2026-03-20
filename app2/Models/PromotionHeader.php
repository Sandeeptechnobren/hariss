<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames;


class PromotionHeader extends Model
{
       use SoftDeletes, Blames;
       protected $table = 'promotion_headers';
      protected $fillable = [
        'uuid',
        'key_combination',
        'promotion_name',
        'description',
        'from_date',
        'to_date',
        'warehouse_ids',
        'manager_ids',
        'projects_id',
        'included_customer_id',
        'excluded_customer_ids',
        'assignment_uom',
        'qualification_uom',
        'outlet_channel_id',
        'customer_category_id',
        'bought_item_ids',
        'bonus_item_ids',
        'status',
    ];

        public function customerCategory()
    {
        return $this->belongsTo(CustomerCategory::class, 'customer_category_id', 'id');
    }
        public function promotionDetails()
    {
        return $this->hasMany(PromotionDetail::class, 'header_id');
    }
}
