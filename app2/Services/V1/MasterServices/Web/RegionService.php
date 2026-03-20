<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\Region;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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


// public function delete($region)
//     {
//         DB::beginTransaction();
//         try {
//             $region->delete();
//             DB::commit();
//         } catch (\Exception $e) {
//             DB::rollBack();
//             Log::error('Region delete failed: ' . $e->getMessage(), ['region_id' => $region->id ?? null]);
//             throw $e; 
//         }
//     }
// RegionService.php
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

    public function getAll($perPage = 10, $filters = [])
    {
        try {
            $query = Region::with([
                'country'=> function ($q){
                    $q->select('id','country_code','country_name');
                },
                'createdBy' => function ($q) {
                    $q->select('id', 'firstname', 'lastname', 'username');
                },
                'updatedBy' => function ($q) {
                    $q->select('id', 'firstname', 'lastname', 'username');
                }
            ]);

            foreach ($filters as $field => $value) {
                if (!empty($value)) {
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

    public function regionDropdown($perPage = 10)
    {
        try {
            return Region::select('id', 'region_code', 'region_name')
                ->where('status', 1)
                ->paginate($perPage);
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch regions: " . $e->getMessage());
        }
    }


public function globalSearch($perPage = 10, $searchTerm = null)
{
    try {
        $query = Region::with([
            'createdBy:id,firstname,lastname,username',
            'updatedBy:id,firstname,lastname,username',
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
