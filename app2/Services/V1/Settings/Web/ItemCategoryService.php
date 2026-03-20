<?php

namespace App\Services\V1\Settings\Web;

use App\Models\ItemCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Throwable;

class ItemCategoryService
{
    public function getAll(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = ItemCategory::with([
                'createdBy' => function ($q) {
                    $q->select('id', 'firstname', 'lastname', 'username');
                },
                'updatedBy' => function ($q) {
                    $q->select('id', 'firstname', 'lastname', 'username');
                }
        ]);

        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                if (in_array($field, ['category_code', 'category_name', 'status'])) {
                    $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                } else {
                    $query->where($field, $value);
                }
            }
        }
// dd($query->paginate($perPage));
        return $query->paginate($perPage);
    }

    public function getById($id): ItemCategory
    {
        return ItemCategory::findOrFail($id);
    }

public function create(array $data): ?ItemCategory
{
    DB::beginTransaction();

    try {
        $data['created_user'] = Auth::id();

        if (empty($data['category_code'])) {
            $randomNumber = random_int(1, 999);
            $data['category_code'] = 'IC' . str_pad($randomNumber, 3, '0', STR_PAD_LEFT);
        }
        $itemCategory = ItemCategory::create($data);

        DB::commit();

        return $itemCategory;
    } catch (\Throwable $e) {
        DB::rollBack();
        logger()->error('ItemCategory create failed', [
            'error' => $e->getMessage(),
            'data' => $data,
        ]);
        return null;
    }
}



    public function update(ItemCategory $itemCategory, array $data): ?ItemCategory
    {
        DB::beginTransaction();

        try {
            $data['updated_user'] = Auth::id();

            $itemCategory->update($data);

            DB::commit();
            return $itemCategory;
        } catch (Throwable $e) {
            DB::rollBack();
            return null;
        }
    }

    /**
     * Delete Item Category
     */
    public function delete(ItemCategory $itemCategory): bool
    {
        DB::beginTransaction();

        try {
            $deleted = $itemCategory->delete();

            DB::commit();
            return $deleted;
        } catch (Throwable $e) {
            DB::rollBack();
            return false;
        }
    }
}
