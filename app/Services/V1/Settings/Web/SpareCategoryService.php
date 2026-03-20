<?php

namespace App\Services\V1\Settings\Web;

use App\Models\SpareCategory;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SpareCategoryService
{

public function createSpareCategory(array $data): SpareCategory
    {
        return SpareCategory::create($data);
    }

    public function listsparecategory(array $filters = [], int $perPage = null)
    {
        $query = SpareCategory::query();

        if (!empty($filters['osa_code'])) {
            $query->where('osa_code', 'like', '%' . $filters['osa_code'] . '%');
        }

        if (!empty($filters['spare_category_name'])) {
            $query->where('spare_category_name', 'like', '%' . $filters['spare_category_name'] . '%');
        }

        if (!empty($filters['status'])) {
            $query->where('status', 'like', '%' . $filters['status'] . '%');
        }

        return $query->orderBy('id', 'desc')->paginate($perPage ?? 50);
    }
      
 public function getByUuid(string $uuid): ?SpareCategory
    {
        return SpareCategory::where('uuid', $uuid)->first();
    }


    public function updateBonus(string $uuid, array $data): ?SpareCategory
{
    $bank = SpareCategory::where('uuid', $uuid)->first();

    if (!$bank) {
        return null;
    }

    $bank->update($data);
    return $bank->fresh();
}

public function deleteBonus(string $uuid): bool
{
    return DB::transaction(function () use ($uuid) {

        $reward = BonusPoint::where('uuid', $uuid)->firstOrFail();
        return $reward->delete();
    });
}
    public function deleteByUuid(string $uuid): void
    {
        $spareCategory = SpareCategory::where('uuid', $uuid)->first();

        if (! $spareCategory) {
            throw new ModelNotFoundException('Spare category not found.');
        }

        $spareCategory->delete();
    }
}