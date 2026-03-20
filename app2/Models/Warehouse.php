<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tbl_warehouse';
    
    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';

    protected $fillable = [
        'warehouse_code',   
        'warehouse_type',
        'warehouse_name',
        'owner_name',
        'owner_number',
        'owner_email',
        'agreed_stock_capital',
        'location',
        'city',
        'warehouse_manager',
        'warehouse_manager_contact',
        'tin_no',
        'company',
        'warehouse_email',
        'region_id',
        'area_id',
        'latitude',
        'longitude',
        'agent_customer',
        'town_village',
        'street',
        'landmark',
        'is_efris',
        'p12_file',
        'password',
        'is_branch',
        'branch_id',
        'status',
        'created_user',
        'updated_user',


        // 'tin_no',
        // 'company_customer_id',
        // 'registation_no',
        // 'business_type',
        // 'warehouse_type',
        // 'address',
        // 'stock_capital',
        // 'deposite_amount',
        // 'district',
        // 'threshold_radius',
        // 'device_no',
        // 'invoice_sync',
    ];

    protected $casts = [
        'status' => 'integer',
        'region_id' => 'integer',
        'area_id' => 'integer',
        'threshold_radius' => 'decimal:2',
        'created_user' => 'integer',
        'updated_user' => 'integer',
    ];

    const DELETED_AT = 'deleted_date';  

    protected $dates = ['deleted_date'];

    // Relationships
    public function region()
    {
        return $this->belongsTo(Region::class,'region_id');
    }

    public function Area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_user');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_user');
    }
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('warehouse_type', $type);
    }
    public function getCompanyCustomer(){
        return $this->belongsTo(CompanyCustomer::class,'agent_customer');
    }
    public function getCompany(){
        return $this->belongsTo(Company::class,'company');
    }
}
