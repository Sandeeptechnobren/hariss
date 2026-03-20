<?php

namespace App\Http\Resources\V1\Settings\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray($request)
    {
        $menus = $this->rolePermissions
            ->groupBy('menu_id') // Group by menu
            ->map(function ($menuGroup) {
                $menu = optional($menuGroup->first()->menu);

                // Group by submenu under each menu
                $submenus = $menuGroup
                    ->groupBy('submenu_id')
                    ->map(function ($submenuGroup) {
                        $submenu = optional($submenuGroup->first()->submenu);

                        // Aggregate all permissions for this submenu
                        $permissions = $submenuGroup
                            ->map(function ($rp) {
                                return [
                                    'permission_id' => $rp->permission_id,
                                    'permission_name' => $rp->permission->name ?? null,
                                ];
                            })
                            ->unique('permission_id')
                            ->values();

                        return [
                            'id' => $submenu->id,
                            'name' => $submenu->name,
                            'path' => $submenu->url ?? null,
                            'permissions' => $permissions,
                        ];
                    })
                    ->values(); // make it a clean array

                return [
                    'id' => $menu->id,
                    'menu' => [
                        'id' => $menu->id,
                        'name' => $menu->name,
                        'path' => $menu->url ?? null,
                    ],
                    'submenu' => $submenus,
                ];
            })
            ->values();
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'labels' => LabelResource::collection($this->labelObjects()),
            'menus' => $menus,
        ];
    }
}
