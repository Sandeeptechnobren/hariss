<?php

namespace App\Services\V1\Settings\Web;

use App\Models\PromotionType;
use Illuminate\Pagination\LengthAwarePaginator;

class PromotionTypeService
{

public function getAll(array $filters = [], int $perPage = 10): LengthAwarePaginator
{
    $query = PromotionType::with([
        'createdBy' => function ($q) {
            $q->select('id', 'name','username');
        },
        'updatedBy' => function ($q) {
            $q->select('id', 'name', 'username');
        }
    ])->orderByDesc('id');
    foreach ($filters as $field => $value) {
        if (!empty($value)) {
            $query->where($field, $value);
        }
    }

    return $query->orderByDesc('id')->paginate($perPage);
}

    public function getById($id)
    {
        return PromotionType::findOrFail($id);
    }

public function create(array $data)
{
    try {
        if (empty($data['code'])) {
            $randomNumber = random_int(10, 999);
            $data['code'] = 'PTC' . $randomNumber;
        }

        $userId = \Auth::id();
        if (!$userId) {
            throw new \Exception("Unauthenticated: No user logged in");
        }

        $data['created_user'] = $userId;
        $data['updated_user'] = $userId;

        $promotionType = PromotionType::create($data);

        return [
            'status'  => 'success',
            'code'    => 201,
            'message' => 'Promotion Type created successfully',
            'data'    => $promotionType
        ];
    } catch (\Exception $e) {
        \Log::error('PromotionType create failed: '.$e->getMessage(), [
            'data' => $data
        ]);

        return [
            'status'  => 'error',
            'code'    => 500,
            'message' => 'Failed to create promotion type',
            'error'   => $e->getMessage()
        ];
    }
}




public function update(int $id, array $data)
{
    try {
        $promotionType = PromotionType::findOrFail($id);

        $data['updated_user'] = \Auth::id();

        $promotionType->update($data);

        return $promotionType;
    } catch (\Exception $e) {
        \Log::error('PromotionType update failed: '.$e->getMessage(), [
            'promotion_type_id' => $id,
            'data' => $data
        ]);
        throw $e; // rethrow so controller can handle
    }
}


public function delete($id): bool
{
    try {
        $promotionType = PromotionType::findOrFail($id);
        $promotionType->delete();
        return true;
    } catch (\Exception $e) {
        \Log::error('PromotionType delete failed: ' . $e->getMessage(), [
            'promotion_type_id' => $id
        ]);
        return false;
    }
}


}
