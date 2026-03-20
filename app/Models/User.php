<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'username',
        'contact_number',
        'password',
        'profile_picture',
        'role',
        'status',
        'street',
        'city',
        'zip',
        'dob',
        'is_block',
        'country_id',
        'company',
        'warehouse',
        'route',
        'item_id',
        'salesman',
        'region',
        'area',
        'outlet_channel',
        'created_by',
        'updated_user',
        'Created_Date',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $casts = [
        'company'           => 'array',
        'warehouse'         => 'array',
        'item_id'         => 'array',
        'route'             => 'array',
        'region'            => 'array',
        'area'              => 'array',
        'outlet_channel'    => 'array',
        'salesman'          => 'array',
        'email_verified_at' => 'datetime',
        'Modifier_Date'     => 'datetime',
        'Login_Date'        => 'datetime',
        'Created_Date'      => 'datetime',
        'is_block' => 'boolean',
    ];
public function roleDetails()
    {
        return $this->belongsTo(Role::class, 'role');
    }
public function roleData()
    {
        return $this->belongsTo(Role::class, 'role');
    }
public function countryId()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
public function getCompanyDetails(){
        return $this->belongsTo(Company::class,'company');
    }
public function getCompaniesFull()
    {
        $companyIds = $this->company ?? [];
        if (!is_array($companyIds)) {$companyIds = [$companyIds];}
        $companyIds = array_filter($companyIds, function ($id) {return !is_null($id) && $id !== '';});
        return count($companyIds)? \App\Models\Company::whereIn('id', $companyIds)->select('id', 'company_code', 'company_name', 'selling_currency')->get(): collect();
    }
public function getWarehousesFull()
    {
        $warehouseIds = $this->warehouse ?? [];
        if (!is_array($warehouseIds)) {$warehouseIds = [$warehouseIds];}
        $warehouseIds = array_filter($warehouseIds, function ($id) {
            return !is_null($id) && $id !== '';
        });
        return count($warehouseIds) ? \App\Models\Warehouse::whereIn('id', $warehouseIds)->select('id', 'warehouse_code', 'warehouse_name')->get() : collect();
    }

public function getitem()
    {
        $itemIds = $this->item_id ?? [];
        if (!is_array($itemIds)) {
            $itemIds = [$itemIds];
        }
        $itemIds = array_filter($itemIds, function ($id) {
            return !is_null($id) && $id !== '';
        });
        return count($itemIds) ? \App\Models\Item::whereIn('id', $itemIds)->select('id', 'code', 'name')->get() : collect();
    }

public function getRoutesFull()
    {
        $routeIds = $this->route ?? [];
        if (!is_array($routeIds)) {
            $routeIds = [$routeIds];
        }
        $routeIds = array_filter($routeIds, function ($id) {
            return !is_null($id) && $id !== '';
        });
        return count($routeIds) ? \App\Models\Route::whereIn('id', $routeIds)->select('id', 'route_code', 'route_name')->get() : collect();
    }

public function getSalesmenFull()
    {
        $salesmanIds = $this->salesman ?? [];
        if (!is_array($salesmanIds)) {
            $salesmanIds = [$salesmanIds];
        }
        $salesmanIds = array_filter($salesmanIds, function ($id) {return !is_null($id) && $id !== '';});
        return count($salesmanIds) ? \App\Models\Salesman::whereIn('id', $salesmanIds)->select('id', 'osa_code', 'name')->get() : collect();
    }

public function getRegionsFull()
    {
        $regionIds = $this->region ?? [];
        if (!is_array($regionIds)) {
            $regionIds = [$regionIds];
        }
        $regionIds = array_filter($regionIds, function ($id) {
            return !is_null($id) && $id !== '';
        });
        return count($regionIds) ? \App\Models\Region::whereIn('id', $regionIds)->select('id', 'region_code', 'region_name')->get() : collect();
    }

    public function getAreasFull()
    {
        $areaIds = $this->area ?? [];
        if (!is_array($areaIds)) {
            $areaIds = [$areaIds];
        }
        $areaIds = array_filter($areaIds, function ($id) {
            return !is_null($id) && $id !== '';
        });
        return count($areaIds) ? \App\Models\Area::whereIn('id', $areaIds)->select('id', 'area_code', 'area_name')->get() : collect();
    }

    public function getOutletChannelsFull()
    {
        $outletChannelIds = $this->outlet_channel ?? [];
        if (!is_array($outletChannelIds)) {
            $outletChannelIds = [$outletChannelIds];
        }
        $outletChannelIds = array_filter($outletChannelIds, function ($id) {
            return !is_null($id) && $id !== '';
        });
        return count($outletChannelIds) ? \App\Models\OutletChannel::whereIn('id', $outletChannelIds)->select('id', 'outlet_channel_code', 'outlet_channel')->get() : collect();
    }
    public function userRole()
    {
        return $this->belongsTo(Role::class, 'role', 'id');
    }
}
