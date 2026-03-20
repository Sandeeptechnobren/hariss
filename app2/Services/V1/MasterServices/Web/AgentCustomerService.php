<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\AgentCustomer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Error;

class AgentCustomerService
{

public function getAll(int $perPage = 10, array $filters = [])
{
    try {
        $query = AgentCustomer::with([
            'customertype:id,code,name',
            'region:id,region_code,region_name',
            'area:id,area_code,area_name',
            'outlet_channel:id,outlet_channel_code,outlet_channel',
            'category:id,customer_category_code,customer_category_name',
            'subcategory:id,customer_category_id,customer_sub_category_name,customer_sub_category_code',
            'route:id,route_code,route_name',
            'getWarehouse:id,warehouse_code,warehouse_name'
        ])->latest();

        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                if (in_array($field, ['osa_code', 'name', 'owner_name', 'business_name'])) {
                    $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                } else {
                    $query->where($field, $value);
                }
            }
        }
        return $query->paginate($perPage);
    } catch (\Throwable $e) {
        Log::error("Failed to fetch customers", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'filters' => $filters,
        ]);
        throw new \Exception("Something went wrong while fetching customers, please try again.");
    }
}


    /**
     * Generate unique customer code
     */
    public function generateCode(): string
    {
        do {
            $last = AgentCustomer::withTrashed()->latest('id')->first();
            $nextNumber = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $osa_code = 'AC' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        } while (AgentCustomer::withTrashed()->where('osa_code', $osa_code)->exists());

        return $osa_code;
    }

    /**
     * Create a new customer
     */
    public function create(array $data)
    {
        DB::beginTransaction();

        try {
            $data = array_merge($data, [
                'uuid'         => $data['uuid'] ?? Str::uuid()->toString(),
            ]);
            if(!isset($data['osa_code']) || empty($data['osa_code'])) {
                $data['osa_code'] = $this->generateCode();
            }
            $customer = AgentCustomer::create($data);

            DB::commit();
            return $customer;
        } catch (Throwable $e) {
            DB::rollBack();

            $friendlyMessage = $e instanceof Error ? "Server error occurred." : "Something went wrong, please try again.";

            Log::error("Customer creation failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data'  => $data,
                'user'  => Auth::id(),
            ]);

            throw new \Exception($friendlyMessage, 0, $e);
        }
    }

    /**
     * Find customer by UUID
     */
    // public function findByUuid(string $uuid): ?AgentCustomer
    // {
    //     if (!Str::isUuid($uuid)) {
    //         return null;
    //     }

    //     return AgentCustomer::where('uuid', $uuid)->first();
    // }
    public function findByUuid(string $uuid): ?AgentCustomer
        {
            if (!Str::isUuid($uuid)) {
                return null;
            }

            return AgentCustomer::with([
                'customertype:id,code,name',
                'region:id,region_code,region_name',
                'area:id,area_code,area_name',
                'outlet_channel:id,outlet_channel_code,outlet_channel',
                'category:id,customer_category_code,customer_category_name',
                'subcategory:id,customer_category_id,customer_sub_category_name,customer_sub_category_code',
                'route:id,route_code,route_name',
                'getWarehouse:id,warehouse_code,warehouse_name'
            ])->where('uuid', $uuid)->first();
        }

// public function updateByUuid(string $uuid, array $validated)
// {
//     $customer = $this->findByUuid($uuid);
//     if (!$customer) {
//         throw new Exception("Customer not found or invalid UUID: {$uuid}");
//     }

//     DB::beginTransaction();

//     try {
//         $data['updated_user'] = Auth::id();
//         $customer->update($data);
//         DB::commit();

//         return $customer->fresh();
//     } catch (Throwable $e) {
//         DB::rollBack();

//         $friendlyMessage = $e instanceof Error ? "Server error occurred." : "Something went wrong, please try again.";

//         Log::error("Customer update failed", [
//             'error'   => $e->getMessage(),
//             'uuid'    => $uuid,
//             'payload' => $data,
//         ]);

//         throw new Exception($friendlyMessage, 0, $e);
//     }
// }
public function updateByUuid(string $uuid, array $validated)
{
    $customer = $this->findByUuid($uuid);
    if (!$customer) {
        throw new \Exception("Customer not found or invalid UUID: {$uuid}");
    }
    DB::beginTransaction();
    try {
        $data = array_merge($validated, [
            'updated_user' => Auth::id(),
        ]);
        $customer->update($data);

        DB::commit();

        // Return fresh copy
        return $customer->fresh();
    } catch (Throwable $e) {
        DB::rollBack();
        $friendlyMessage = $e instanceof Error
            ? "Server error occurred."
            : "Something went wrong, please try again.";

        Log::error("Customer update failed", [
            'error'   => $e->getMessage(),
            'uuid'    => $uuid,
            'payload' => $validated,
        ]);
        throw new \Exception($friendlyMessage, 0, $e);
    }
}

    /**
     * Soft delete customer by UUID
     */
    public function deleteByUuid(string $uuid): void
    {
        $customer = $this->findByUuid($uuid);
        if (!$customer) {
            throw new \Exception("Customer not found or invalid UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $customer->delete();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            $friendlyMessage = $e instanceof Error ? "Server error occurred." : "Something went wrong, please try again.";

            Log::error("Customer delete failed", [
                'error' => $e->getMessage(),
                'uuid'  => $uuid,
            ]);

            throw new \Exception($friendlyMessage, 0, $e);
        }
    }
}
