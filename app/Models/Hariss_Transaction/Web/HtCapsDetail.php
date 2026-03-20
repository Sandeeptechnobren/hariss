<?php

namespace App\Models\Hariss_Transaction\Web;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\Blames;
use Illuminate\Support\Facades\DB;
use App\Models\Item;
use App\Models\ItemUOM;
use App\Models\Uom;

class HtCapsDetail extends Model
{
    use HasFactory, SoftDeletes, Blames;

    protected $table = 'ht_caps_details';

    protected $fillable = [
        'uuid',
        'header_id',
        'osa_code',
        'item_id',
        'uom_id',
        'quantity',
        'receive_qty',
        'receive_amount',
        'receive_date',
        'remarks',
        'remarks2',
        'status',
    ];

protected static function boot()
{
    parent::boot();

    static::creating(function ($model) {

        if (empty($model->uuid)) {
            $model->uuid = Str::uuid()->toString();
        }

        if (empty($model->osa_code)) {
            DB::beginTransaction();
            try {
                $prefix = 'CAPSD';  
                $year   = now()->year;

                $counter = DB::table('code_counters')
                    ->where('prefix', $prefix)
                    ->where('year', $year)
                    ->lockForUpdate()
                    ->first();

                if (!$counter) {
                    DB::table('code_counters')->insert([
                        'prefix'        => $prefix,
                        'current_value' => 1,
                        'year'          => $year,
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

                $model->osa_code = $prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

                DB::commit();

            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        }
    });
}

    public function header()
    {
        return $this->belongsTo(HtCapsHeader::class, 'header_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function itemuom()
    {
        return $this->belongsTo(ItemUOM::class, 'uom_id');
    }
        public function uoms()
    {
        return $this->belongsTo(Uom::class, 'uom_id');
    }

}
