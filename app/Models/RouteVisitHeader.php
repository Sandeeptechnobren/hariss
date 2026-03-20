<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RouteVisitHeader extends Model
{
    use SoftDeletes;

    protected $table = 'route_visit_headers';

    protected $primaryKey = 'id';

    protected $keyType = 'int';

    public $incrementing = true;

    protected $fillable = [
        'uuid',
        'osa_code',
        'created_user',
        'updated_user',
        'deleted_user',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 🔹 One Header → Many Route Visits
     */
    public function routeVisits()
    {
        return $this->hasMany(RouteVisit::class, 'header_id');
    }
}
