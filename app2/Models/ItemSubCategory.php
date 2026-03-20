<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemSubCategory extends Model
{
    use HasFactory, SoftDeletes;


    protected $table = 'item_sub_categories';

    protected $primaryKey = 'id';

    protected $fillable = [
        'category_id',
        'sub_category_code',
        'sub_category_name',
        'status',
        'created_user',
        'updated_user',
    ];

    public $timestamps = false;
    protected $casts = [
        'created_date' => 'datetime',
        'updated_date' => 'datetime',
        'deleted_at'   => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }
    public function createdBy(){
        return $this->belongsTo(User::class,'created_user');
    }
    public function updatedBy(){
        return $this->belongsTo(User::class,'created_user');
    }
}
