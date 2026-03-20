<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\Discount;
use Illuminate\Support\Facades\DB;
use Exception;

class DiscountService
{
public function getAll(int $perPage = 10)
{
    try {
        return Discount::with([
            'item:id,code,name',
            'itemCategory:id,category_name,category_code',
            'itemSubCategory:id,sub_category_name,sub_category_code',
            'discountType:id,discount_code,discount_name',
        ])->paginate($perPage);
    } catch (\Exception $e) {
        throw new \Exception("Failed to fetch discounts: " . $e->getMessage());
    }
}
    public function getByUuid(string $uuid): ?Discount
    {
        $discount = Discount::with([
            'item:id,code,name',
            'itemCategory:id,category_name,category_code',
            'itemSubCategory:id,sub_category_name,sub_category_code',
            'discountType:id,discount_code,discount_name',
            ])
            ->where('uuid', $uuid)
            ->first();

        if (!$discount) {
            throw new Exception("Discount not found");
        }

        return $discount;
    }
public function create(array $data): Discount
    {
        try {
            if (empty($data['osa_code'])) {
                // $lastWarehouse = Discount::latest('id')->first();
                $lastWarehouse = Discount::withTrashed()->latest('id')->first();
                $nextId = $lastWarehouse ? $lastWarehouse->id + 1 : 1;
                $data['osa_code'] = 'OSA' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
            } 
            return DB::transaction(function () use ($data) {
                return Discount::create($data);
            });
        } catch (\Exception $e) {
            throw new \Exception("Failed to create discount: " . $e->getMessage());
        }
    }
public function update(string $uuid, array $data): Discount
    {
        return DB::transaction(function () use ($uuid, $data) {
            $discount = Discount::where('uuid', $uuid)->first();

            if (!$discount) {
                throw new Exception("Discount not found");
            }

            $discount->update($data);
            return $discount;
        });
    }
public function delete(string $uuid): bool
    {
        return DB::transaction(function () use ($uuid) {
            $discount = Discount::where('uuid', $uuid)->first();

            if (!$discount) {
                throw new Exception("Discount not found");
            }

            return $discount->delete();
        });
    }
public function globalSearch(int $perPage = 10, ?string $searchTerm = null)
{
    try {
        $query = Discount::with([
            'item:id,code,name',
            'itemCategory:id,category_name,category_code',
            'itemSubCategory:id,sub_category_name,sub_category_code',
            'discountType:id,discount_code,discount_name',
        ]);

        if (!empty($searchTerm)) {
            $searchTerm = strtolower($searchTerm);
            $likeSearch = '%' . $searchTerm . '%';

            $query->where(function ($q) use ($likeSearch) {
                $q->orWhereRaw("LOWER(CAST(min_quantity AS TEXT)) LIKE ?", [$likeSearch])
                  ->orWhereRaw("LOWER(CAST(discount_value AS TEXT)) LIKE ?", [$likeSearch])
                  ->orWhereRaw("LOWER(CAST(min_order_value AS TEXT)) LIKE ?", [$likeSearch])
                  ->orWhereRaw("LOWER(osa_code) LIKE ?", [$likeSearch])
                  ->orWhereRaw("LOWER(uuid::text) LIKE ?", [$likeSearch]);
            });
        }

        return $query->paginate($perPage);
    } catch (\Exception $e) {
        throw new \Exception("Failed to search discounts: " . $e->getMessage());
    }
}

}
