<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class CustomerCategory extends Model
{
    protected $table = 'customer_categories';
    protected $primaryKey = 'id';
    public $timestamps = false;
    use SoftDeletes;

    protected $fillable = [
        'outlet_channel_id',
        'customer_category_code',
        'customer_category_name',
        'status',
        'created_user',
        'updated_user'
    ];
    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';
    
    protected $dates = [
        'created_date',
        'updated_date',
        'deleted_at', 
    ];

    // Relationships
    public function outletChannel()
    {
        return $this->belongsTo(OutletChannel::class, 'outlet_channel_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_user');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_user');
    }
}
