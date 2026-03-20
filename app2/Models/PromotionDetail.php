<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames;
use Illuminate\Support\Str;

class PromotionDetail extends Model
{
     use SoftDeletes, Blames;
    protected $table = 'promotion_details';
    protected $fillable = [
    'header_id',
    'lower_qty',
    'upper_qty',
    'free_qty',
    'uuid',
];
  protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    // Relation to PromotionHeader
    public function header()
    {
        return $this->belongsTo(PromotionHeader::class, 'header_id', 'id');
    }
}
