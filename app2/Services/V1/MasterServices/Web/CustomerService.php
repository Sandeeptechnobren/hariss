<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\Customer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class CustomerService
{
    /**
     * Get paginated list of customers with relationships
     */
    public function list(int $perPage = 10)
    {
        try {
            return Customer::with([
                'salesman',
                'route',
                'region',
                'area'
            ])->paginate($perPage);
        } catch (Exception $e) {
            Log::error('Failed to fetch customer list', ['exception' => $e]);
            throw new Exception("Failed to fetch customer list: " . $e->getMessage());
        }
    }

    public function generateCode(): string
    {
        do {
            $last = Customer::withTrashed()->latest('id')->first();
            $nextNumber = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $osa_code = 'C' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        } while (Customer::withTrashed()->where('osa_code', $osa_code)->exists());

        return $osa_code;
    }

    public function create(array $data): Customer
    {
        DB::beginTransaction();
        try {
            $data = array_merge($data, [
                'osa_code'     => $data['osa_code'] ?? $this->generateCode(),
                'uuid'         => $data['uuid'] ?? Str::uuid()->toString(),
            ]);

            $customer = Customer::create($data);
            DB::commit();
            return $customer->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create customer', ['data' => $data, 'exception' => $e]);
            throw new Exception("Failed to create customer: " . $e->getMessage());
        }
    }

    /**
     * Find customer by UUID with relationships
     */
    public function findByUuid(string $uuid): Customer
    {
        try {
            $customer = Customer::where('uuid', $uuid)
                ->with(['salesman', 'agent', 'region', 'area',])
                ->first();

            if (!$customer) {
                throw new ModelNotFoundException("Customer not found with UUID: {$uuid}");
            }

            return $customer;
        } catch (ModelNotFoundException $e) {
            throw $e; // let controller handle 404
        } catch (Exception $e) {
            Log::error('Failed to find customer', ['uuid' => $uuid, 'exception' => $e]);
            throw new Exception("Failed to find customer: " . $e->getMessage());
        }
    }

    /**
     * Update existing customer by UUID
     */
    public function update(string $uuid, array $data): Customer
    {
        DB::beginTransaction();
        try {
            $customer = $this->findByUuid($uuid);
            $customer->update($data);

            DB::commit();
            return $customer->fresh(['salesman', 'route', 'region', 'area']);
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update customer', ['uuid' => $uuid, 'data' => $data, 'exception' => $e]);
            throw new Exception("Failed to update customer: " . $e->getMessage());
        }
    }

    /**
     * Delete customer by UUID
     */
    public function delete(string $uuid): ?Customer
    {
        DB::beginTransaction();
        try {
            $customer = Customer::where('uuid', $uuid)->first();

            if (!$customer) {
                return null; // Controller can handle response
            }

            $customer->delete();

            DB::commit();
            return $customer;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete customer', ['uuid' => $uuid, 'exception' => $e]);
            throw new Exception("Failed to delete customer: " . $e->getMessage());
        }
    }

    public function globalSearch($perPage = 10, $searchTerm = null)
    {
        try {
            $query = Customer::with([
                'salesman:id,osa_code,name',
                'route:id,route_code,route_name',
                'region:id,region_code,region_name',
                'area:id,area_code,area_name',
                'fridgeRelation:id,fridge_code',
                'customerTypeRelation:id,code,name',
                'customerCategory:id,customer_category_code,customer_category_name',
                'customerSubCategory:id,customer_sub_category_code,customer_sub_category_name',
                'outletChannel:id,outlet_channel_code,outlet_channel',
                'createdBy:id,firstname,lastname,username',
                'updatedBy:id,firstname,lastname,username',
            ]);

            if (!empty($searchTerm)) {
                $searchTerm = strtolower($searchTerm);
                $likeSearch = '%' . $searchTerm . '%';

                $columns = [
                    'osa_code',
                    'name',
                    'email',
                    'owner_name',
                    'street',
                    'city',
                    'state',
                    'status',
                    'address_1',
                    'address_2',
                ];

                $query->where(function ($q) use ($columns, $likeSearch) {
                    foreach ($columns as $col) {
                        $q->orWhereRaw("LOWER(CAST($col AS TEXT)) LIKE ?", [$likeSearch]);
                    }
                });
            }

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            Log::error("Customer search failed", [
                'error' => $e->getMessage(),
                'search' => $searchTerm,
            ]);

            throw new \Exception("Failed to search Customer: " . $e->getMessage(), 0, $e);
        }
    }
}
