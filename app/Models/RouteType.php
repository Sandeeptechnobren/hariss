<?php

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class RouteType extends Model
{
    use SoftDeletes, Blames;

    protected $table = 'route_types';

    protected $fillable = [
        'route_type_code',
        'route_type_name',
        'status',
        'created_user',
        'updated_user',
        'deleted_user'
    ];

    public $timestamps = false;
    const DELETED_AT = 'deleted_date';

    protected $dates = ['deleted_date'];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_user');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_user');
    }
}
