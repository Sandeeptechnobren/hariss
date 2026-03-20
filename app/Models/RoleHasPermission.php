<?php

namespace App\Models;

use App\Traits\Blames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleHasPermission extends Model
{
    use Blames;

    protected $table = 'role_has_permissions';

    protected $fillable = [
        'role_id',
        'permission_id',
        'menu_id',
        'submenu_id',
    ];

    public function permission()
    {
        return $this->belongsTo(\Spatie\Permission\Models\Permission::class, 'permission_id');
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    public function submenu()
    {
        return $this->belongsTo(SubMenu::class, 'submenu_id');
    }
}
