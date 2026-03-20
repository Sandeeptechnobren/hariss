<?php

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class SubMenu extends Model
{
    use HasFactory, SoftDeletes,  Blames;

    protected $table = 'sub_menu';

    protected $fillable = [
        'uuid',
        'osa_code',
        'name',
        'menu_id',
        'parent_id',
        'url',
        'display_order',
        'action_type',
        'is_visible',
        'created_user',
        'updated_user',
        'deleted_user',
    ];


    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    public function parent()
    {
        return $this->belongsTo(SubMenu::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(SubMenu::class, 'parent_id');
    }
    
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_user');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_user');
    }
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_user');
    }
}
