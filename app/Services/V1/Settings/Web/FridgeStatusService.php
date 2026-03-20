<?php

namespace App\Services\V1\Settings\Web;

use App\Models\FridgeStatus;
use Throwable;
use Log;

class FridgeStatusService
{
    public function list(array $filters = [])
    {
        try {
            $query = FridgeStatus::query()
                ->select(['id','name']);

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['name'])) {
                $query->where('name', 'ILIKE', '%' . $filters['name'] . '%');
            }

            if (!empty($filters['code'])) {
                $query->where('code', 'ILIKE', '%' . $filters['code'] . '%');
            }

            return $query
                ->orderBy('id', 'desc')
                ->get();
        } catch (Throwable $e) {
            Log::error('Failed to fetch fridge status list', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);

            throw new \Exception('Unable to fetch fridge status list.');
        }
    }
}
