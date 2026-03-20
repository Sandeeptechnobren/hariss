<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\Region;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Helpers\DataAccessHelper;

class RegionService
{
protected function generateRegionCode(): string
    {
        $lastRegion = Region::orderByDesc('id')->first();
        $nextId = $lastRegion ? $lastRegion->id + 1 : 1;
        return 'REG' . str_pad($nextId, 2, '0', STR_PAD_LEFT);
    }

public function create(array $data): Region
    {
        return DB::transaction(function () use ($data) {
            if (empty($data['region_code'])) {
                $data['region_code'] = $this->generateRegionCode();
            }
            $data['created_user'] = Auth::id();
            return Region::create($data);
        });
    }

public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $region = Region::findOrFail($id); // fetch region

            // keep old code if not provided
            if (empty($data['region_code'])) {
                $data['region_code'] = $region->region_code;
            }

            $data['updated_user'] = Auth::id();

            $region->update($data);

            return $region;
        });
    }
public function delete($region)
    {
        DB::beginTransaction();
        try {
            if (!$region instanceof Region) {
                $region =Region::findOrFail($region);
            }

            $region->delete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Region delete failed: ' . $e->getMessage(), [
                'region_id' => $region->id ?? null
            ]);
            throw $e;
        }
    }
// public function getAll($perPage = 10, $filters = [])
//     {
//         try {
//             $companyIds = $request->input('company_ids', []); // e.g., [1,2,3]
//             $query = Region::with([
//                 'company' => function ($q) {
//                     $q->select('id', 'company_code', 'company_name');
//                 },
//                 'createdBy' => function ($q) {
//                     $q->select('id', 'name', 'username');
//                 },
//                 'updatedBy' => function ($q) {
//                     $q->select('id', 'name', 'username');
//                 }
//             ]);
//             foreach ($filters as $field => $value) {
//                 if (!empty($value)) {
//                     if (in_array($field, ['region_name', 'region_code'])) {
//                         $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
//                     } else {
//                         $query->where($field, $value);
//                     }
//                 }
//             }
//             return $query->paginate($perPage);
//         } catch (\Exception $e) {
//             throw new \Exception("Failed to fetch regions: " . $e->getMessage());
//         }
//     }

// public function getAll($perPage = 10, $filters = [])
// {
//     try {
//         $query = Region::with([
//             'company:id,company_code,company_name',
//             'createdBy:id,name,username',
//             'updatedBy:id,name,username'
//         ]);

//         if (!empty($filters['company_id'])) {
//             $companyIds = is_array($filters['company_id'])
//                 ? $filters['company_id']
//                 : explode(',', $filters['company_id']); 
//             $query->whereIn('company_id', $companyIds);
//         }
//         foreach ($filters as $field => $value) {
//             if (!empty($value) && $field !== 'company_id') {
//                 if (in_array($field, ['region_name', 'region_code'])) {
//                     $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
//                 } else {
//                     $query->where($field, $value);
//                 }
//             }
//         }
//         return $query->paginate($perPage);
//     } catch (\Exception $e) {
//         throw new \Exception("Failed to fetch regions: " . $e->getMessage());
//     }
// }
public function getAll($perPage = 10, $filters = [], $dropdown = false)
{
    try {
        if ($dropdown) {
            $query = Region::select(['id', 'region_code', 'region_name'])
                ->when(!empty($filters['company_id']), function ($q) use ($filters) {
                    $companyIds = is_array($filters['company_id'])
                        ? $filters['company_id']
                        : explode(',', $filters['company_id']); 
                    $q->whereIn('company_id', $companyIds);
                })
                ->orderBy('id', 'desc');
            return $query->get();
        }
        $query = Region::with([
            'company:id,company_code,company_name',
            'createdBy:id,name,username',
            'updatedBy:id,name,username'
        ])->orderBy('id', 'desc');
        if (!empty($filters['company_id'])) {
            $companyIds = is_array($filters['company_id'])
                ? $filters['company_id']
                : explode(',', $filters['company_id']); 
            $query->whereIn('company_id', $companyIds);
        }
        foreach ($filters as $field => $value) {
            if (!empty($value) && $field !== 'company_id') {
                if (in_array($field, ['region_name', 'region_code'])) {
                    $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                } else {
                    $query->where($field, $value);
                }
            }
        }
        return $query->paginate($perPage);
    } catch (\Exception $e) {
        throw new \Exception("Failed to fetch regions: " . $e->getMessage());
    }
}


public function regionDropdown(int $perPage = 10, array $filters = [])
{
    try {
        $user = auth()->user();
        $query = Region::select('id', 'region_code', 'region_name')
            ->where('status', 1)
            ->when(!empty($filters['region_id']), fn ($q) =>
                $q->where('id', $filters['region_id'])
            );
        $query = DataAccessHelper::filterRegions($query, $user);

        return $query->paginate($perPage);

    } catch (\Exception $e) {
        Log::error('❌ Failed to fetch regions', [
            'message' => $e->getMessage(),
            'filters' => $filters,
        ]);

        throw new \Exception("Failed to fetch regions: " . $e->getMessage());
    }
}
public function globalSearch($perPage = 10, $searchTerm = null)
{
    try {
        $query = Region::with([
            'createdBy:id,name,username',
            'updatedBy:id,name,username',
        ]);

        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $likeSearch = '%' . strtolower($searchTerm) . '%';

                $q->orWhereRaw("LOWER(region_code) LIKE ?", [$likeSearch])
                  ->orWhereRaw("LOWER(region_name) LIKE ?", [$likeSearch]);
            });
        }
        return $query->paginate($perPage);
    } catch (\Exception $e) {
        throw new \Exception("Failed to fetch vehicles: " . $e->getMessage());
    }
}


}
