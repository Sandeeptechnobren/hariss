<?php

namespace App\Services\V1\Settings\Web;

use App\Models\Usertypes;
use Illuminate\Http\JsonResponse;
class UserTypeService
{
  use \App\Traits\ApiResponse;

public function getAll(int $perPage = 10): JsonResponse
{
    $userTypes = Usertypes::select(
        'id',
        'code',
        'name',
        'status',
        'created_user',
        'updated_user'
    )->with([
        'createdBy' => function ($q) {
            $q->select('id', 'firstname', 'lastname', 'username');
        },
        'updatedBy' => function ($q) {
            $q->select('id', 'firstname', 'lastname', 'username');
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
        return Usertypes::findOrFail($id);
    }

    public function create(array $data, $userId)
    {
        $data['created_user'] = $userId;
        $data['updated_user'] = $userId;
        return Usertypes::create($data);
    }

    public function update($id, array $data, $userId)
    {
        $userType = Usertypes::findOrFail($id);
        $data['updated_user'] = $userId;
        $userType->update($data);
        return $userType;
    }

    public function delete($id)
    {
        $userType = Usertypes::findOrFail($id);
        return $userType->delete();
    }
}
