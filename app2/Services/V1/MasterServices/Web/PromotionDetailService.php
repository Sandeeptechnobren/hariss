<?php
namespace App\Services\V1\MasterServices\Web;

use App\Models\PromotionDetail;

class PromotionDetailService
{
    public function create(array $data): PromotionDetail
    {
        return PromotionDetail::create($data);
    }
    
   public function list(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
{
    $query = PromotionDetail::with('header')->orderBy('id', 'desc');

    // Global search
    if (!empty($filters['search'])) {
        $search = $filters['search'];
        $query->where(function ($q) use ($search) {
            $q->where('id', 'like', "%{$search}%")
              ->orWhere('lower_qty', 'like', "%{$search}%")
              ->orWhereHas('header', function ($q2) use ($search) {
                  $q2->where('upper_qty', 'like', "%{$search}%");
              });
        });
    }

    // Pagination
    $perPage = $filters['per_page'] ?? 10;

    return $query->paginate($perPage);
}

    public function show(string $uuid): PromotionDetail
    {
        $uuid = trim($uuid);
        return PromotionDetail::with('header')->where('uuid', trim($uuid))->firstOrFail();
    }

   public function update(string $uuid, array $data): PromotionDetail
    {
        $uuid = trim($uuid);
        $promotionDetail = PromotionDetail::where('uuid', $uuid)->firstOrFail();
        $promotionDetail->update($data);
        return $promotionDetail;
    }

   public function delete(string $uuid): void
    {
        $uuid = trim($uuid);
        $promotionDetail = PromotionDetail::where('uuid', $uuid)->firstOrFail();
        $promotionDetail->delete();
    }
}
