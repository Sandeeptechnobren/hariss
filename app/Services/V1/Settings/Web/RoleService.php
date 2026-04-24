<?php

namespace App\Services\V1\Settings\Web;

use App\Models\Role;
use App\Models\RoleHasPermission;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Support\Facades\Auth;


class RoleService
{
    public function listRoles($perPage = 50, array $filters = [])
    {
        try {
            $query = Role::with([
                'rolePermissions.permission',
                'rolePermissions.menu',
                'rolePermissions.submenu'
            ])->where('id', '!=', 1)->orderByDesc('id');
            foreach ($filters as $field => $value) {
                if (!empty($value)) {
                    if (in_array($field, ['name', 'guard_name'])) {
                        $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                    } else {
                        $query->where($field, $value);
                    }
                }
            }
            return $query->paginate($perPage);
        } catch (\Throwable $e) {
            Log::error("Failed to fetch roles", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception("Failed to fetch roles. Please try again.");
        }
    }


    public function getDropdownRole()
    {
        try {
            return Role::query()
                ->select(['id', 'name'])
                ->orderBy('id', 'asc')
                ->get();
        } catch (\Throwable $e) {
            Log::error("Failed to fetch roles", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \Exception("Failed to fetch roles. Please try again.");
        }
    }


    public function findRole(int $roleId)
    {
        try {
            $role = Role::with([
                'rolePermissions.permission',
                'rolePermissions.menu',
                'rolePermissions.submenu'
            ])->findOrFail($roleId);

            return $role;
        } catch (Throwable $e) {
            Log::error("Failed to fetch role", [
                'role_id' => $roleId,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception("Role not found.");
        }
    }

    public function getAllData()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                throw new \Exception("Unauthenticated.");
            }

            $role = Role::with([
                'rolePermissions.permission',
                'rolePermissions.menu',
                'rolePermissions.submenu'
            ])->findOrFail($user->role);

            return $role;
        } catch (Throwable $e) {
            // dd($e);
            Log::error("Failed to fetch role", [
                'user_id' => optional(Auth::user())->id,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception("Role not found.");
        }
    }


    // public function assignPermissionsWithMenu(int $roleId, array $data): array
    // {
    //     DB::beginTransaction();

    //     try {
    //         RoleHasPermission::where('role_id', $roleId)->delete();

    //         foreach ($data['permissions'] ?? [] as $permission) {
    //             $permissionId = $permission['permission_id'] ?? null;
    //             $menus = $permission['menus'] ?? [];

    //             if (empty($permissionId)) {
    //                 continue;
    //             }

    //             if (empty($menus)) {
    //                 RoleHasPermission::create([
    //                     'role_id'       => $roleId,
    //                     'permission_id' => $permissionId,
    //                     'menu_id'       => null,
    //                     'submenu_id'    => null,
    //                 ]);
    //                 continue;
    //             }

    //             foreach ($menus as $menu) {
    //                 RoleHasPermission::create([
    //                     'role_id'       => $roleId,
    //                     'permission_id' => $permissionId,
    //                     'menu_id'       => $menu['menu_id'] ?? null,
    //                     'submenu_id'    => $menu['submenu_id'] ?? null,
    //                 ]);
    //             }
    //         }

    //         DB::commit();

    //         return [
    //             'status'  => true,
    //             'message' => 'Permissions assigned successfully with menus and submenus.',
    //         ];
    //     } catch (\Throwable $e) {
    //         DB::rollBack();

    //         Log::error('Failed to assign permissions', [
    //             'role_id' => $roleId,
    //             'error'   => $e->getMessage(),
    //         ]);

    //         return [
    //             'status'  => false,
    //             'message' => 'Failed to assign permissions. Please try again later.',
    //         ];
    //     }
    // }


    // public function updatePermissionsForRole(int $roleId, array $data): array
    // {
    //     DB::beginTransaction();

    //     try {
    //         RoleHasPermission::where('role_id', $roleId)->delete();

    //         foreach ($data['permissions'] ?? [] as $permission) {
    //             $permissionId = $permission['permission_id'] ?? null;
    //             $menus = $permission['menus'] ?? [];

    //             if (empty($permissionId)) {
    //                 continue;
    //             }

    //             if (empty($menus)) {
    //                 RoleHasPermission::create([
    //                     'role_id'       => $roleId,
    //                     'permission_id' => $permissionId,
    //                     'menu_id'       => null,
    //                     'submenu_id'    => null,
    //                 ]);
    //                 continue;
    //             }

    //             foreach ($menus as $menu) {
    //                 RoleHasPermission::create([
    //                     'role_id'       => $roleId,
    //                     'permission_id' => $permissionId,
    //                     'menu_id'       => $menu['menu_id'] ?? null,
    //                     'submenu_id'    => $menu['submenu_id'] ?? null,
    //                 ]);
    //             }
    //         }

    //         DB::commit();

    //         return [
    //             'status'  => true,
    //             'message' => 'Role permissions updated successfully.',
    //         ];
    //     } catch (\Throwable $e) {
    //         DB::rollBack();

    //         Log::error('Failed to update role permissions', [
    //             'role_id' => $roleId,
    //             'error'   => $e->getMessage(),
    //         ]);

    //         return [
    //             'status'  => false,
    //             'message' => 'Failed to update role permissions. Please try again later.',
    //         ];
    //     }
    // }

    public function assignPermissionsWithMenu(int $roleId, array $data): array
    {
        DB::beginTransaction();
        try {
            RoleHasPermission::where('role_id', $roleId)->delete();
            $permissions = collect($data['permissions'] ?? []);
            $records = $permissions->flatMap(function ($permission) use ($roleId) {
                $permissionId = $permission['permission_id'] ?? null;
                $menus = collect($permission['menus'] ?? []);
                if (empty($permissionId)) {
                    return [];
                }
                if ($menus->isEmpty()) {
                    return [[
                        'role_id'       => $roleId,
                        'permission_id' => $permissionId,
                        'menu_id'       => null,
                        'submenu_id'    => null
                    ]];
                }

                return $menus->map(function ($menu) use ($roleId, $permissionId) {
                    return [
                        'role_id'       => $roleId,
                        'permission_id' => $permissionId,
                        'menu_id'       => $menu['menu_id'] ?? null,
                        'submenu_id'    => $menu['submenu_id'] ?? null
                    ];
                });
            });

            // Bulk insert all records
            if ($records->isNotEmpty()) {
                RoleHasPermission::insert($records->toArray());
            }

            DB::commit();

            return [
                'status'  => true,
                'message' => 'Permissions assigned successfully with menus and submenus.',
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Failed to assign permissions', [
                'role_id' => $roleId,
                'error'   => $e->getMessage(),
            ]);

            return [
                'status'  => false,
                'message' => 'Failed to assign permissions. Please try again later.',
            ];
        }
    }


    public function updatePermissionsForRole(int $roleId, array $data): array
    {
        DB::beginTransaction();

        try {
            // Delete existing permissions for the role
            RoleHasPermission::where('role_id', $roleId)->delete();

            $permissions = collect($data['permissions'] ?? []);

            // Prepare all records to insert
            $records = $permissions->flatMap(function ($permission) use ($roleId) {
                $permissionId = $permission['permission_id'] ?? null;
                $menus = collect($permission['menus'] ?? []);

                if (empty($permissionId)) {
                    return [];
                }

                if ($menus->isEmpty()) {
                    return [[
                        'role_id'       => $roleId,
                        'permission_id' => $permissionId,
                        'menu_id'       => null,
                        'submenu_id'    => null
                    ]];
                }

                return $menus->map(function ($menu) use ($roleId, $permissionId) {
                    return [
                        'role_id'       => $roleId,
                        'permission_id' => $permissionId,
                        'menu_id'       => $menu['menu_id'] ?? null,
                        'submenu_id'    => $menu['submenu_id'] ?? null
                    ];
                });
            });

            // Bulk insert all records
            if ($records->isNotEmpty()) {
                RoleHasPermission::insert($records->toArray());
            }

            DB::commit();

            return [
                'status'  => true,
                'message' => 'Role permissions updated successfully.',
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Failed to update role permissions', [
                'role_id' => $roleId,
                'error'   => $e->getMessage(),
            ]);

            return [
                'status'  => false,
                'message' => 'Failed to update role permissions. Please try again later.',
            ];
        }
    }

    public function createRole(array $data)
    {
        DB::beginTransaction();
        try {
            if (empty($data['name'])) {
                throw new \Exception("Role name is required.");
            }
            $permissions = $data['permissions'] ?? [];
            $guard = $data['guard_name'] ?? null;
            $labels = $data['labels'] ?? [];
            if (!$guard && !empty($permissions)) {
                $guard = Permission::whereIn('id', $permissions)->value('guard_name') ?? 'web';
            }
            $validPermissions = [];
            if (!empty($permissions)) {
                $dbPermissions = Permission::whereIn('id', $permissions)->get();
                foreach ($dbPermissions as $perm) {
                    if ($perm->guard_name !== $guard) {
                        throw new \Exception(
                            "Permission '{$perm->name}' (ID: {$perm->id}) does not belong to guard '{$guard}'."
                        );
                    }
                    $validPermissions[] = $perm->id;
                }
                if (count($validPermissions) !== count($permissions)) {
                    throw new \Exception("Some permissions do not exist in the database.");
                }
            }
            if (is_array($labels)) {
                $labels = implode(',', $labels);
            }
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => $guard,
                'labels' => $labels,
                'status' => $data['status'],
            ]);
            if (!empty($validPermissions)) {
                $role->givePermissionTo($validPermissions);
            }
            DB::commit();

            return $role;
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error("Failed to create role", [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw new \Exception($e->getMessage());
        }
    }


    public function updateRole($roleOrId, array $data)
    {
        DB::beginTransaction();

        try {
            if (is_int($roleOrId)) {
                $role = Role::findOrFail($roleOrId);
            } elseif ($roleOrId instanceof Role) {
                $role = $roleOrId;
            } else {
                throw new \Exception("Invalid role provided for update.");
            }

            if (isset($data['labels'])) {
                $labels = $data['labels'];

                if (is_array($labels)) {
                    $labels = implode(',', $labels);
                }

                $role->labels = $labels;
            }

            $roleData = $data;
            unset($roleData['permissions'], $roleData['labels']);

            if (!empty($roleData)) {
                $role->update($roleData);
            }

            if (isset($data['permissions'])) {
                $permissions = $data['permissions'];
                $guard = $data['guard_name'] ?? $role->guard_name;

                $validPermissions = Permission::whereIn('id', (array)$permissions)
                    ->where('guard_name', $guard)
                    ->pluck('id')
                    ->toArray();

                $role->givePermissionTo($validPermissions);
            }

            if (isset($data['labels'])) {
                $role->save();
            }

            DB::commit();
            return $role;
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("Failed to update role", [
                'role_id' => $role->id ?? null,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception($e->getMessage());
        }
    }

    // public function updateRole($roleOrId, array $data)
    // {
    //     DB::beginTransaction();

    //     try {
    //         if (is_int($roleOrId)) {
    //             $role = Role::findOrFail($roleOrId);
    //         } elseif ($roleOrId instanceof Role) {
    //             $role = $roleOrId;
    //         } else {
    //             throw new \Exception("Invalid role provided for update.");
    //         }

    //         $roleData = $data;
    //         unset($roleData['permissions']);

    //         if (!empty($roleData)) {
    //             $role->update($roleData);
    //         }

    //         if (isset($data['permissions'])) {
    //             $permissions = $data['permissions'];
    //             $guard = $data['guard_name'] ?? $role->guard_name;

    //             $validPermissions = Permission::whereIn('id', (array)$permissions)
    //                 ->where('guard_name', $guard)
    //                 ->pluck('id')
    //                 ->toArray();

    //             $role->givePermissionTo($validPermissions);
    //         }

    //         DB::commit();
    //         return $role;
    //     } catch (Throwable $e) {
    //         DB::rollBack();
    //         Log::error("Failed to update role", [
    //             'role_id' => $role->id ?? null,
    //             'data' => $data,
    //             'error' => $e->getMessage(),
    //         ]);

    //         throw new \Exception($e->getMessage());
    //     }
    // }



    public function deleteRole($role)
    {
        try {
            $role->delete();
            return true;
        } catch (Throwable $e) {
            Log::error("Failed to delete role", ['role_id' => $role->id, 'error' => $e->getMessage()]);
            throw new \Exception("Failed to delete role. Please try again.");
        }
    }
}
