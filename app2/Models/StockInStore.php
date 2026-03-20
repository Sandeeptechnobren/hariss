<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames;
use Illuminate\Support\Str;

class StockInStore extends Model
{
    use HasFactory, SoftDeletes,  Blames;

    protected $table = 'stock_in_store';

    protected $fillable = [
        'code',
        'uuid',
        'activity_name',
        'date_from',
        'date_to',
        'assign_customers',
    ];

    protected $casts = [
        'assign_customers' => 'array', 
        'date_from' => 'date',
        'date_to' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(CompanyCustomer::class,'id');
    }
        public function inventories()
    {
        return $this->hasMany(AssignInventory::class, 'header_id', 'id');
    }
    // public function createdUser()
    // {
    //     return $this->belongsTo(User::class, 'created_user', 'id');
    // }

    // // Updated user relation
    // public function updatedUser()
    // {
    //     return $this->belongsTo(User::class, 'updated_user', 'id');
    // }

    // // Deleted user relation
    // public function deletedUser()
    // {
    //     return $this->belongsTo(User::class, 'deleted_user', 'id');
    // }
    /**
     * Automatically generate UUID and code if not set
     */
protected static function boot()
{
    parent::boot();

    static::creating(function ($stock) {
        if (empty($stock->uuid)) {
            $stock->uuid = (string) Str::uuid();
        }

        if (empty($stock->code)) {
            $latestCode = self::where('code', 'like', 'STK%')
                ->orderBy('code', 'desc')
                ->value('code');

            if ($latestCode) {
                $lastNumber = (int) substr($latestCode, 3);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }

            $stock->code = 'STK' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }
    });
}
}