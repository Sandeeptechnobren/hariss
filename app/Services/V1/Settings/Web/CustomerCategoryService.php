<?php

namespace App\Services\V1\Settings\Web;

use App\Models\CustomerCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class CustomerCategoryService
{
    /**
     * Get all customer categories with optional filters and pagination
     */
    // public function getAll(array $filters = [], int $perPage = 10)
    // {
    //     try {
    //         $query = CustomerCategory::with([
    //             'outletChannel' => function ($q) {
    //                 $q->select('id', 'outlet_channel_code', 'outlet_channel');
    //             },
    //             'createdBy' => function ($q) {
    //                 $q->select('id', 'name', 'username');
    //             },
    //             'updatedBy' => function ($q) {
    //                 $q->select('id', 'name', 'username');
    //             }
    //         ])->orderByDesc('id');
    //         if (!empty($filters['customer_category_name'])) {
    //             $query->where('customer_category_name', 'like', '%' . $filters['customer_category_name'] . '%');
    //         }
    //         if (!empty($filters['customer_category_code'])) {
    //             $query->where('customer_category_code', 'like', '%' . $filters['customer_category_code'] . '%');
    //         }
    //         if (isset($filters['status'])) {
    //             $query->where('status', $filters['status']);
    //         }
    //         if (isset($filters['outlet_channel_id'])) {
    //             $query->where('outlet_channel_id', $filters['outlet_channel_id']);
    //         }
    //         return $query->paginate($perPage);
    //     } catch (Exception $e) {
    //         throw new Exception("Failed to fetch customer categories: " . $e->getMessage());
    //     }
    // }


    public function getAll(array $filters = [], int $perPage = 10)
    {
        try {
            $query = CustomerCategory::with([
                'outletChannel:id,outlet_channel_code,outlet_channel',
                'createdBy:id,name,username',
                'updatedBy:id,name,username'
            ])
                ->orderByDesc('id');

            // 🔹 Filters
            if (!empty($filters['customer_category_name'])) {
                $query->where(
                    'customer_category_name',
                    'like',
                    '%' . $filters['customer_category_name'] . '%'
                );
            }

            if (!empty($filters['customer_category_code'])) {
                $query->where(
                    'customer_category_code',
                    'like',
                    '%' . $filters['customer_category_code'] . '%'
                );
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['outlet_channel_id'])) {
                $query->where('outlet_channel_id', $filters['outlet_channel_id']);
            }

            // ✅ Dropdown → only active + no pagination
            if (
                isset($filters['dropdown']) &&
                filter_var($filters['dropdown'], FILTER_VALIDATE_BOOLEAN)
            ) {
                $query->where('status', 1);
                return $query->get();
            }

            return $query->paginate($perPage);
        } catch (Exception $e) {
            throw new Exception(
                "Failed to fetch customer categories: " . $e->getMessage()
            );
        }
    }


    public function getById($id)
    {
        try {
            return CustomerCategory::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new Exception("Customer category not found with ID {$id}");
        } catch (Exception $e) {
            throw new Exception("Failed to fetch customer category: " . $e->getMessage());
        }
    }

    public function create(array $data)
    {
        try {
            $lastCategory = CustomerCategory::withTrashed()->latest('id')->first();

            if (empty($data['customer_category_code'])) {
                $nextNumber = $lastCategory ? $lastCategory->id + 1 : 1;
                $data['customer_category_code'] = 'CA' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }
            $data['created_user'] = Auth::id();
            $data['updated_user'] = Auth::id();

            return CustomerCategory::create($data);
        } catch (Exception $e) {
            throw new Exception("Failed to create customer category: " . $e->getMessage());
        }
    }

    // public function create(array $data)
    // {
    //     try {
    //         $lastCategory = CustomerCategory::withTrashed()->latest('id')->first();
    //         $nextNumber = $lastCategory ? $lastCategory->id + 1 : 1;
    //         $data['customer_category_code'] = 'CA' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT); 
    //         $data['created_user'] = Auth::id(); 
    //         $data['updated_user'] = Auth::id();
    //         return CustomerCategory::create($data);
    //     } catch (Exception $e) {
    //         throw new Exception("Failed to create customer category: " . $e->getMessage());
    //     }
    // }

    public function update($id, array $data)
    {
        try {
            $category = CustomerCategory::findOrFail($id);
            $data['updated_user'] = Auth::id(); // current logged-in user
            $category->update($data);
            return $category;
        } catch (ModelNotFoundException $e) {
            throw new Exception("Customer category not found with ID {$id}");
        } catch (Exception $e) {
            throw new Exception("Failed to update customer category: " . $e->getMessage());
        }
    }

public function destroy(int $id): JsonResponse
{
    $oldCategory = CustomerCategory::find($id);
    $previousData = $oldCategory ? $oldCategory->getOriginal() : null;

    DB::beginTransaction();
    try {
        $this->service->delete($id);
        DB::commit();
        if ($previousData) {
            LogHelper::store(
                'settings',                  
                'customer_category',          
                'delete',                   
                $previousData,              
                null,                        
                auth()->id()         
            );
        }
        return $this->success(null, 'Customer category deleted successfully');
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error("Delete CustomerCategory failed: ID {$id}, Error: " . $e->getMessage());
        return $this->fail($e->getMessage(), 404);
    }
}
    public function search(int $perPage = 10, ?string $keyword = null)
    {
        try {
            $query = CustomerCategory::with([
                'outletChannel:id,outlet_channel_code,outlet_channel',
                'createdBy:id,name,username',
                'updatedBy:id,name,username',
            ]);

            if (!empty($keyword)) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('customer_category_code', 'ILIKE', "%{$keyword}%")
                        ->orWhere('customer_category_name', 'ILIKE', "%{$keyword}%")
                        ->orWhereRaw('CAST(status AS TEXT) ILIKE ?', ["%{$keyword}%"])
                        ->orWhere('created_user', 'ILIKE', "%{$keyword}%")
                        ->orWhere('updated_user', 'ILIKE', "%{$keyword}%")
                        ->orWhereRaw('CAST(created_date AS TEXT) ILIKE ?', ["%{$keyword}%"])
                        ->orWhereRaw('CAST(updated_date AS TEXT) ILIKE ?', ["%{$keyword}%"]);
                });
            }

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            throw new \Exception("Failed to search customer categories: " . $e->getMessage());
        }
    }
}
