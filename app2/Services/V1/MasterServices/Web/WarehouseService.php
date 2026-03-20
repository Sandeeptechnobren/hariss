<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WarehouseService
{

public function getAll($perPage = 10, $filters = [])
{
    try {
        $query = Warehouse::select(
            'uuid',
            'id',
        'warehouse_code',   
        'warehouse_type',
        'warehouse_name',
        'owner_name',
        'owner_number',
        'owner_email',
        'agreed_stock_capital',
        'location',
        'city',
        'warehouse_manager',
        'warehouse_manager_contact',
        'vat_no',
        'company',
        'warehouse_email',
        'region_id',
        'area_id',
        'latitude',
        'longitude',
        'agent_customer',
        'town_village',
        'street',
        'landmark',
        'is_efris',
        'password',
        'is_branch',
        'branch_id',
        'status',
        'created_user',
        'updated_user',
        )->with([
            'region' => function ($q) {
                $q->select('id', 'region_name');
            }, 
            'area' => function ($q) {
                $q->select('id', 'area_code', 'area_name', 'region_id');
            },
            'createdBy' => function ($q) {
                $q->select('id', 'firstname', 'lastname', 'username');
            },
            'updatedBy' => function ($q) {
                $q->select('id', 'firstname', 'lastname', 'username');
            },
            'getCompanyCustomer' => function ($q) {
                $q->select('id', 'customer_code', 'business_name', 'owner_name');
            },
            'getCompany:id,company_code,company_name'

        ]);

        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                if ($field === 'created_date_from') {
                    $query->whereDate('created_date', '>=', $value);
                } elseif ($field === 'created_date_to') {
                    $query->whereDate('created_date', '<=', $value);
                } elseif (in_array($field, [
                    'warehouse_code', 'warehouse_name', 'owner_name', 'owner_number', 'owner_email',
                    'warehouse_manager', 'warehouse_manager_contact', 'tin_no', 'registation_no',
                    'business_type', 'warehouse_type', 'city', 'location', 'address', 'stock_capital',
                    'deposite_amount', 'latitude', 'longitude', 'device_no', 'p12_file',
                    'password', 'branch_id', 'is_branch', 'invoice_sync', 'is_efris',
                    'district', 'town_village', 'street', 'landmark'
                ])) {
                    $query->whereRaw("LOWER($field) LIKE ?", ['%' . strtolower($value) . '%']);
                } elseif (in_array($field, ['region_id', 'sub_region_id', 'area_id', 'status'])) {
                    $query->where($field, $value);
                } else {
                    $query->where($field, $value);
                }
            }
        }
    $query->orderBy('created_date', 'desc');
        return $query->paginate($perPage);

    } catch (\Exception $e) {
        throw new \Exception("Failed to fetch warehouses: " . $e->getMessage());
    }
}


    public function getAllActive(): Collection
    {
        return Warehouse::active()
            ->with([
                'region',
                'area',
                'createdBy',
                'updatedBy'
            ])->get();
    }

    public function getByType(string $type): Collection
    {
        return Warehouse::byType($type)
            ->with([
                'region',
                'area',
                'createdBy',
                'updatedBy'
            ])->get();
    }

public function create(array $data): Warehouse
{
    $user=Auth::user()->id;
    if (empty($data['warehouse_code'])) {
        $lastWarehouse = Warehouse::latest('id')->first();
        $nextId = $lastWarehouse ? $lastWarehouse->id + 1 : 1;
        $data['warehouse_code'] = 'WHC' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
        
    }
    $data['created_user']=$user;
    $data['updated_user']=$user;
    return Warehouse::create($data);
}


public function find(int $id): Warehouse
    {
        return Warehouse::select(
            'uuid',
            'id',
        'warehouse_code',   
        'warehouse_type',
        'warehouse_name',
        'owner_name',
        'owner_number',
        'owner_email',
        'agreed_stock_capital',
        'location',
        'city',
        'warehouse_manager',
        'warehouse_manager_contact',
        'tin_no',
        'company',
        'warehouse_email',
        'region_id',
        'area_id',
        'latitude',
        'longitude',
        'agent_customer',
        'town_village',
        'street',
        'landmark',
        'is_efris',
        'password',
        'is_branch',
        'branch_id',
        'status',
        'created_user',
        'updated_user',
        )->with([
            'region:id,region_name,region_code', 
            'area:id,area_code,area_name',
            'createdBy:id,firstname,lastname,username',
            'updatedBy:id,firstname,lastname,username',
            'getCompanyCustomer:id,customer_code,business_name,owner_name',
            'getCompany:id,company_code,company_name'
        ])->findOrFail($id);
    }

// public function update(int $id, array $data): Warehouse
//     {
//         $warehouse = Warehouse::findOrFail($id);
//         $warehouse->update($data);
//         return $warehouse->load([
//             'region',
//             'area',
//             'createdBy',
//             'updatedBy'
//         ]);
//     }
public function update(int $id, array $data): Warehouse
{
    $warehouse = Warehouse::findOrFail($id);
    $warehouse->update($data);

    return Warehouse::select(
        'uuid',
        'id',
        'warehouse_code',   
        'warehouse_type',
        'warehouse_name',
        'owner_name',
        'owner_number',
        'owner_email',
        'agreed_stock_capital',
        'location',
        'city',
        'warehouse_manager',
        'warehouse_manager_contact',
        'vat_no',
        'company',
        'warehouse_email',
        'region_id',
        'area_id',
        'latitude',
        'longitude',
        'agent_customer',
        'town_village',
        'street',
        'landmark',
        'is_efris',
        'password',
        'is_branch',
        'branch_id',
        'status',
        'created_user',
        'updated_user',
    )->with([
        'region:id,region_name', 
        'area:id,area_code,area_name',
        'createdBy:id,firstname,lastname,username',
        'updatedBy:id,firstname,lastname,username',
        'getCompanyCustomer:id,customer_code,business_name,owner_name',
        'getCompany:id,company_code,company_name'
    ])->findOrFail($id);
}




public function delete(int $id): bool
{
    $warehouse = Warehouse::findOrFail($id);
    $deleted = $warehouse->delete();
    if ($deleted) {
        $logData = sprintf(
            "[%s] Warehouse ID %d (Code: %s) deleted by User ID %d\n",
            now()->toDateTimeString(),
            $warehouse->id,
            $warehouse->warehouse_code,
            auth()->id() ?? 0
        );
        Storage::append('logs/deleted_warehouses.log', $logData);
    }

    return $deleted;
}


    public function updateStatus(int $id, int $status): Warehouse
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->update(['status' => $status]);
        return $warehouse;
    }

    public function getByRegion(int $regionId): Collection
    {
        return Warehouse::where('region_id', $regionId)
            ->with([
                'region',
                'area',
                'createdBy',
                'updatedBy'
            ])->get();
    }

    public function getByArea(int $areaId): Collection
    {
        return Warehouse::where('area_id', $areaId)
            ->with([
                'region',
                'area',
                'createdBy',
                'updatedBy'
            ])->get();
    }
 

    public function globalSearch($perPage = 10, $keyword = null)
        {
            try {
                $query = Warehouse::with([
                    'region:id,region_name',
                    'area:id,area_code,area_name,region_id',
                    'createdBy:id,firstname,lastname,username',
                    'updatedBy:id,firstname,lastname,username',
                    'getCompanyCustomer:id,customer_code,business_name,owner_name',
                ]);

                if (!empty($keyword)) {
                    $query->where(function ($q) use ($keyword) {
                        $searchableFields = [
                            'warehouse_code', 'warehouse_name', 'owner_name', 'owner_number', 'owner_email',
                            'warehouse_manager', 'warehouse_manager_contact', 'tin_no', 'registation_no',
                            'business_type', 'warehouse_type', 'city', 'location', 'address', 'stock_capital',
                            'deposite_amount', 'latitude', 'longitude', 'device_no', 'p12_file',
                            'password', 'branch_id', 'is_branch', 'invoice_sync', 'is_efris',
                            'district', 'town_village', 'street', 'landmark'
                        ];

                        foreach ($searchableFields as $field) {
                            $q->orWhereRaw("CAST({$field} AS TEXT) ILIKE ?", ['%' . $keyword . '%']);
                        }
                        $q->orWhereRaw('CAST(status AS TEXT) ILIKE ?', ['%' . $keyword . '%']);
                        $q->orWhereRaw('CAST(region_id AS TEXT) ILIKE ?', ['%' . $keyword . '%']);
                        $q->orWhereRaw('CAST(area_id AS TEXT) ILIKE ?', ['%' . $keyword . '%']);
                        $q->orWhereRaw('CAST(company_customer_id AS TEXT) ILIKE ?', ['%' . $keyword . '%']);
                        $q->orWhereRaw('CAST(created_user AS TEXT) ILIKE ?', ['%' . $keyword . '%']);
                        $q->orWhereRaw('CAST(updated_user AS TEXT) ILIKE ?', ['%' . $keyword . '%']);
                        $q->orWhereRaw('CAST(created_date AS TEXT) ILIKE ?', ['%' . $keyword . '%']);
                        $q->orWhereRaw('CAST(updated_date AS TEXT) ILIKE ?', ['%' . $keyword . '%']);
                        $q->orWhereRaw('CAST(deleted_date AS TEXT) ILIKE ?', ['%' . $keyword . '%']);
                    });
                }

                return $query->paginate($perPage);

            } catch (\Exception $e) {
                throw new \Exception("Failed to search warehouses: " . $e->getMessage());
            }
        }



    public function updateWarehousesStatus(array $warehouseIds, $status)
    {
        $updated = Warehouse::whereIn('id', $warehouseIds)->update(['status' => $status]);
        return $updated > 0;
    }



}
