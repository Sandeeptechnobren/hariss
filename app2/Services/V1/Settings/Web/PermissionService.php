<?php

namespace App\Services\V1\Settings\Web;

use App\Models\Permission;
use Illuminate\Support\Facades\Log;
use Throwable;

class PermissionService
{
    /**
     * List all permissions
     */
public function listPermissions()
    {
        try {
            return Permission::orderBy('id', 'desc')->paginate(10);
        } catch (Throwable $e) {
            Log::error("Failed to fetch permissions", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception("Failed to fetch permissions. Please try again.");
        }
    }

    public function findPermission(int $id)
    {
        try {
            return Permission::findOrFail($id);
        } catch (Throwable $e) {
            Log::error("Failed to fetch permission", [
                'permission_id' => $id,
                'error' => $e->getMessage()
            ]);
            throw new \Exception("Permission not found.");
        }
    }

    public function createPermission(array $data)
    {
        try {
            $permission = Permission::create([
                'name' => $data['name'] ?? null,
                'guard_name' => $data['guard_name'] ?? 'api'
            ]);

            return $permission;
        } catch (Throwable $e) {
            Log::error("Failed to create permission", [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw new \Exception("Failed to create permission. Please try again.");
        }
    }

    /**
     * Update permission
     */
    public function updatePermission(Permission $permission, array $data)
    {

        try {
            if (empty($permission->id)) {
                throw new \Exception("Invalid permission ID.");
            }

            $updateData = [];
            
            if (!empty($data['name'])) {
                $exists = Permission::where('name', $data['name'])
                    ->where('guard_name', $data['guard_name'] ?? $permission->guard_name)
                    ->where('id', '<>', $permission->id)
                    ->exists();

                if ($exists) {
                    throw new \Exception("Permission name '{$data['name']}' already exists for this guard.");
                }
                
                $updateData['name'] = trim($data['name']);
            }
            
            if (!empty($data['guard_name'])) {
                $updateData['guard_name'] = trim($data['guard_name']);
            }
            
            if (!empty($updateData)) {
                $permission->update($updateData);
            }
            
            return $permission->fresh(); 

        } catch (Throwable $e) {
            Log::error("Failed to update permission", [
                'permission_id' => $permission->id ?? null,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception($e->getMessage() ?: "Failed to update permission. Please try again.");
        }
    }


    /**
     * Delete a permission
     */
    public function deletePermission(Permission $permission)
    {
        try {
            $permission->delete();
            return true;
        } catch (Throwable $e) {
            Log::error("Failed to delete permission", [
                'permission_id' => $permission->id,
                'error' => $e->getMessage()
            ]);
            throw new \Exception("Failed to delete permission. Please try again.");
        }
    }
}
