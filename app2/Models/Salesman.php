<?php

// namespace App\Models;

// use App\Traits\Blames;
// use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

// class Salesman extends Model
// {
//     use SoftDeletes, Blames;

//     protected $table = 'salesman';
//     protected $primaryKey = 'id';
//     public $timestamps = false;

//     protected $fillable = [
//         'uuid',
//         'osa_code',
//         'name',
//         'type',
//         'designation',
//         'route_id',
//         'username',
//         'password',
//         'contact_no',
//         'warehouse_id',
//         'email',
//         'status',
//         'forceful_login',
//         'is_block',
//         'is_block_reason',
//         'block_date_to',
//         'block_date_from',
//         'created_user',
//         // 'is_login',
//         // 'sub_type',
//         // 'security_code',
//         // 'device_no',
//         // 'salesman_role',
//         // 'token_no',
//         // 'sap_id',
//     ];

//     protected $casts = [
//         'block_date_from' => 'date',
//         'block_date_to' => 'date',
//     ];

//     // Relationships
//     public function route()
//     {
//         return $this->belongsTo(Route::class, 'route_id');
//     }
//     // Salesman.php
//     public function salesmanType()
//     {
//         return $this->belongsTo(SalesmanType::class, 'type', 'id');
//     }


//     public function warehouse()
//     {
//         return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
//     }

//         public function type()
//     {
//         return $this->belongsTo(SalesmanType::class, 'type', 'id');
//     }
// }

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Warehouse;
use App\Models\Route;
use App\Models\SalesmanType; 
use Laravel\Passport\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Salesman extends Authenticatable
{
    use SoftDeletes, Blames,HasApiTokens;

    protected $table = 'salesman';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'osa_code',
        'name',
        'type',
        'designation',
        'route_id',
        'username',
        'password',
        'contact_no',
        'warehouse_id',
        'email',
        'status',
        'forceful_login',
        'is_block',
        'reason',
        'block_date_to',
        'block_date_from',
        'created_user',
        'cashier_description_block',
        'invoice_block',
        // 'is_login',
        // 'sub_type',
        // 'security_code',
        // 'device_no',
        // 'salesman_role',
        // 'token_no',
        // 'sap_id',
    ];

    protected $casts = [
        'block_date_from' => 'date',
        'block_date_to' => 'date',
    ];

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id');
    }
    public function salesmanType()
    {
        return $this->belongsTo(SalesmanType::class, 'type', 'id');
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id','id');
    }
    public function type()
    {
        return $this->belongsTo(SalesmanType::class, 'type', 'id');
    }
}