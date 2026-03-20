<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames;
use Illuminate\Support\Str; 
use App\Models\Salesman;
use App\Models\CompanyCustomer;
use App\Models\Shelve;

class Planogram extends Model
{
     use HasFactory, SoftDeletes, Blames;

    protected $fillable = [
        'name',
        'uuid',
        'code',
        'valid_from',
        'valid_to',
        'merchendisher_id',
        'customer_id',
        'images',
    ];
        protected $casts = [
        'merchendisher_id' => 'array',
        'customer_id'      => 'array',
        'shelf_id'         => 'array',
    ];

     protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->code)) {
                $latestCode = self::orderBy('id', 'desc')->value('code');

                if ($latestCode) {
                    $lastNumber = (int) str_replace('PLN-', '', $latestCode);
                    $nextNumber = $lastNumber + 1;
                } else {
                    $nextNumber = 1;
                }
                $model->code = 'PLN-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function merchendisher()
    {
        return $this->belongsTo(Salesman::class, 'merchendisher_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(CompanyCustomer::class, 'customer_id', 'id');
    }

        public function planogramImages()
    {
        return $this->hasMany(PlanogramImage::class, 'planogram_id');
    }

    public function shelves()
    {
        return $this->belongsTo(Shelve::class, 'shelf_id', 'id');
    }
public function getMerchandishers()
{
    if (empty($this->merchendisher_id)) {
        return [];
    }

    $ids = is_array($this->merchendisher_id)
        ? $this->merchendisher_id
        : explode(',', $this->merchendisher_id);

    return Salesman::whereIn('id', $ids)
        ->select('id', 'name', 'osa_code')
        ->get();
}
public function getCustomers()
{
    if (empty($this->customer_id)) {
        return [];
    }

    $ids = is_array($this->customer_id)
        ? $this->customer_id
        : explode(',', $this->customer_id);

    return CompanyCustomer::whereIn('id', $ids)
        ->select('id', 'business_name', 'osa_code')
        ->get();
}
}
