<?php

namespace App\Services\V1\Settings\Web;

use App\Models\Location;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class LocationService
{
    public function all($perPage = 15, $filters = [], $isDropdown = false)
    {
        $query = Location::query()->orderByDesc('id');

        // Search filter
        if (!empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function ($qf) use ($search) {
                $qf->where('name', 'ILIKE', "%{$search}%")
                   ->orWhere('code', 'ILIKE', "%{$search}%")
                   ->orWhere('uuid', 'ILIKE', "%{$search}%");
            });
        }

        // For dropdown: return all without pagination
        if ($isDropdown) {
            return $query->orderBy('name')->get();
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function create(array $data)
    {
        $data['uuid'] = (string) Str::uuid();

        // Auto-generate code if not provided
        if (empty($data['code'])) {
            $prefix = 'LOC';
            $last = Location::withTrashed()->orderBy('id', 'desc')->first();
            $num = $last && preg_match('/\d+$/', $last->code, $m) ? intval($m[0]) + 1 : 1;
            $data['code'] = $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
        }

        if (Auth::check()) {
            $data['create_user'] = Auth::id();
            $data['update_user'] = Auth::id();
        }

        return Location::create($data);
    }

    public function getByUuid(string $uuid)
    {
        return Location::where('uuid', $uuid)->firstOrFail();
    }
    public function update(Location $location, array $data)
    {
        if (Auth::check()) {
            $data['update_user'] = Auth::id();
        }

        $location->update($data);
        return $location;
    }

    public function delete(Location $location)
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
        $location = Location::onlyTrashed()->where('uuid', $uuid)->first();

        if (!$location) {
            throw new ModelNotFoundException("Location not found");
        }

        $location->restore();
        $location->deleted_user = null;
        $location->saveQuietly();

        return $location;
    }
}
