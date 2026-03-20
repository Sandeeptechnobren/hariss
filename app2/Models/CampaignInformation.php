<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames;
use Illuminate\Support\Str;


class CampaignInformation extends Model
{  
    use SoftDeletes, Blames;
    protected $table = 'campaign_informations';
    protected $fillable = [
        'uuid',
        'code',
        'date_time',
        'merchandiser_id',
        'customer_id',
        'feedback',
        'images',
    ];

    protected $casts = [
        'images' => 'array',
    ];

     protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->code)) {
                $model->code = self::generateNextCode();
            }
        });
    }

    private static function generateNextCode(): string
    {
        $lastCode = self::select('code')
            ->where('code', 'like', 'CAMPIN-%')
            ->orderBy('id', 'desc')
            ->value('code');

        if ($lastCode) {
            $number = (int) str_replace('CAMPIN-', '', $lastCode);
            $number++;
        } else {
            $number = 1;
        }
        return 'CAMPIN-' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

        public function merchandiser()
    {
        return $this->belongsTo(Salesman::class, 'merchandiser_id');
    }

    public function customer()
    {
        return $this->belongsTo(CompanyCustomer::class, 'customer_id');
    }
}