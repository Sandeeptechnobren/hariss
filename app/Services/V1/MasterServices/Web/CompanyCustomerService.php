<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\CompanyCustomer;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Helpers\DataAccessHelper;

class CompanyCustomerService
{
    // public function create(array $data)
    // {
    //     DB::beginTransaction();
    //     try {
    //         $data['created_user'] = Auth::id();
    //         $data['updated_user'] = Auth::id();
    //         $customer = CompanyCustomer::create($data);
    //         DB::commit();
    //         return $customer;
    //     } catch (\Throwable $e) {
    //         DB::rollBack();

    //         Log::error('Failed to create CompanyCustomer', [
    //             'message' => $e->getMessage(),
    //             'trace'   => $e->getTraceAsString(),
    //             'data'    => $data,
    //         ]);

    //         return [
    //             'status'  => false,
    //             'message' => 'Failed to create company customer.',
    //             'error'   => $e->getMessage(),
    //         ];
    //     }
    // }
    public function create(array $data)
    {
        DB::beginTransaction();

        try {
            // ✅ Add creator info
            $data['created_user'] = Auth::id();
            $data['updated_user'] = Auth::id();

            // ✅ Create record
            $customer = CompanyCustomer::create($data);

            DB::commit();
            return $customer;
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('❌ Failed to create Company Customer', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
            ]);

            return [
                'status' => false,
                'message' => 'Failed to create company customer.',
                'error' => $e->getMessage(),
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

    public function find(string $uuid): ?CompanyCustomer
    {
        return CompanyCustomer::select([
            'id',
            'uuid',
            'sap_code',
            'osa_code',
            'business_name',
            'company_type',
            'town',
            'payment_type',
            'creditday',
            'tin_no',
            'creditlimit',
            'bank_guarantee_name',
            'bank_guarantee_amount',
            'bank_guarantee_from',
            'bank_guarantee_to',
            'credit_limit_validity',
            'language',
            'contact_number',
            'landmark',
            'district',
            'region_id',
            'area_id',
            'business_type',
            'totalcreditlimit',
            'distribution_channel_id',
            'status',
            'created_user',
            'updated_user',
        ])
            ->with([
                'companyType:id,code,name',
                'getRegion:id,region_code,region_name',
                'getArea:id,area_code,area_name',
                'getOutletChannel:id,outlet_channel_code,outlet_channel',
            ])
            ->where('uuid', $uuid)->first();
    }


    // public function getAll(int $perPage = 50, array $filters = [], bool $dropdown = false)
    // {
    //     try {
    //         $user = auth()->user();
    //         if ($dropdown) {
    //             return CompanyCustomer::select(['id', 'business_name', 'osa_code'])
    //                 ->when(!empty($filters['region_id']), fn($q) => $q->where('region_id', $filters['region_id']))
    //                 ->when(!empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
    //                 ->orderBy('id', 'desc')
    //                 ->get();
    //         }
    //         $query = CompanyCustomer::select([
    //             'id',
    //             'uuid',
    //             'sap_code',
    //             'osa_code',
    //             'business_name',
    //             'company_type',
    //             'language',
    //             'town',
    //             'landmark',
    //             'district',
    //             'payment_type',
    //             'creditday',
    //             'tin_no',
    //             'creditlimit',
    //             'bank_guarantee_name',
    //             'bank_guarantee_amount',
    //             'bank_guarantee_from',
    //             'bank_guarantee_to',
    //             'totalcreditlimit',
    //             'credit_limit_validity',
    //             'region_id',
    //             'area_id',
    //             'distribution_channel_id',
    //             'status',
    //             'business_type',
    //             'contact_number',
    //             'created_user',
    //             'updated_user',
    //         ])
    //             ->with([
    //                 'getRegion:id,region_code,region_name',
    //                 'getArea:id,area_code,area_name',
    //             ])
    //             ->when(!empty($filters['region_id']), fn($q) => $q->where('region_id', $filters['region_id']))
    //             ->when(!empty($filters['area_id']), fn($q) => $q->where('area_id', $filters['area_id']))
    //             ->when(!empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
    //             ->latest();
    //         $query = DataAccessHelper::filterCompanyCustomers($query, $user);
    //         return $query->paginate($perPage);
    //     } catch (\Exception $e) {
    //         Log::error('❌ Failed to fetch company customers', [
    //             'message' => $e->getMessage(),
    //             'filters' => $filters,
    //         ]);
    //         throw new \Exception("Failed to fetch company customers: " . $e->getMessage());
    //     }
    // }

    public function getAll(int $perPage = 50, array $filters = [], bool $dropdown = false)
    {
        try {
            $user = auth()->user();
            if ($dropdown) {
                return CompanyCustomer::select(['id', 'business_name', 'osa_code'])
                    ->when(!empty($filters['region_id']), fn($q) => $q->where('region_id', $filters['region_id']))
                    ->when(!empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
                    ->orderBy('id', 'desc')
                    ->get();
            }
            $query = CompanyCustomer::select([
                'id',
                'uuid',
                'sap_code',
                'osa_code',
                'business_name',
                'company_type',
                'language',
                'town',
                'landmark',
                'district',
                'payment_type',
                'creditday',
                'tin_no',
                'creditlimit',
                'bank_guarantee_name',
                'bank_guarantee_amount',
                'bank_guarantee_from',
                'bank_guarantee_to',
                'totalcreditlimit',
                'credit_limit_validity',
                'region_id',
                'area_id',
                'distribution_channel_id',
                'status',
                'business_type',
                'contact_number',
                'created_user',
                'updated_user',
            ])
                ->with([
                    'getRegion:id,region_code,region_name',
                    'getArea:id,area_code,area_name',
                ])
                ->when(!empty($filters['region_id']), fn($q) => $q->where('region_id', $filters['region_id']))
                ->when(!empty($filters['area_id']), fn($q) => $q->where('area_id', $filters['area_id']))
                ->when(isset($filters['status']), function ($q) use ($filters) {

                    if ((int) $filters['status'] === 1) {
                        // ✅ Active (1) first, then Inactive (2)
                        $q->orderByRaw('CASE WHEN status = 1 THEN 0 ELSE 1 END');
                    } else {
                        // ✅ Inactive (2) first, then Active (1)
                        $q->orderByRaw('CASE WHEN status = 2 THEN 0 ELSE 1 END');
                    }
                })
                ->orderBy('id', 'desc');

            $query = DataAccessHelper::filterCompanyCustomers($query, $user);
            // dd($query->toSql(), $query->getBindings());
            return $query->paginate($perPage);
        } catch (\Exception $e) {
            Log::error('❌ Failed to fetch company customers', [
                'message' => $e->getMessage(),
                'filters' => $filters,
            ]);
            throw new \Exception("Failed to fetch company customers: " . $e->getMessage());
        }
    }
    public function globalSearch(?string $search)
    {
        $query = CompanyCustomer::query()
            ->with(['getRegion', 'getArea', 'getOutletChannel', 'companyType', 'country']);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {

                $q->where(function ($q2) use ($search) {
                    $q2->where('osa_code', 'ILIKE', "%{$search}%")
                        ->orWhere('sap_code', 'ILIKE', "%{$search}%")
                        ->orWhere('business_name', 'ILIKE', "%{$search}%")
                        ->orWhere('language', 'ILIKE', "%{$search}%")
                        ->orWhere('town', 'ILIKE', "%{$search}%")
                        ->orWhere('landmark', 'ILIKE', "%{$search}%")
                        ->orWhere('district', 'ILIKE', "%{$search}%")
                        ->orWhere('payment_type', 'ILIKE', "%{$search}%")
                        ->orWhere('tin_no', 'ILIKE', "%{$search}%")
                        ->orWhere('bank_guarantee_name', 'ILIKE', "%{$search}%")
                        ->orWhere('bank_guarantee_from', 'ILIKE', "%{$search}%")
                        ->orWhere('bank_guarantee_to', 'ILIKE', "%{$search}%")
                        ->orWhere('contact_number', 'ILIKE', "%{$search}%")

                        ->orWhereRaw('creditday::text ILIKE ?', ["%{$search}%"])
                        ->orWhereRaw('creditlimit::text ILIKE ?', ["%{$search}%"])
                        ->orWhereRaw('totalcreditlimit::text ILIKE ?', ["%{$search}%"])
                        ->orWhereRaw('status::text ILIKE ?', ["%{$search}%"])
                        ->orWhereRaw('distribution_channel_id::text ILIKE ?', ["%{$search}%"]);
                });

                $q->orWhereHas('getRegion', function ($rq) use ($search) {
                    $rq->where('region_name', 'ILIKE', "%{$search}%")
                        ->orWhere('region_code', 'ILIKE', "%{$search}%");
                });

                $q->orWhereHas('getArea', function ($aq) use ($search) {
                    $aq->where('area_name', 'ILIKE', "%{$search}%")
                        ->orWhere('area_code', 'ILIKE', "%{$search}%");
                });

                $q->orWhereHas('getOutletChannel', function ($cq) use ($search) {
                    $cq->where('outlet_channel_code', 'ILIKE', "%{$search}%")
                        ->orWhere('outlet_channel', 'ILIKE', "%{$search}%");
                });

                $q->orWhereHas('companyType', function ($ctq) use ($search) {
                    $ctq->where('code', 'ILIKE', "%{$search}%")
                        ->orWhere('name', 'ILIKE', "%{$search}%");
                });
            });
        }

        return $query->latest()->paginate(50);
    }
    public function getByCustomerType(int $customerType, int $perPage = 50)
    {
        try {
            return CompanyCustomer::where('customer_type', $customerType)
                ->where('status', 1)                     // Only active customers
                ->orderBy('id')
                ->paginate($perPage);
        } catch (Throwable $e) {

            Log::error('Customer fetch failed', [
                'customer_type' => $customerType,
                'error'         => $e->getMessage(),
            ]);

            throw new \Exception('Failed to fetch customers', 0, $e);
        }
    }
}
