<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

trait RoleAuthorization
{
    protected function authorizeRoleAccess(string $method): ?JsonResponse
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return $this->fail("Unauthorized access", 403);
        }

        $roleId = $user->role;
        if ($roleId === 1) {
            return null;
        }

        if ($roleId === 2) {
            if ($method === 'index') {
                return null;
            }
            return $this->fail("You don't have permission to access this route", 403);
        }

        return $this->fail("Unauthorized access", 403);
    }
}
