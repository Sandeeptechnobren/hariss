<?php

namespace App\Services\V1\Settings\Web;

use App\Models\CustomerSubCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class CustomerSubCategoryService
{

    public function getAll(
        int $perPage = 10,
        array $filters = [],
        bool $dropdown = false
    ) {
        try {
            $query = CustomerSubCategory::query()
                ->with('customerCategory:id,customer_category_name')
                ->orderByDesc('id');

            /**
             * 🔹 STATUS FILTER (DEFAULT = 1)
             */
            if (array_key_exists('status', $filters) && $filters['status'] !== '' && $filters['status'] !== null) {
                $query->where('status', $filters['status']);
            } 
            // else {
            //     $query->where('status', 1);
            // }

            /**
             * 🔹 OTHER FILTERS
             */
            foreach ($filters as $field => $value) {
                if ($value === '' || $value === null || $field === 'status') {
                    continue;
                }

                if (in_array($field, ['customer_sub_category_name', 'customer_sub_category_code'])) {
                    $query->whereRaw(
                        "LOWER({$field}) LIKE ?",
                        ['%' . strtolower($value) . '%']
                    );
                } elseif ($field === 'customer_category_id') {

                    $categoryIds = is_array($value)
                        ? $value
                        : explode(',', $value);

                    $query->whereIn(
                        'customer_category_id',
                        array_filter(array_map('intval', $categoryIds))
                    );
                }
            }

            /**
             * 🔹 DROPDOWN MODE
             */
            if ($dropdown) {
                return $query
                    ->without('customerCategory')
                    ->select(
                        'id',
                        'customer_sub_category_code',
                        'customer_sub_category_name',
                        'customer_category_id',
                        'status'
                    )
                    ->orderBy('customer_sub_category_name')
                    ->get();
            }

            /**
             * 🔹 NORMAL PAGINATED MODE
             */
            return $query
                ->select(
                    'id',
                    'customer_category_id',
                    'customer_sub_category_code',
                    'customer_sub_category_name',
                    'status'
                )
                ->paginate($perPage);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch Customer SubCategories: ' . $e->getMessage());
            throw new \Exception('Failed to fetch Customer SubCategories');
        }
    }




    public function getById($id)
    {
        try {
            return CustomerSubCategory::with('customerCategory:id,customer_category_name')->findOrFail($id);
        } catch (Exception $e) {
            Log::error("Customer SubCategory fetch failed: ID {$id}, Error: " . $e->getMessage());
            throw new Exception("Customer SubCategory not found");
        }
    }

    public function create(array $data)
    {
        DB::beginTransaction();
        try {
            $userId = Auth::id();
            if (!$userId) {
                throw new Exception("Unauthenticated: No user logged in");
            }
            $lastSubCategory = CustomerSubCategory::latest('id')->first();
            $nextNumber = $lastSubCategory ? $lastSubCategory->id + 1 : 1;
            $autoCode = 'CSSCT' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            // dd($data);
            // Use provided code if available, else auto-generate
            $data['customer_sub_category_code'] = !empty($data['customer_sub_category_code'])
                ? $data['customer_sub_category_code']
                : $autoCode;

            $data['created_user'] = $userId;
            $data['updated_user'] = $userId;

            $subCategory = CustomerSubCategory::create($data);

            DB::commit();
            return $subCategory->fresh()->load('customerCategory:id,customer_category_name');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Customer SubCategory create failed: ' . $e->getMessage(), ['data' => $data]);
            throw new Exception('Failed to create Customer SubCategory: ' . $e->getMessage());
        }
    }


    public function update($id, array $data)
    {
        DB::beginTransaction();
        try {
            $userId = Auth::id();
            if (!$userId) throw new Exception("Unauthenticated: No user logged in");
            $data['updated_user'] = $userId;
            $subCategory = CustomerSubCategory::findOrFail($id);
            $subCategory->update($data);
            DB::commit();
            return $subCategory->fresh()->load('customerCategory:id,customer_category_name');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Customer SubCategory update failed: ID {$id}, Error: " . $e->getMessage(), ['data' => $data]);
            throw new Exception('Failed to update Customer SubCategory: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        DB::beginTransaction();
        try {
            $subCategory = CustomerSubCategory::findOrFail($id);
            $subCategory->delete();
            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Customer SubCategory delete failed: ID {$id}, Error: " . $e->getMessage());
            throw new Exception('Failed to delete Customer SubCategory: ' . $e->getMessage());
        }
    }
}
