<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\Area;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\DataAccessHelper;


class AreaService
{
public function getAll($perPage = 50, $filters = [], $dropdown = false)
{
    try {
        $user = auth()->user();

        $query = Area::select(['id', 'area_code', 'area_name', 'region_id', 'status'])
            ->with('region:id,region_code,region_name')
            ->orderBy('id', 'desc');

        foreach ($filters as $field => $value) {
            // Only skip if null or empty string, allow 0
            if ($value !== null && $value !== '') {
                switch ($field) {
                    case 'area_name':
                    case 'area_code':
                        $query->whereRaw(
                            "LOWER({$field}) LIKE ?",
                            ['%' . strtolower($value) . '%']
                        );
                        break;

                    case 'region_id':
                        $regionIds = is_string($value) && str_contains($value, ',')
                            ? explode(',', $value)
                            : (array) $value;

                        $regionIds = array_filter($regionIds);
                        $query->whereIn('region_id', $regionIds);
                        break;

                    case 'status':
                        $query->where('status', $value);
                        break;

                    default:
                        $query->where($field, $value);
                        break;
                }
            }
        }

        // ✅ APPLY DATA ACCESS HELPER
        $query = DataAccessHelper::filterAreas($query, $user);

        if ($dropdown) {
            return $query->get(['id', 'area_code', 'area_name', 'region_id']);
        }

        return $query->paginate($perPage);

    } catch (\Exception $e) {
        Log::error('❌ Failed to fetch areas', [
            'message' => $e->getMessage(),
            'filters' => $filters,
        ]);

        throw new \Exception("Failed to fetch areas: " . $e->getMessage());
    }
}


    public function areaDropdown()
    {
        try {
            $data = Area::where('status', 1)->select('id', 'area_code', 'area_name', 'region_id')->get();
            return $data;
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch areas: " . $e->getMessage());
        }
    }

    public function create(array $data)
    {
        DB::beginTransaction();

        try {
            $data['created_user'] = Auth::id();
            $data['updated_user'] = Auth::id();
            if (empty($data['area_code'])) {
                $lastArea = Area::orderBy('id', 'desc')->first();
                $nextNumber = $lastArea
                    ? ((int) substr($lastArea->area_code, 2)) + 1
                    : 1;
                $data['area_code'] = 'AR' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }

            $area = Area::create($data);

            DB::commit();
            return $area;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Failed to create area: " . $e->getMessage());
        }
    }


    public function find($id)
    {
        try {
            return Area::with(['region', 'createdBy', 'updatedBy'])->findOrFail($id);
        } catch (\Exception $e) {
            throw new \Exception("Area not found: " . $e->getMessage());
        }
    }

    public function update($id, array $data)
    {
        DB::beginTransaction();
        try {
            $area = Area::findOrFail($id);
            $data['updated_user'] = Auth::id();

            $area->update($data);
            DB::commit();
            return $area;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Failed to update area: " . $e->getMessage());
        }
    }

    public function delete($id)
    {
        DB::beginTransaction();
        try {
            $area = Area::findOrFail($id);
            $area->delete();
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Failed to delete area: " . $e->getMessage());
        }
    }
public function globalSearch($perPage = 10, $searchTerm = null)
    {
        try {
            $query = Area::with([
                'region:id,region_code,region_name',
                'createdBy:id,name,username',
                'updatedBy:id,name,username',
            ]);

            if (!empty($searchTerm)) {
                $searchTerm = strtolower($searchTerm);

                $query->where(function ($q) use ($searchTerm) {
                    $likeSearch = '%' . $searchTerm . '%';

                    $q->orWhereRaw("LOWER(area_code) LIKE ?", [$likeSearch])
                    ->orWhereRaw("LOWER(area_name) LIKE ?", [$likeSearch]);
                });
            }

            return $query->paginate($perPage);

        } catch (\Exception $e) {
            throw new \Exception("Failed to search areas: " . $e->getMessage());
        }
    }

}
