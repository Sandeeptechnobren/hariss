<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\CompanyCustomer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CompanyCustomerService
{
    public function create(array $data)
    {
        DB::beginTransaction();
        try {
            $data['created_user'] = Auth::id();
            $data['updated_user'] = Auth::id();
            $customer = CompanyCustomer::create($data);
            DB::commit();
            return $customer;
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Failed to create CompanyCustomer', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'data'    => $data,
            ]);

            return [
                'status'  => false,
                'message' => 'Failed to create company customer.',
                'error'   => $e->getMessage(),
            ];
        }
    }

    public function update(CompanyCustomer $customer, array $data): CompanyCustomer
    {
        $customer->update($data);
        return $customer;
    }

    public function delete(CompanyCustomer $customer): bool
    {
        return $customer->delete();
    }

    public function findById(int $id): ?CompanyCustomer
    {
        return CompanyCustomer::with([
            'country:id,country_code,country_name',
            'getRegion:id,region_code,region_name',
            'getArea:id,area_code,area_name',
            'getOutletChannel:id,outlet_channel_code,outlet_channel',
            'createdBy:id,firstname,lastname,username',
            'updatedBy:id,firstname,lastname,username',
        ])->find($id);
    }

    public function getAll(int $perPage = 10)
    {
        return CompanyCustomer::with([
            'country:id,country_code,country_name',
            'getRegion:id,region_code,region_name',
            'getArea:id,area_code,area_name',
            'getOutletChannel:id,outlet_channel_code,outlet_channel',
            'createdBy:id,firstname,lastname,username',
            'updatedBy:id,firstname,lastname,username',
        ])->latest()->paginate($perPage);
    }
}
