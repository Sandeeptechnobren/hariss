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
        'shelf_id'
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

public function getMerchendisherIdAttribute($value)
{
    if (is_string($value)) {
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
    return $value ?? [];
}

public function getCustomerIdAttribute($value)
{
    if (is_string($value)) {
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
    return $value ?? [];
}

public function getShelfIdAttribute($value)
{
    if (is_string($value)) {
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
    return $value ?? [];
}
 public function getMerchandishers()
    {
        return Salesman::whereIn('id', $this->merchendisher_id ?? [])
            ->get()
            ->map(function ($merch) {
                return [
                    'id'   => $merch->id,
                    'name' => $merch->name ?? '',   // adjust attribute names as per your DB
                    'code' => $merch->osa_code ?? '',
                ];
            });
    }
 public function getCustomers()
    {
        return CompanyCustomer::whereIn('id', $this->customer_id ?? [])
            ->get()
            ->map(function ($cust) {
                return [
                    'id'            => $cust->id,
                    'owner_name'    => $cust->owner_name,
                    'business_name' => $cust->business_name,
                    'customer_code' => $cust->customer_code,
                ];
            });
    }

    public function getShelves()
    {
        return Shelve::whereIn('id', $this->shelf_id ?? [])
            ->get()
            ->map(function ($shelf) {
                return [
                    'id'         => $shelf->id,
                    'shelf_name' => $shelf->shelf_name,
                    'code'       => $shelf->code,
                ];
            });
    }

}
