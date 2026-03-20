<?php

namespace App\Services\V1\Settings\Web;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class UserService
{
    /**
     * Global search for users.
     */
    public function globalSearch(?string $query = null, int $perPage = 10): LengthAwarePaginator
    {
        try {
            $userQuery = User::select(
                'id',
                'uuid',
                'name',
                'username',
                'email',
                'contact_number',
                'profile_picture',
                'role',
                'status',
                'company',
                'warehouse',
                'route',
                'salesman',
                'region',
                'area',
                'outlet_channel',
                'created_by',
                'updated_user',
                'created_at'
            );

            $query = trim($query ?? '');

            if ($query !== '') {
                $userQuery->where(function ($q) use ($query) {
                    $q->where('name', 'ILIKE', "%{$query}%")
                      ->orWhere('username', 'ILIKE', "%{$query}%")
                      ->orWhere('email', 'ILIKE', "%{$query}%")
                      ->orWhere('contact_number', 'ILIKE', "%{$query}%")
                      ->orWhereRaw("CAST(status AS TEXT) ILIKE ?", ["%{$query}%"])
                      ->orWhereRaw("CAST(role AS TEXT) ILIKE ?", ["%{$query}%"]);
                });
            }

            return $userQuery->paginate($perPage);

        } catch (\Throwable $e) {
            Log::error('Failed to fetch users in globalSearch', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
