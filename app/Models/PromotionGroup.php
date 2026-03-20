<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames;
use Illuminate\Support\Str;

class PromotionGroup extends Model
{
    use HasFactory, SoftDeletes, Blames;
    protected $table = 'promotiongroups';

    protected $fillable = [
        'uuid',
        'name',
        'item',
        'status',
        'osa_code',
    ];

    protected $attributes = [
        'status' => 1,
    ];

   protected static function boot()
    {
        parent::boot();

        static::creating(function ($promotion) {
            // Generate UUID
            $promotion->uuid = Str::uuid()->toString();

            // If osa_code not already set, generate it
            if (empty($promotion->osa_code)) {
                $latestCode = self::orderBy('id', 'desc')->value('osa_code');

                if ($latestCode && preg_match('/PROMGRP-(\d+)/', $latestCode, $matches)) {
                    $number = (int) $matches[1] + 1;
                } else {
                    $number = 1;
                }

                $promotion->osa_code = 'PROMGRP-' . str_pad($number, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function item()
{
    return $this->belongsToMany(Item::class, 'item', 'id');
}

}