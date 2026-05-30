<?php

namespace App\Services\V1\B2C_App\Master;

use App\Models\PromotionHeader;

class PromotionService
{

    public function promotionListWithImage()
    {
        try {

            $query = PromotionHeader::with([
                'promotionDetails',
                'offerItems',
                'promotionalSlabs'
            ])
                ->whereNotNull('promotion_image')
                ->where('promotion_image', '!=', '')
                ->orderByDesc('id');

            return $query->paginate(50);
        } catch (\Throwable $e) {

            \Log::error('[PROMOTION IMAGE LIST FAILED]', [
                'error' => $e->getMessage(),
            ]);

            throw new \Exception(
                'Failed to fetch promotion list.',
                500,
                $e
            );
        }
    }

    /**
     * LIST
  
     * LIST PROMOTIONS
     */
    // public function list(array $filters)
    // {
    //     try {
    //         $query = PromotionHeader::with('promotionDetails', 'offerItems', 'promotionalSlabs')
    //             ->orderByDesc('id');

    //         if (!empty($filters['id'])) {
    //             $query->where('id', $filters['id']);
    //         }

    //         if (!empty($filters['promtion_name'])) {
    //             $query->where('promotion_name', 'ILIKE', '%' . $filters['promtion_name'] . '%');
    //         }

    //         if (isset($filters['status'])) {
    //             $query->where('status', $filters['status']);
    //         }

    //         $limit = $filters['limit'] ?? 50;

    //         return $query->paginate($limit);
    //     } catch (\Throwable $e) {

    //         \Log::error('[PROMOTION LIST FAILED]', [
    //             'filters' => $filters,
    //             'error'   => $e->getMessage(),
    //         ]);

    //         throw new \Exception(
    //             'Failed to fetch promotion list. Please try again later.',
    //             500,
    //             $e
    //         );
    //     }
    // }
}
