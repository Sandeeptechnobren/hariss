<?php

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Location extends Model
{
    use SoftDeletes;

    protected $table = 'locations';

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'create_user',
        'update_user',
        'deleted_user',
    ];

    protected $casts = [
        'uuid' => 'string',
    ];

    // Hide internal user columns in JSON if desired (optional)
    // protected $hidden = ['create_user', 'update_user', 'deleted_user'];

    protected static function boot()
{
    parent::boot();

    static::creating(function ($model) {
        $model->uuid = $model->uuid ?? (string) Str::uuid();

        // ✅ Agar user ne code diya hai to wahi store karo
        if (!empty($model->code)) {
            return;
        }

        // ✅ Agar code nahi diya gaya, to auto generate karo
        $prefix = 'LOC';
        $latestCode = self::withTrashed()->orderBy('id', 'desc')->value('code');

        if ($latestCode && preg_match('/\d+$/', $latestCode, $m)) {
            $num = intval($m[0]) + 1;
        } else {
            $num = 1;
        }

        $model->code = $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
    });



        // set update_user on updating
        static::updating(function ($model) {
            if (auth()->check()) {
                $model->update_user = auth()->id();
            }
        });

        // set create_user on created
        static::created(function ($model) {
            if (auth()->check() && empty($model->create_user)) {
                $model->create_user = auth()->id();
                $model->saveQuietly();
            }
        });

        // set deleted_user on deleting (soft delete)
        static::deleting(function ($model) {
            if ($model->isForceDeleting()) {
                // permanent delete: no special handling
                return;
            }
            if (auth()->check()) {
                $model->deleted_user = auth()->id();
                $model->saveQuietly();
            }
        });
    }
}
