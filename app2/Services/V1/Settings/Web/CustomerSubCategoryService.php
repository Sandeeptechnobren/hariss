<?php

namespace App\Services\V1\Settings\Web;

use App\Models\CustomerSubCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class CustomerSubCategoryService
{
    // public function getAll($perPage = 10, $filters = [])
    // {
    //     try {
    //         $query = CustomerSubCategory::select(
    //             'id',
    //             'customer_category_id',
    //             'customer_sub_category_code',
    //             'customer_sub_category_name',
    //             'status'
    //         )->with('customerCategory:id,customer_category_name');

    //         // Apply filters
    //         foreach ($filters as $field => $value) {
    //             if (!empty($value)) {
    //                 if (in_array($field, ['customer_sub_category_name', 'customer_sub_category_code'])) {
    //                     $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
    //                 } else {
    //                     $query->where($field, $value);
    //                 }
    //             }
    //         }

    //         return $query->paginate($perPage);
    //     } catch (Exception $e) {
    //         Log::error('Failed to fetch Customer SubCategories: ' . $e->getMessage());
    //         throw new Exception('Failed to fetch Customer SubCategories');
    //     }
    // }
    public function getAll($perPage = 10, $filters = [])
        {
            try {
                $query = CustomerSubCategory::select(
                    'id',
                    'customer_category_id',
                    'customer_sub_category_code',
                    'customer_sub_category_name',
                    'status'
                )->with('customerCategory:id,customer_category_name');

                // Apply filters
                foreach ($filters as $field => $value) {
                    // For search fields, allow partial match.
                    if (in_array($field, ['customer_sub_category_name', 'customer_sub_category_code']) && $value !== '' && $value !== null) {
                        $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                    }
                    // For customer_category_id, only apply if set (and non-empty/non-null)
                    elseif ($field === "customer_category_id" && isset($value) && $value !== '') {
                        $query->where($field, $value);
                    }
                    // For status
                    elseif ($field === "status" && isset($value) && $value !== '') {
                        $query->where($field, $value);
                    }
                }

                return $query->paginate($perPage);
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
            $autoCode = 'CUSTSUB' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            $data['customer_sub_category_code'] = $autoCode;
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
