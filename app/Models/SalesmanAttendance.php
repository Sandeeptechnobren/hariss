<?php

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
// use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesmanAttendance extends Model
{
      use SoftDeletes, Blames;

    protected $table = 'salesman_attendance';

    protected $fillable = [
        'uuid',
        'salesman_id',
        'route_id',
        'warehouse_id',
        'time_in',
        'latitude_in',
        'longitude_in',
        'in_img',
        'time_out',
        'latitude_out',
        'longitude_out',
        'out_img',
        'attendance_date',
        'check_in',
        'check_out',
        'created_user',
        'updated_user',
        'deleted_user'

    ];
     protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }
}
