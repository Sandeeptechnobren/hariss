<?php

namespace App\Services\V1\Settings\Web;

use App\Models\ItemSubCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Throwable;

class ItemSubCategoryService
{
public function getAll(array $filters = [], int $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = ItemSubCategory::with([
            'category:id,category_name',
            'createdBy:id,name,username',
            'updatedBy:id,name,username'
        ])->select(
            'id',
            'category_id',
            'sub_category_name',
            'sub_category_code',
            'status',
            'created_user',
            'updated_user',
            'created_date',
            'updated_date'
        )->orderByDesc('id');
        if (!empty($filters['category_id'])) {
            $query->where('category_id', (int)$filters['category_id']);
        }
        if (!empty($filters['sub_category_name'])) {
            $query->whereRaw('LOWER(sub_category_name) LIKE ?', ['%' . strtolower($filters['sub_category_name']) . '%']);
        }
        if (!empty($filters['sub_category_code'])) {
            $query->where('sub_category_code', $filters['sub_category_code']);
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }
        return $query->paginate($perPage);
    }


public function getById($id)
    {
        return ItemSubCategory::with('category')->findOrFail($id);
    }

public function create(array $data): ?ItemSubCategory
    {
        DB::beginTransaction();
        try {
            $userId = Auth::id();
            if (!$userId) {
                throw new \Exception("Unauthenticated: No user logged in");
            }

            if (empty($data['sub_category_code'])) {
                $lastSubCategory = ItemSubCategory::withTrashed()->latest('id')->first();
                $nextNumber = $lastSubCategory ? $lastSubCategory->id + 1 : 1;
                $data['sub_category_code'] = 'SBC' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }

            $data['created_user'] = $userId;
            $data['updated_user'] = $userId;

            $subCategory = ItemSubCategory::create($data);

            DB::commit();
            return $subCategory;
        } catch (\Throwable $e) {
            DB::rollBack();
            // return real error to API response for debugging
            throw new \Exception("Item SubCategory create failed: " . $e->getMessage());
        }
    }




    public function update($id, array $data): ?ItemSubCategory
    {
        DB::beginTransaction();
        try {
            $data['updated_user'] = Auth::id();
            $subCategory = ItemSubCategory::findorfail($id);
            $subCategory->update($data);
            DB::commit();
            return $subCategory;
        } catch (Throwable $e) {
            DB::rollBack();
            return null;
        }
    }

    public function delete(ItemSubCategory $subCategory): bool
    {
        DB::beginTransaction();

        try {
            $deleted = $subCategory->delete();

            DB::commit();
            return $deleted;
        } catch (Throwable $e) {
            DB::rollBack();
            return false;
        }
    }
}
