<?php

namespace App\Services\V1\Settings\Web;

use App\Models\CustomerType;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\DB;


class CustomerTypeService
{
    /**
     * Get all customer types with filters and pagination
     */
    // public function getAll(array $filters = [], int $perPage = 10)
    // {
    //     try {
    //         $query = CustomerType::with([
    //             'createdBy' => function ($q) {
    //                 $q->select('id', 'name', 'username');
    //             },
    //             'updatedBy' => function ($q) {
    //                 $q->select('id', 'name', 'username');
    //             }
    //         ])->orderByDesc('id');
    //         foreach ($filters as $field => $value) {
    //             if (!empty($value)) {
    //                 if (in_array($field, ['customer_type_name', 'customer_type_code', 'status'])) {
    //                     $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
    //                 } else {
    //                     $query->where($field, $value);
    //                 }
    //             }
    //         }

    //         return $query->paginate($perPage);
    //     } catch (Exception $e) {
    //         Log::error("Error fetching customer types: " . $e->getMessage());
    //         throw new Exception("Failed to fetch customer types.");
    //     }
    // }

    public function getAll(array $filters = [], int $perPage = 10)
    {
        try {
            /**
             * 🔹 DROPDOWN MODE
             */
            if (!empty($filters['dropdown']) && $filters['dropdown'] === true) {
                return CustomerType::query()
                    ->select([
                        'id',
                        'name',
                        'code',
                        'status'
                    ])
                    ->where('status', 1)
                    ->orderBy('name')
                    ->get();
            }

            /**
             * 🔹 NORMAL LIST MODE
             */
            $query = CustomerType::with([
                'createdBy:id,name,username',
                'updatedBy:id,name,username',
            ])->orderByDesc('id');

            foreach ($filters as $field => $value) {
                if (!empty($value)) {
                    if (in_array($field, ['name', 'code', 'status'])) {
                        $query->whereRaw(
                            "LOWER({$field}) LIKE ?",
                            ['%' . strtolower($value) . '%']
                        );
                    } elseif ($field !== 'dropdown') {
                        $query->where($field, $value);
                    }
                }
            }

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            dd($e);
            Log::error('Error fetching customer types: ' . $e->getMessage());
            throw new \Exception('Failed to fetch customer types.');
        }
    }


    /**
     * Get customer type by ID
     */
    public function getById($id)
    {
        try {
            return CustomerType::findOrFail($id);
        } catch (Exception $e) {
            Log::error("Error fetching customer type by ID: " . $e->getMessage());
            throw new Exception("Customer type not found.");
        }
    }

    /**
     * Create a new customer type
     */
    // public function create(array $data, $userId)
    // {
    //     try {
    //         $lastCategory = CustomerType::withTrashed()->latest('id')->first(); 
    //         $nextNumber = $lastCategory ? $lastCategory->id + 1 : 1;
    //         $data['code'] = 'CUST' . str_pad($nextNumber, 2, '0', STR_PAD_LEFT); 
    //         $data['created_user'] = $userId;
    //         return CustomerType::create($data);
    //     } catch (Exception $e) {
    //         Log::error("Error creating customer type: " . $e->getMessage());
    //         throw new Exception("Failed to create customer type.");
    //     }
    // }
    public function create(array $data, $userId)
    {
        DB::beginTransaction();
        try {
            $lastCategory = CustomerType::withTrashed()->latest('id')->first();
            $nextNumber = $lastCategory ? $lastCategory->id + 1 : 1;
            $data['code'] = !empty($data['osa_code'])
                ? $data['osa_code']
                : 'CSTP' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            $data['created_user'] = $userId;
            $data['updated_user'] = $userId;

            $customerType = CustomerType::create($data);

            DB::commit();

            return $customerType->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error creating customer type: " . $e->getMessage(), ['data' => $data]);
            throw new Exception("Failed to create customer type: " . $e->getMessage());
        }
    }
    /**
     * Update customer type
     */
    public function update($id, array $data, $userId)
    {
        try {
            $customerType = CustomerType::findOrFail($id);
            $data['updated_user'] = $userId;
            $customerType->update($data);
            return $customerType;
        } catch (Exception $e) {
            Log::error("Error updating customer type: " . $e->getMessage());
            throw new Exception("Failed to update customer type.");
        }
    }

    /**
     * Delete customer type
     */
    public function delete($id)
    {
        try {
            $customerType = CustomerType::find($id);

            if (!$customerType) {
                return response()->json([
                    'status'  => false,
                    'code'    => 200,
                    'message' => 'Data not found',
                ]);
            }

            $customerType->delete();

            return response()->json([
                'status'  => true,
                'code'    => 200,
                'message' => 'Customer type deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error("Error deleting customer type: " . $e->getMessage());

            return response()->json([
                'status'  => false,
                'code'    => 500,
                'message' => 'Failed to delete customer type.',
            ]);
        }
    }
}
