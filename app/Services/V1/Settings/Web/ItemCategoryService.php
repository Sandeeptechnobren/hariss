<?php

namespace App\Services\V1\Settings\Web;

use App\Models\ItemCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Throwable;
use Illuminate\Support\Collection;


class ItemCategoryService
{
    //     public function getAll(array $filters = [], int $perPage = 50): LengthAwarePaginator
    //     {
    //         $query = ItemCategory::with([
    //             'createdBy' => function ($q) {
    //                 $q->select('id', 'name', 'username');
    //             },
    //             'updatedBy' => function ($q) {
    //                 $q->select('id', 'name', 'username');
    //             }
    //         ])->orderByDesc('id');

    //         foreach ($filters as $field => $value) {
    //             if (!empty($value)) {
    //                 if (in_array($field, ['category_code', 'category_name', 'status'])) {
    //                     $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
    //                 } else {
    //                     $query->where($field, $value);
    //                 }
    //             }
    //         }
    //         // dd($query->paginate($perPage));
    //         return $query->paginate($perPage);
    //     }


    public function getAll(array $filters = [], int $perPage = 50)
    {
        $isDropdown = isset($filters['dropdown'])
            && filter_var($filters['dropdown'], FILTER_VALIDATE_BOOLEAN);
        unset($filters['dropdown']);
    
        $query = ItemCategory::with([
            'createdBy:id,name,username',
            'updatedBy:id,name,username'
        ])->orderByDesc('id');
    
        if (array_key_exists('status', $filters) && $filters['status'] !== '' && $filters['status'] !== null) {
            $query->where('status', $filters['status']);
        } 
        // else {
        //     $query->where('status', 1);
        // }

        foreach ($filters as $field => $value) {
            if ($value === '' || $value === null || $field === 'status') {
                continue;
            }
    
            if (in_array($field, ['category_code', 'category_name'])) {
                $query->whereRaw(
                    "LOWER({$field}) LIKE ?",
                    ['%' . strtolower($value) . '%']
                );
            } else {
                $query->where($field, $value);
            }
        }
    
        if ($isDropdown) {
            return $query->get();
        }
    
        return $query->paginate($perPage);
    }
    

    public function getById($id): ItemCategory
    {
        return ItemCategory::findOrFail($id);
    }


    public function create(array $data): ItemCategory
    {
        try {
            $lastCategory = ItemCategory::withTrashed()->latest('id')->first();

            if (empty($data['category_code'])) {
                $nextNumber = $lastCategory ? $lastCategory->id + 1 : 1;
                $data['category_code'] = 'IC' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }
            // dd($data);
            $data['created_user'] = Auth::id();
            $data['updated_user'] = Auth::id();

            return ItemCategory::create($data);
        } catch (Exception $e) {
            throw new Exception("Failed to create item category: " . $e->getMessage());
        }
    }

    // public function create(array $data): ?ItemCategory
    // {
    //     DB::beginTransaction();

    //     try {
    //         $data['created_user'] = Auth::id();

    //         if (empty($data['category_code'])) {
    //             $randomNumber = random_int(1, 999);
    //             $data['category_code'] = 'IC' . str_pad($randomNumber, 3, '0', STR_PAD_LEFT);
    //         }
    //         $itemCategory = ItemCategory::create($data);

    //         DB::commit();

    //         return $itemCategory;
    //     } catch (\Throwable $e) {
    //         DB::rollBack();
    //         logger()->error('ItemCategory create failed', [
    //             'error' => $e->getMessage(),
    //             'data' => $data,
    //         ]);
    //         return null;
    //     }
    // }



    public function update(ItemCategory $itemCategory, array $data): ?ItemCategory
    {
        DB::beginTransaction();

        try {
            $data['updated_user'] = Auth::id();
            $itemCategory->update($data);
            // dd($itemCategory);

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
