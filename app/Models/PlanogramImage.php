<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames;
use Illuminate\Support\Str;

class PlanogramImage extends Model
{
   use SoftDeletes, Blames;

    protected $fillable = [
        'uuid',
        'merchandiser_id',
        'customer_id',
        'shelf_id',
        'image',
        'planogram_id'
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(CompanyCustomer::class, 'customer_id');
    }
    public function shelf()
    {
        return $this->belongsTo(Shelve::class);
    }
    public function merchandiser()
    {
        return $this->belongsTo(Salesman::class);
    }

        protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
