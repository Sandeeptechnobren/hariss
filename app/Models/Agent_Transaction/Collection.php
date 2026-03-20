<?php

namespace App\Models\Agent_Transaction;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Traits\Blames;
use Illuminate\Support\Facades\DB;
use App\Models\Agent_Transaction\InvoiceHeader;
use App\Models\Route;
use App\Models\Salesman;
use App\Models\AgentCustomer;
use App\Models\Warehouse;
use App\Models\User;

class Collection extends Model
{
    use HasFactory,SoftDeletes,Blames;

    protected $table = 'tbl_collections';

    protected $fillable = [
        'uuid',
        'invoice_id',
        'customer_id',
        'salesman_id',
        'route_id',
        'warehouse_id',
        'collection_no',
        'amount',
        'outstanding',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'outstanding' => 'decimal:2',
    ];

    protected static function boot()
{
    parent::boot();

    static::creating(function ($model) {
        if (empty($model->uuid)) {
            $model->uuid = \Str::uuid()->toString();
        }

        if (empty($model->collection_no)) {
            DB::beginTransaction();

            try {
                $prefix = 'COL'; 
                $year = now()->year;

                $counter = DB::table('code_counters')
                    ->where('prefix', $prefix)
                    ->where('year', $year)
                    ->lockForUpdate()
                    ->first();

                if (!$counter) {
                    DB::table('code_counters')->insert([
                        'prefix' => $prefix,
                        'current_value' => 1,
                        'year' => $year,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $nextNumber = 1;
                } else {
                    $nextNumber = $counter->current_value + 1;

                    DB::table('code_counters')
                        ->where('id', $counter->id)
                        ->update([
                            'current_value' => $nextNumber,
                            'updated_at' => now(),
                        ]);
                }

                $model->collection_no = $prefix . '-'. str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        }
    });
}

    public function invoice()
    {
        return $this->belongsTo(InvoiceHeader::class, 'invoice_id','id');
    }

    public function customer()
    {
        return $this->belongsTo(AgentCustomer::class, 'customer_id');
    }

    public function salesman()
    {
        return $this->belongsTo(Salesman::class, 'salesman_id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
}