<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\Country;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CountryService
{

    public function getAll($perPage = 10, $filters = [])
    {
        try {
            $query = Country::with([
                'createdBy' => function ($q) {
                    $q->select('id', 'name', 'username');
                },
                'updatedBy' => function ($q) {
                    $q->select('id', 'name', 'username');
                }
            ])->latest('id');

            foreach ($filters as $field => $value) {
                if (!empty($value)) {
                    if (in_array($field, ['country_name', 'country_code'])) {
                        $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                    } else {
                        $query->where($field, $value);
                    }
                }
            }

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch countries: " . $e->getMessage());
        }
    }

    public function getById($id)
    {
        return Country::findOrFail($id);
    }
    public function create(array $data)
    {
        DB::beginTransaction();
        try {
            $data['created_user'] = Auth::id() ?? $data['created_user'] ?? null;

            $country = Country::create($data);

            DB::commit();
            return $country;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Country create failed: " . $e->getMessage());
        }
    }
  
    public function update(Country $country, array $data)
    {
        DB::beginTransaction();
        try {
            $country->update($data);

            DB::commit();
            return $country;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Country update failed: " . $e->getMessage());
        }
    }

    public function delete(Country $country)
    {
        DB::beginTransaction();
        try {
            $country->delete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Country delete failed: " . $e->getMessage());
        }
    }
public function search($perPage = 10, $keyword = null)
{
    try {
        $query = Country::with([
                'createdBy' => function ($q) {
                    $q->select('id','name', 'username');
                },
                'updatedBy' => function ($q) {
                    $q->select('id','name', 'username');
                }
            ])->latest('id');

        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('country_code', 'ILIKE', "%{$keyword}%")
                  ->orWhere('country_name', 'ILIKE', "%{$keyword}%")
                  ->orWhere('currency', 'ILIKE', "%{$keyword}%")
                  ->orWhereRaw('CAST(status AS TEXT) ILIKE ?', ["%{$keyword}%"])
                  ->orWhere('created_user', 'ILIKE', "%{$keyword}%")
                  ->orWhere('updated_user', 'ILIKE', "%{$keyword}%")
                  ->orWhereRaw('CAST(created_date AS TEXT) ILIKE ?', ["%{$keyword}%"])
                  ->orWhereRaw('CAST(updated_date AS TEXT) ILIKE ?', ["%{$keyword}%"]);
            });
        }

        return $query->paginate($perPage);

    } catch (\Exception $e) {
        throw new \Exception("Failed to search countries: " . $e->getMessage());
    }
}



}
