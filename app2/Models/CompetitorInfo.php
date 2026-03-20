<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames; 
use Illuminate\Support\Str;

class CompetitorInfo extends Model
{
      use SoftDeletes, Blames;
    protected $table = 'competitor_infos';

    protected $fillable = [
        'company_name',
        'brand',
        'merchendiser_id',
        'item_name',
        'price',
        'promotion',
        'notes',
        'image',
        'code',
        'uuid',
    ];

    protected $casts = [
    'image' => 'array',
]; 

    //     public function user()
    // {
    //     return $this->belongsTo(User::class);
    // }

protected static function boot()
{
    parent::boot();

    static::creating(function ($model) {
        if (empty($model->uuid)) {
            $model->uuid = \Str::uuid()->toString();
        }

        if (empty($model->code)) {
            $model->code = self::generateSequentialCode();
        }
    });
}

protected static function generateSequentialCode(): string
{
    $latestCode = self::where('code', 'like', 'COMPIN-%')
                      ->orderByDesc('id')
                      ->value('code');

    if ($latestCode) {
        $number = (int) str_replace('COMPIN-', '', $latestCode);
    } else {
        $number = 0;
    }

    $newNumber = $number + 1;
    $formatted = str_pad($newNumber, 3, '0', STR_PAD_LEFT);

    return "COMPIN-{$formatted}";
}

        public function merchandiser()
    {
        return $this->belongsTo(Salesman::class, 'merchendiser_id', 'id');
    }
}