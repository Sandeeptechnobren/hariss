<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerSubCategory extends Model
{
    protected $table = 'customer_sub_categories';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'customer_category_id',
        'customer_sub_category_code',
        'customer_sub_category_name',
        'status',
        'created_user',
        'updated_user'
    ];

    // Relationships
    public function CustomerCategory()
    {
        return $this->belongsTo(CustomerCategory::class, 'customer_category_id');
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
