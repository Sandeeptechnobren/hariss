<?php

namespace App\Models;


use App\Traits\Blames;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;


class IRDetail extends Model
{

    use SoftDeletes, Blames;
    protected $table = 'tbl_ir_details';

    protected $fillable = [
        'uuid',
        'header_id',
        'fridge_id',
        'agreement_id',
        'crf_id',
        'created_user',
        'updated_user',
        'deleted_user',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public $timestamps = false;

    public function header()
    {
        return $this->belongsTo(IRHeader::class, 'header_id', 'id');
    }
    public function fridge()
    {
        return $this->belongsTo(AddChiller::class, 'fridge_id', 'id');
    }
    public function crf()
    {
        return $this->belongsTo(ChillerRequest::class, 'crf_id', 'id');
    }
}
