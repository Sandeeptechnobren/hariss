<?php

namespace App\Services\V1\Settings\Web;

use App\Models\SpareSubCategory;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SpareSubCategoryService
{

public function createSpareSubCategory(array $data): SpareSubCategory
    {
        return SpareSubCategory::create($data);
    }

    public function listsparesubcategory(array $filters = [], int $perPage = null)
    {
        $query = SpareSubCategory::query();

        if (!empty($filters['osa_code'])) {
            $query->where('osa_code', 'like', '%' . $filters['osa_code'] . '%');
        }

        if (!empty($filters['spare_subcategory_name'])) {
            $query->where('spare_subcategory_name', 'like', '%' . $filters['spare_subcategory_name'] . '%');
        }
        
        if (!empty($filters['spare_category_id'])) {
            $query->where('spare_category_id', 'like', '%' . $filters['spare_category_id'] . '%');
        }

        if (!empty($filters['status'])) {
            $query->where('status', 'like', '%' . $filters['status'] . '%');
        }

        return $query->orderBy('id', 'desc')->paginate($perPage ?? 50);
    }
      
 public function getByUuid(string $uuid): ?SpareSubCategory
    {
        return SpareSubCategory::where('uuid', $uuid)->first();
    }


    public function updateBonus(string $uuid, array $data): ?SpareSubCategory
{
    $bank = SpareSubCategory::where('uuid', $uuid)->first();

    if (!$bank) {
        return null;
    }

    $bank->update($data);
    return $bank->fresh();
}

    public function deleteByUuid(string $uuid): void
    {
        $spareCategory = SpareSubCategory::where('uuid', $uuid)->first();

        if (! $spareCategory) {
            throw new ModelNotFoundException('Spare subcategory not found.');
        }

        $spareCategory->delete();
    }
}