<?php

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class IRHeader extends Model
{
    use SoftDeletes, Blames;
    protected $table = 'tbl_ir_headers';

    protected $fillable = [
        'uuid',
        'iro_id',
        'osa_code',
        'salesman_id',
        'schedule_date',
        'status',
        'created_user',
        'updated_user',
        'deleted_user',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public $timestamps = false;

    public function details()
    {
        return $this->hasMany(IRDetail::class, 'header_id', 'id');
    }
    public function iroHeader()
    {
        return $this->belongsTo(IROHeader::class, 'iro_id');
    }
    public function salesman()
    {
        return $this->belongsTo(Salesman::class, 'salesman_id');
    }
}
