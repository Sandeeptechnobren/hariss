<?php

namespace App\Http\Resources\V1\Settings\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray($request)
    {
        // Fetch directly from role_has_permissions relationship
        $permissions = $this->rolePermissions
            ->map(function ($rp) {
                return [
                    'id' => $rp->id,
                    'permission_id' => $rp->permission_id,
                    'permission_name' => $rp->permission->name ?? null,
                    'menu' => $rp->menu ? [
                        'id' => $rp->menu->id,
                        'name' => $rp->menu->name,
                        'path' => $rp->menu->url ?? null,
                    ] : null,
                    'submenu' => $rp->submenu ? [
                        'id' => $rp->submenu->id,
                        'name' => $rp->submenu->name,
                        'path' => $rp->submenu->url ?? null,
                    ] : null,
                ];
            })->unique(function ($item) {
                return $item['permission_id'] . '-' .
                    ($item['menu_id'] ?? 'null') . '-' .
                    ($item['submenu_id'] ?? 'null');
            })
            ->values(); 

        return [
            'id' => $this->id,
            'name' => $this->name,
            'guard_name' => $this->guard_name,
            'permissions' => $permissions
        ];
    }
}
