<?php

namespace App\Services\V1\Settings\Web;

use App\Models\RewardCategory;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Storage;

class RewardCategoryService
{
public function createReward(array $data): RewardCategory
{
    return DB::transaction(function () use ($data) {

        if (isset($data['image'])) {
            $path = $data['image']->store('rewards', 'public');

            $appUrl = rtrim(config('app.url'), '/');
            $fullUrl = $appUrl . '/storage/app/public/' . $path;
            $data['image'] = $fullUrl;
        }

        return RewardCategory::create($data);
    });
}

    public function listRewards(array $filters = [], int $perPage = null)
    {
        $query = RewardCategory::query();

        if (!empty($filters['osa_code'])) {
            $query->where('osa_code', 'like', '%' . $filters['osa_code'] . '%');
        }

        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (!empty($filters['image'])) {
            $query->where('image', 'like', '%' . $filters['image'] . '%');
        }

         if (!empty($filters['points_required'])) {
            $query->where('points_required', 'like', '%' . $filters['points_required'] . '%');
        }

         if (!empty($filters['stock_qty'])) {
            $query->where('stock_qty', 'like', '%' . $filters['stock_qty'] . '%');
        }
        if (!empty($filters['type'])) {
            $query->where('type', 'like', '%' . $filters['type'] . '%');
        }
        if ($perPage) {
            return $query->orderBy('id', 'desc')->paginate($perPage);
        }

        return $query->orderBy('id', 'desc')->get();
    }

 public function getByUuid(string $uuid): ?RewardCategory
    {
        return RewardCategory::where('uuid', $uuid)->first();
    }


public function updateReward(string $uuid, array $data): RewardCategory
{
    return DB::transaction(function () use ($uuid, $data) {

        $reward = RewardCategory::where('uuid', $uuid)->firstOrFail();

        if (isset($data['image'])) {

            if ($reward->image) {
                $oldPath = str_replace(config('app.url') . '/storage/app/public/', '', $reward->image);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $data['image']->store('rewards', 'public');

            $appUrl  = rtrim(config('app.url'), '/');
            $fullUrl = $appUrl . '/storage/app/public/' . $path;

            $data['image'] = $fullUrl;
        }
        $reward->update($data);

        return $reward;
    });
}

public function deleteReward(string $uuid): bool
{
    return DB::transaction(function () use ($uuid) {

        $reward = RewardCategory::where('uuid', $uuid)->firstOrFail();

        if ($reward->image) {
            $oldPath = str_replace(config('app.url') . '/storage/app/public/', '', $reward->image);
            Storage::disk('public')->delete($oldPath);
        }

        return $reward->delete();
    });
}
}