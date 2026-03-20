<?php

namespace App\Services\V1\MasterServices\Web;

use App\Http\Resources\V1\Master\Web\PromotionGroupResource;
use App\Models\PromotionGroup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PromotionGroupService
{

          public function getAll()
    {
      return PromotionGroup::latest()->paginate(10);
                            
    }

    public function getByUuid(string $uuid): ?PromotionGroup
    {
        return PromotionGroup::where('uuid', $uuid)->first();                     
    }
    public function create(array $data): PromotionGroupResource
    {
        $group = PromotionGroup::create($data);
        return new PromotionGroupResource($group);
    }
   public function update(string $uuid, array $data): PromotionGroup
    {
        $uuid = trim($uuid);
        $promotionGroup = PromotionGroup::where('uuid', $uuid)->firstOrFail();
        $promotionGroup->update($data);
        return $promotionGroup;
    }

    public function delete(string $uuid): void
    {
        $uuid = trim($uuid);
        $promotionDetail = PromotionGroup::where('uuid', $uuid)->firstOrFail();
        $promotionDetail->delete();
    }
}
