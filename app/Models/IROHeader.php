<?php

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IROHeader extends Model
{
    use SoftDeletes, Blames;
    protected $table = 'tbl_iro_headers';


    protected $fillable = [
        'uuid',
        'osa_code',
        'crf_id',
        'warehouse_id',
        'name',
        'status',
        'created_at',
        'updated_at',
        'created_user',
        'updated_user',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_user');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_user')
            ->where('role', 92);
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function details()
    {
        return $this->hasMany(IRODetail::class, 'header_id');
    }
}
