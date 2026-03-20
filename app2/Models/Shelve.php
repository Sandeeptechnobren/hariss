<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames;
use Illuminate\Support\Str;
use App\Models\CompanyCustomer;
use App\Models\Salesman;

class Shelve extends Model
{
    use HasFactory, SoftDeletes, Blames;

    protected $table = 'shelves';

    protected $fillable = [
        'shelf_name',
        'height',
        'width',
        'depth',
        'valid_from',
        'valid_to',
        'customer_ids',
        'merchendiser_ids',
        'code',
        'uuid',
        'created_user',
        'updated_user',
        'deleted_user'
    ];

    protected $casts = [
    'customer_ids'     => 'array',
    'merchendiser_ids' => 'array',
    'valid_from'       => 'date',         
    'valid_to'     => 'date',        
    'created_at'   => 'datetime',    
    'updated_at'   => 'datetime',
    'deleted_at'   => 'datetime',
  ];

  protected static function boot()
    {
        parent::boot();

        static::creating(function ($shelve) {
            $shelve->uuid = Str::uuid()->toString();

            if (empty($shelve->code)) {
                $latestCode = self::orderBy('id', 'desc')->value('code');

                if ($latestCode && preg_match('/SHEL-(\d+)/', $latestCode, $matches)) {
                    $number = (int) $matches[1] + 1;
                } else {
                    $number = 1;
                }

                $shelve->code = 'SHEL-' . str_pad($number, 3, '0', STR_PAD_LEFT);
            }
        });
    }

       public function createdUser()
    {
        return $this->belongsTo(User::class, 'created_user', 'id');
    }

    public function updatedUser()
    {
        return $this->belongsTo(User::class, 'updated_user', 'id');
    }

    public function deletedUser()
    {
        return $this->belongsTo(User::class, 'deleted_user', 'id');
    }

    public function getCustomersAttribute()
    {
        return CompanyCustomer::whereIn('id', $this->customer_ids ?? [])->get();
    }

    public function getMerchandisersAttribute()
    {
        return Salesman::whereIn('id', $this->merchendiser_ids ?? [])->get();
    }

}
