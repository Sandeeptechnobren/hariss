<?php

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class WarehouseStock extends Model
{
    use HasFactory, SoftDeletes, Blames;
    protected $table = 'tbl_warehouse_stocks';
    protected $fillable = [
        'uuid',
        'osa_code',
        'warehouse_id',
        'item_id',
        'qty',
        'status',
        'create_user',
        'updated_user',
        'deleted_user'
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
