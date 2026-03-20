<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\Area;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AreaService
{
    public function getAll($perPage = 10, $filters = [])
    {
        try {
            $query = Area::with('region');

            foreach ($filters as $field => $value) {
                if (!empty($value)) {
                    if (in_array($field, ['area_name', 'area_code'])) {
                        $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                    } else {
                        $query->where($field, $value);
                    }
                }
            }

            return $query->paginate($perPage);
        } catch (\Exception $e) {
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
                'createdBy:id,firstname,lastname,username',
                'updatedBy:id,firstname,lastname,username',
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
