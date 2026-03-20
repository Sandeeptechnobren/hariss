<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends SpatieRole
{
    use HasFactory;
    protected $fillable = [
        'name',
        'guard_name',
        'permissions',
        'labels',
        'status'
    ];

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(RoleHasPermission::class, 'role_id')
            ->with(['permission', 'menu', 'submenu']); // eager load
    }

    // Get labels as objects
    public function labelObjects()
    {
        if (!$this->labels) {
            return collect();
        }
        $labelIds = array_map('intval', explode(',', $this->labels));
        return Label::whereIn('id', $labelIds)->get();
    }
}
