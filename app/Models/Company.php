<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;   // <--- import SoftDeletes

class Company extends Model
{
    use HasFactory, SoftDeletes;   // <--- add SoftDeletes

    protected $table = 'tbl_company';

    protected $fillable = [
        'company_code',
        'company_name',
        'email',
        'tin_number',
        'vat',
        'country_id',
        'selling_currency',
        'purchase_currency',
        'toll_free_no',
        'logo',
        'website',
        'service_type',
        'company_type',
        'status',
        'module_access',
        'city',
        'address',
        // 'street',
        // 'landmark',
        // 'region',
        // 'sub_region',
        'primary_contact',
    ];

    protected $casts = [
        'module_access' => 'array',
    ];

    // Autogenerate company code
    public static function generateCode()
    {
        $latest = self::withTrashed()->latest('id')->first();
        $number = $latest ? $latest->id + 1 : 1;
        return 'CMP' . str_pad($number, 2, '0', STR_PAD_LEFT);
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }    

    // public function getregion() 
    // {
    //     return $this->belongsTo(Region::class, 'region');
    // }

    // public function getarea()
    // {
    //     return $this->belongsTo(Area::class,'sub_region');
    // }
}
