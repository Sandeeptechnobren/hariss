<?php

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RouteVisit extends Model
{
    use HasFactory, SoftDeletes, Blames;

    protected $table = 'route_visit';

    protected $fillable = [
        'uuid',
        'osa_code',
        'header_id',
        'customer_type',
        'company_id',
        'region',
        'area',
        'warehouse',
        'route',
        'days',
        'from_date',
        'to_date',
        'status',
        'merchandiser_id',
        'customer_id',
        'created_user',
        'updated_user',
        'deleted_user',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
    ];

    public function header()
    {
        return $this->belongsTo(RouteVisitHeader::class, 'header_id');
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_user');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_user');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_user');
    }
    public function agentCustomer()
    {
        return $this->belongsTo(AgentCustomer::class, 'customer_id', 'id');
    }

    public function companyCustomer()
    {
        return $this->belongsTo(CompanyCustomer::class, 'customer_id', 'id');
    }


    public function merchandiser()
    {
        return $this->belongsTo(Salesman::class, 'merchandiser_id', 'id');
    }

    public function getCompanyIdsAttribute()
    {
        return $this->company_id ? array_map('intval', explode(',', $this->company_id)) : [];
    }

    public function getRegionIdsAttribute()
    {
        return $this->region ? array_map('intval', explode(',', $this->region)) : [];
    }

    public function getAreaIdsAttribute()
    {
        return $this->area ? array_map('intval', explode(',', $this->area)) : [];
    }

    public function getWarehouseIdsAttribute()
    {
        return $this->warehouse ? array_map('intval', explode(',', $this->warehouse)) : [];
    }

    public function getRouteIdsAttribute()
    {
        return $this->route ? array_map('intval', explode(',', $this->route)) : [];
    }

    public function getDaysListAttribute()
    {
        return $this->days ? explode(',', $this->days) : [];
    }
}
