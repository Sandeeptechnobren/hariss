<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockInStore extends Model
{
    use HasFactory, SoftDeletes,  Blames;

    protected $table = 'stock_in_store';

    protected $fillable = [
        'id',
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
// public function customer()
//     {
//         return $this->belongsTo(CompanyCustomer::class, 'assign_customers', 'id');
//     }
        public function inventories()
    {
        return $this->hasMany(AssignInventory::class, 'header_id', 'id');
    }
        public function posts()
    {
        return $this->hasMany(StockInStorePost::class, 'stock_id', 'id');
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
    static::creating(function ($model) {
        if (empty($model->uuid)) {
            $model->uuid = (string) Str::uuid();
        }
        if (empty($model->code)) {
            DB::transaction(function () use ($model) {
                $prefix = 'STK';
                $year   = now()->year;
                $counter = DB::table('code_counters')
                    ->where('prefix', $prefix)
                    ->where('year', $year)
                    ->lockForUpdate()
                    ->first();
                if (!$counter) {
                    DB::table('code_counters')->insert([
                        'prefix'        => $prefix,
                        'year'          => $year,
                        'current_value' => 1,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                    $nextNumber = 1;
                } else {
                    $nextNumber = $counter->current_value + 1;
                    DB::table('code_counters')
                        ->where('id', $counter->id)
                        ->update([
                            'current_value' => $nextNumber,
                            'updated_at'    => now(),
                        ]);
                }
                $model->code = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            });
        }
    });
}

}