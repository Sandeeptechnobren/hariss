<?php

namespace App\Services\V1\Settings\Web;

use App\Models\Brand;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BrandService
{
    public function all($perPage = 50, $filters = [], $isDropdown = false)
    {
        $query = Brand::query();

        if (!empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function ($qf) use ($search) {
                $qf->where('name', 'ILIKE', "%{$search}%")
                   ->orWhere('osa_code', 'ILIKE', "%{$search}%")
                   ->orWhere('uuid', 'ILIKE', "%{$search}%");
            });
        }

        if ($isDropdown) {
            return $query->orderBy('name')->get();
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function create(array $data)
    {
        $data['uuid'] = (string) Str::uuid();

        if (Auth::check()) {
            $data['create_user'] = Auth::id();
            $data['update_user'] = Auth::id();
        }

        return Brand::create($data);
    }

    public function getByUuid(string $uuid)
    {
        return Brand::where('uuid', $uuid)->firstOrFail();
    }
    public function update(Brand $location, array $data)
    {
        if (Auth::check()) {
            $data['update_user'] = Auth::id();
        }

        $location->update($data);
        return $location;
    }

    public function delete(Brand $location)
    {
        if (Auth::check()) {
            $location->deleted_user = Auth::id();
            $location->saveQuietly();
        }

        $location->delete();
        return true;
    }

    public function restoreByUuid(string $uuid)
    {
        $location = Brand::onlyTrashed()->where('uuid', $uuid)->first();

        if (!$location) {
            throw new ModelNotFoundException("Brand not found");
        }

        $location->restore();
        $location->deleted_user = null;
        $location->saveQuietly();

        return $location;
    }
}
