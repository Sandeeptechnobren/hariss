<?php

namespace App\Models;

use App\Traits\Blames;
use App\Models\Warehouse;
use App\Models\ProjectList;
use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Salesman extends Model
{
    use SoftDeletes, Blames;

    protected $table = 'salesman';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'osa_code',
        'name',
        'type',
        'sub_type',
        'designation',
        'route_id',
        'username',
        'password',
        'contact_no',
        'warehouse_id',
        'email',
        'status',
        'is_take',
        'location',
        'forceful_login',
        'is_block',
        'reason',
        'block_date_to',
        'block_date_from',
        'created_user',
        'cashier_description_block',
        'invoice_block',
        'company_id',
    ];

    protected $casts = [
        'block_date_from' => 'date',
        'block_date_to' => 'date',
    ];
    protected $hidden = [
        'route',
        'route_name'
    ];
    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id');
    }
    public function salesmanType()
    {
        return $this->belongsTo(SalesmanType::class, 'type', 'id');
    }
    public function subtype()
    {
        return $this->belongsTo(ProjectList::class, 'sub_type', 'id');
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }
    public function getWarehousesDataAttribute()
    {
        if (empty($this->warehouse_id)) {
            return null;
        }
        $ids = array_filter(
            array_map('intval', explode(',', $this->warehouse_id)),
            fn($id) => $id > 0
        );
        return Warehouse::whereIn('id', $ids)
            ->with([
                'locationRelation:id,name',
                'companyRelation:id,selling_currency,purchase_currency',
            ])
            ->get([
                'id',
                'warehouse_code',
                'warehouse_name',
                'location',
                'company',
                'tin_no',
                'is_efris',
                'owner_number',
                'warehouse_manager_contact',
            ]);
    }
    protected $appends = ['route_name'];
    public function getRouteNameAttribute()
    {
        return $this->route ? $this->route->route_name : null;
    }
}
