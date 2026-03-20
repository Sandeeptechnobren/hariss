<?php
namespace App\Services\V1\MasterServices\Web;

use App\Models\PromotionHeader;
use Illuminate\Support\Str;
use App\Models\PromotionDetail;
use Illuminate\Support\Facades\DB;

class PromotionHeaderService
{
public function create(array $data): PromotionHeader
{
    $data['uuid'] = Str::uuid()->toString();
    DB::beginTransaction();
    try {
        $promotionHeader = PromotionHeader::create($data);

        $details = collect($data['promotion_details'])->map(function ($detail) use ($promotionHeader) {
            $detail['header_id'] = $promotionHeader->id;
            return PromotionDetail::create($detail);
        });
             DB::commit();

        return $promotionHeader;
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e; 
    }
}


public function list(array $filters = [])
    {
        $query = PromotionHeader::with('customerCategory')->orderBy('id', 'desc');
        if (!empty($filters['id'])) {
            $query->where('id', $filters['id']);
        }

        if (!empty($filters['promtion_name'])) {
            $query->where('promtion_name', 'ILIKE', '%' . $filters['promtion_name'] . '%');
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = $filters['limit'] ?? 10;

        return $query->paginate($perPage);
    }

      public function show(int $id): PromotionHeader
    {
        return PromotionHeader::with('customerCategory')->findOrFail($id);
    }

   public function update(int $id, array $data): PromotionHeader
{
    DB::beginTransaction();
    
    try {
        $promotionHeader = PromotionHeader::findOrFail($id);
        $promotionHeader->update($data);
         if (isset($data['promotion_details'])) {
            collect($data['promotion_details'])->each(function ($detail) use ($id) {
                if (isset($detail['id'])) {
                    $promotionDetail = PromotionDetail::where('header_id', $id)
                        ->where('id', $detail['id'])
                        ->first();
                    if ($promotionDetail) {
                        $promotionDetail->update($detail);
                    }
                } else {
                    $detail['header_id'] = $id;
                    PromotionDetail::create($detail);
                }
            });
        }
        DB::commit();
        return $promotionHeader;
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}


    public function delete(int $id): bool
    {
        $promotionHeader = PromotionHeader::findOrFail($id);
        return $promotionHeader->delete();
    }
}
