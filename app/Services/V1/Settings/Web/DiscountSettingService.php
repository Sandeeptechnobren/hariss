<?php

namespace App\Services\V1\Settings\Web;

use App\Models\DiscountSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

class DiscountSettingService
{
    /**
     * Get All
     */
    public function getAll(array $filters = [], int $perPage = 10)
    {
        $query = DiscountSetting::query()->latest('id');

        if (!empty($filters['name'])) {
            $query->where('name', 'ILIKE', "%{$filters['name']}%");
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['dropdown']) && filter_var($filters['dropdown'], FILTER_VALIDATE_BOOLEAN)) {
            return $query->where('status', 1)->get();
        }
        // dd($query->count());
        return $query->paginate($perPage);
    }

    /**
     * Get by UUID
     */
    public function getByUuid($uuid)
    {
        return DiscountSetting::where('uuid', $uuid)->first();
    }

    /**
     * Create
     */
    public function create(array $data)
    {
        try {
            $user = Auth::user();

            $data['created_user'] = $user?->id;
            $data['updated_user'] = $user?->id;

            return DiscountSetting::create($data);
        } catch (Throwable $e) {
            throw new \Exception(
                "Failed to create discount setting: " . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Update
     */
    public function update($uuid, array $data)
    {
        try {
            $record = DiscountSetting::where('uuid', $uuid)->firstOrFail();

            // ✅ Remove null / empty values (only update sent fields)
            $filteredData = array_filter($data, function ($value) {
                return !is_null($value);
            });

            // ✅ Add updated_user
            $filteredData['updated_user'] = auth()->user()?->id;

            $record->update($filteredData);

            return $record->fresh();
        } catch (ModelNotFoundException $e) {
            throw new \Exception("Discount setting not found", 404);
        } catch (Throwable $e) {
            throw new \Exception(
                "Failed to update discount setting: " . $e->getMessage(),
                500
            );
        }
    }
}
