<?php

namespace App\Services\V1\Settings\Web;

use App\Models\DiscountType;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Exception;

class DiscountTypeService
{
// public function getAll(array $filters = [], int $perPage = 10): LengthAwarePaginator
// {
//     $query = DiscountType::with([
//         'createdBy:id,firstname,lastname,username',
//         'updatedBy:id,firstname,lastname,username'
//     ]);

//     // Apply filters
//     foreach ($filters as $field => $value) {
//         if (!empty($value)) {
//             if (in_array($field, ['discount_type_code', 'discount_type_name', 'status'])) {
//                 $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
//             } else {
//                 $query->where($field, $value);
//             }
//         }
//     }

//     return $query->paginate($perPage);
// } 
public function getAll(array $filters = [], int $perPage = 10): LengthAwarePaginator
{
    $query = DiscountType::with([
        'createdBy:id,name,username',
        'updatedBy:id,name,username'
    ])->orderByDesc('id');

    foreach ($filters as $field => $value) {
        if (!empty($value)) {
            // Case-insensitive search for string fields
            if (in_array($field, ['discount_type_code', 'discount_type_name', 'status'])) {
                $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
            } else {
                $query->where($field, $value);
            }
        }
    }

    return $query->orderByDesc('id')->paginate($perPage);
}

    public function getById($id)
    {
        return DiscountType::findOrFail($id);
    }

public function create(array $data)
    {
        DB::beginTransaction();
        try {
            $userId = auth()->id();
            if (!$userId) {
                throw new Exception("Unauthenticated user");
            }
            if (empty($data['discount_code'])) {
                $data['discount_code'] = $this->generateCode();
            }

            $data['created_user'] = $userId;
            $data['updated_user'] = $userId;

            $discountType = DiscountType::create($data);

            DB::commit();

            return $discountType->fresh(); // return fresh instance with timestamps
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating DiscountType: ' . $e->getMessage(), ['data' => $data]);
            throw new Exception("Failed to create discount type: " . $e->getMessage());
        }
    }

public function update($id, array $data)
{
    DB::beginTransaction();

    try {
        $data['updated_user'] = auth()->id();
        $discountType = DiscountType::findOrFail($id);
        $discountType->update($data);
        DB::commit();
        return $discountType->fresh();
    } catch (Exception $e) {
        DB::rollBack();
        Log::error('Error updating DiscountType: ' . $e->getMessage(), [
            'data' => $data,
            'id'   => $id,
        ]);
        throw new Exception("Failed to update discount type: " . $e->getMessage());
    }
}

public function delete($id): bool
{
    
    DB::beginTransaction();
    try {
        $discountType = DiscountType::where('id',$id)->delete();

        DB::commit();
        return true;
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        DB::rollBack();
        throw $e;
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error deleting DiscountType: ' . $e->getMessage(), ['id' => $id]);
        throw new \Exception("Failed to delete discount type with ID: {$id}");
    }
}



private function generateCode(): string
    {
        $lastCode = DiscountType::withTrashed()
            ->orderByDesc('id')
            ->value('discount_code');

        if ($lastCode) {
            $number = (int) substr($lastCode, 3); // remove 'DTC' prefix
            $nextNumber = $number + 1;
        } else {
            $nextNumber = 1;
        }

        return 'DTC' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
}
