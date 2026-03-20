<?php

namespace App\Services\V1\Assets\Web;

use App\Models\Spare;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SpareService
{
public function createSpare(array $data): Spare
    {
        return Spare::create($data);
    }

    public function listspare(array $filters = [], int $perPage = null)
    {
        $query = Spare::query();
    
        if (!empty($filters['osa_code'])) {
            $query->where('osa_code', 'like', '%' . trim($filters['osa_code']) . '%');
        }
    
        if (!empty($filters['spare_name'])) {
            $query->where('spare_name', 'like', '%' . trim($filters['spare_name']) . '%');
        }
    
        if (!empty($filters['spare_categoryid'])) {
            $query->where('spare_categoryid', $filters['spare_categoryid']); // ✅ FIXED
        }
    
        if (!empty($filters['spare_subcategoryid'])) {
            $query->where('spare_subcategoryid', $filters['spare_subcategoryid']); // ✅ FIXED
        }
    
        if (!empty($filters['plant'])) {
            $query->where('plant', 'like', '%' . trim($filters['plant']) . '%');
        }
    
        return $query
            ->orderBy('id', 'desc')
            ->paginate($perPage ?? 50);
    }
    
      
 public function getByUuid(string $uuid): ?Spare
    {
        return Spare::where('uuid', $uuid)->first();
    }


    public function updateBonus(string $uuid, array $data): ?Spare
{
    $bank = Spare::where('uuid', $uuid)->first();

    if (!$bank) {
        return null;
    }

    $bank->update($data);
    return $bank->fresh();
}

    public function deleteByUuid(string $uuid): void
    {
        $spareCategory = Spare::where('uuid', $uuid)->first();

        if (! $spareCategory) {
            throw new ModelNotFoundException('Spare not found.');
        }

        $spareCategory->delete();
    }
}