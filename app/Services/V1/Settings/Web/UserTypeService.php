<?php

namespace App\Services\V1\Settings\Web;

use App\Models\User;
use Illuminate\Http\JsonResponse;
class UserTypeService
{
  use \App\Traits\ApiResponse;

public function getAll(int $perPage = 10): JsonResponse
{
    $userTypes = User::select(
        'uuid',
        'name',
        'email',
        'username',
        'contact_number',
        'password',
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
        'Created_Date',
    )->with([
        'createdBy' => function ($q) {
            $q->select('id','name', 'username');
        },
        'updatedBy' => function ($q) {
            $q->select('id','name', 'username');
        }
    ])->paginate($perPage);
    $pagination = [
        'page'         => $userTypes->currentPage(),
        'limit'        => $userTypes->perPage(),
        'totalPages'   => $userTypes->lastPage(),
        'totalRecords' => $userTypes->total(),
    ];
    return $this->success(($userTypes->items()),
        'User types fetched successfully',
        200,
        $pagination
    );
}
    public function getById($id)
    {
        return User::findOrFail($id);
    }

    public function create(array $data, $userId)
    {
        $data['created_user'] = $userId;
        $data['updated_user'] = $userId;
        return User::create($data);
    }

    public function update($id, array $data, $userId)
    {
        $userType = User::findOrFail($id);
        $data['updated_user'] = $userId;
        $userType->update($data);
        return $userType;
    }

    public function delete($id)
    {
        $userType = User::findOrFail($id);
        return $userType->delete();
    }
    


//     public function globalSearch(?string $query = null, int $perPage = 10): JsonResponse
// {
//     try {
//         $userTypesQuery = User::select(
//             'id',
//             'code',
//             'name',
//             'status',
//             'created_user',
//             'updated_user'
//         )->with([
//             'createdBy:id,name,username',
//             'updatedBy:id,name,username'
//         ]);

//         // Trim aur null handling
//         $query = trim($query ?? '');

//         // Agar query empty nahi hai to filter lagao
//         if ($query !== '') {
//             $userTypesQuery->where(function ($q) use ($query) {
//                 $q->where('code', 'ILIKE', "%{$query}%")
//                   ->orWhere('name', 'ILIKE', "%{$query}%")
//                   ->orWhereRaw("CAST(status AS TEXT) ILIKE ?", ["%{$query}%"]);
//             });
//         }

//         // Paginate results
//         $userTypes = $userTypesQuery->paginate($perPage);

//         $pagination = [
//             'page'         => $userTypes->currentPage(),
//             'limit'        => $userTypes->perPage(),
//             'totalPages'   => $userTypes->lastPage(),
//             'totalRecords' => $userTypes->total(),
//         ];

//         return $this->success(
//             $userTypes->items(),
//             'User types fetched successfully',
//             200,
//             $pagination
//         );

//     } catch (\Throwable $e) {
//         \Log::error('Failed to fetch user types in globalSearch', [
//             'error' => $e->getMessage(),
//         ]);

//         // return $this->error('Failed to fetch user types. Please try again.', 500);
//     }
// }
public function globalSearch(?string $query = null, int $perPage = 10): JsonResponse
{
    try {
        $userQuery = User::select(
            'id',
            'name',
            'username',
            'email',
            'contact_number',
            'role',
            'status',
            'Login_Date',
            'created_by',
            'updated_user',
            'Created_Date'
        )->with([
            'createdBy:id,name,username',
            'updatedBy:id,name,username'
        ]);

        // Clean query text
        $query = trim($query ?? '');

        // Apply filters only if query not empty
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

        // Paginate results
        $users = $userQuery->paginate($perPage);

        $pagination = [
            'page'         => $users->currentPage(),
            'limit'        => $users->perPage(),
            'totalPages'   => $users->lastPage(),
            'totalRecords' => $users->total(),
        ];

        return $this->success(
            $users->items(),
            'Users fetched successfully',
            200,
            $pagination
        );

    } catch (\Throwable $e) {
        \Log::error('Failed to fetch users in globalSearch', [
            'error' => $e->getMessage(),
        ]);

        return $this->fail('Failed to fetch users. Please try again.', 500);
    }
}


}