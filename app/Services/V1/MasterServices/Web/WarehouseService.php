<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\Warehouse;
use App\Models\AgentCustomer;
use App\Models\Route;
use App\Models\Salesman;
use App\Models\Location;
use App\Models\Agent_Transaction\InvoiceHeader;
use App\Models\Agent_Transaction\ReturnHeader;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Helpers\DataAccessHelper;
use Illuminate\Http\Request;

class WarehouseService
{
    public function getAll($perPage = 50, $filters = [], $dropdown = false)
    {
        try {
            $user = auth()->user();

            $query = Warehouse::query()
                ->with([
                    'region:id,region_name,region_code',
                    'area:id,area_code,area_name,region_id',
                    'createdBy:id,name,username',
                    'updatedBy:id,name,username',
                    'getCompanyCustomer:id,osa_code,business_name,contact_number',
                    'getCompany:id,company_code,company_name,email',
                    'locationRelation:id,code,name'
                ]);

            // ✅ APPLY DATA ACCESS HELPER (IMPORTANT 🔥)
            $query = DataAccessHelper::filterWarehouses($query, $user);

            // ✅ FILTERS (FIXED)

            // region_id
            if (!empty($filters['region_id'])) {
                $regionIds = is_array($filters['region_id'])
                    ? $filters['region_id']
                    : explode(',', $filters['region_id']);

                $regionIds = array_filter(array_map('intval', $regionIds));

                $query->whereIn('region_id', $regionIds);
            }

            // area_id
            if (!empty($filters['area_id'])) {
                $areaIds = is_array($filters['area_id'])
                    ? $filters['area_id']
                    : explode(',', $filters['area_id']);

                $areaIds = array_filter(array_map('intval', $areaIds));

                $query->whereIn('area_id', $areaIds);
            }

            // status (important for 0)
            if (array_key_exists('status', $filters) && $filters['status'] !== '') {
                $query->where('status', (int) $filters['status']);
            }

            // 🔒 DO NOT TOUCH (as per your instruction)

            if ($dropdown) {
                return $query
                    ->select('id', 'warehouse_name', 'warehouse_code', 'region_id', 'area_id', 'status')
                    ->where('status', 1)
                    ->orderBy('warehouse_code', 'asc')
                    ->get();
            }

            $sortStatus = isset($filters['status']) ? (string)$filters['status'] : null;

            if ($sortStatus === '0') {
                // inactive first
                $query->orderByRaw("CASE WHEN status = 0 THEN 0 ELSE 1 END");
            } else {
                // active first (default)
                $query->orderByRaw("CASE WHEN status = 1 THEN 0 ELSE 1 END");
            }


            if (is_null($perPage)) {
                return $query->get();
            }

            return $query->orderByDesc('id')->paginate($perPage);
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch warehouses: " . $e->getMessage());
        }
    }

    // public function getAll($perPage = 50, $filters = [], $dropdown = false)
    // {
    //     try {
    //         $user = auth()->user();
    //         $query = Warehouse::select(
    //             'uuid',
    //             'id',
    //             'warehouse_code',
    //             'warehouse_type',
    //             'warehouse_name',
    //             'owner_name',
    //             'owner_number',
    //             'owner_email',
    //             'agreed_stock_capital',
    //             'location',
    //             'city',
    //             'warehouse_manager',
    //             'warehouse_manager_contact',
    //             'vat_no',
    //             'company',
    //             'warehouse_email',
    //             'region_id',
    //             'area_id',
    //             'latitude',
    //             'longitude',
    //             'agent_customer',
    //             'town_village',
    //             'street',
    //             'tin_no',
    //             'landmark',
    //             'is_efris',
    //             'p12_file',
    //             // 'password',
    //             'device_no',
    //             'is_branch',
    //             'branch_id',
    //             'status',
    //             'created_user',
    //             'updated_user',
    //             'created_date'
    //         )->with([
    //             'region:id,region_name,region_code',
    //             'area:id,area_code,area_name,region_id',
    //             'createdBy:id,name,username',
    //             'updatedBy:id,name,username',
    //             'getCompanyCustomer:id,osa_code,business_name',
    //             'getCompany:id,company_code,company_name',
    //             'locationRelation:id,code,name'
    //         ]);
    //         $query = DataAccessHelper::filterWarehouses($query, $user);
    //         foreach ($filters as $field => $value) {
    //             if (!empty($value)) {
    //                 switch ($field) {
    //                     case 'created_date_from':
    //                         $query->whereDate('created_date', '>=', $value);
    //                         break;

    //                     case 'created_date_to':
    //                         $query->whereDate('created_date', '<=', $value);
    //                         break;
    //                     case 'warehouse_id':
    //                         $warehouseIds = is_string($value) && str_contains($value, ',')
    //                             ? array_map('intval', explode(',', $value))
    //                             : (array) $value;

    //                         $query->whereIn('id', array_filter($warehouseIds));
    //                         break;
    //                     case 'id':
    //                     case 'warehouse_code':
    //                     case 'warehouse_name':
    //                     case 'owner_name':
    //                     case 'owner_number':
    //                     case 'owner_email':
    //                     case 'warehouse_manager':
    //                     case 'warehouse_manager_contact':
    //                     case 'tin_no':
    //                     case 'registation_no':
    //                     case 'business_type':
    //                     case 'warehouse_type':
    //                     case 'city':
    //                     case 'location':
    //                     case 'address':
    //                     case 'stock_capital':
    //                     case 'deposite_amount':
    //                     case 'latitude':
    //                     case 'longitude':
    //                     case 'device_no':
    //                     case 'p12_file':
    //                     case 'password':
    //                     case 'branch_id':
    //                     case 'is_branch':
    //                     case 'invoice_sync':
    //                     case 'is_efris':
    //                     case 'district':
    //                     case 'town_village':
    //                     case 'street':
    //                     case 'landmark':
    //                         $query->whereRaw("LOWER($field) LIKE ?", ['%' . strtolower($value) . '%']);
    //                         break;
    //                     case 'region_id':
    //                     case 'status':
    //                         $query->where($field, $value);
    //                         break;
    //                     case 'area_id':
    //                         $areaIds = is_string($value) && str_contains($value, ',')
    //                             ? array_map('intval', explode(',', $value))
    //                             : (array) $value;
    //                         $query->whereIn('area_id', array_filter($areaIds));
    //                         break;

    //                     default:
    //                         $query->where($field, $value);
    //                         break;
    //                 }
    //             }
    //         }
    //         if ($dropdown) {
    //             return $query
    //                 ->select('id', 'warehouse_name', 'warehouse_code', 'region_id', 'area_id', 'status')
    //                 ->where('status', 1)
    //                 ->orderBy('warehouse_name', 'asc')
    //                 ->get();
    //         }
    //         if (isset($filters['status']) && (int)$filters['status'] === 0) {

    //             // inactive first
    //             $query->orderBy('status', 'asc')
    //                 ->orderBy('id', 'desc');
    //         } else {

    //             // active first
    //             $query->orderBy('status', 'desc')
    //                 ->orderBy('id', 'desc');
    //         }
    //         if (is_null($perPage)) {
    //             return $query->get();
    //         }
    //         return $query->paginate($perPage);
    //     } catch (\Exception $e) {
    //         throw new \Exception("Failed to fetch warehouses: " . $e->getMessage());
    //     }
    // }


    public function getAllFilter($perPage = 50, $filters = [], $dropdown = false)
    {
        try {
            $user = auth()->user();

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
                'tin_no',
                'landmark',
                'is_efris',
                'is_branch',
                'device_no',
                'p12_file',
                'branch_id',
                'status',
                'created_user',
                'updated_user',
                'created_date'
            )->with([
                'region:id,region_name',
                'area:id,area_code,area_name,region_id',
                'createdBy:id,name,username',
                'updatedBy:id,name,username',
                'getCompanyCustomer:id,osa_code,business_name,contact_number',
                'getCompany:id,company_code,company_name',
                'locationRelation:id,code,name'
            ]);

            // Access filter
            $query = DataAccessHelper::filterWarehouses($query, $user);

            /**
             * APPLY FILTERS (UNCHANGED)
             */
            foreach ($filters as $field => $value) {
                if (empty($value)) {
                    continue;
                }

                switch ($field) {
                    case 'created_date_from':
                        $query->whereDate('created_date', '>=', $value);
                        break;

                    case 'created_date_to':
                        $query->whereDate('created_date', '<=', $value);
                        break;

                    case 'region_id':
                    case 'status':
                        $query->where($field, $value);
                        break;

                    case 'area_id':
                        $areaIds = is_string($value) && str_contains($value, ',')
                            ? array_map('intval', explode(',', $value))
                            : (array) $value;
                        $query->whereIn('area_id', array_filter($areaIds));
                        break;

                    default:
                        $query->whereRaw(
                            "LOWER($field) LIKE ?",
                            ['%' . strtolower($value) . '%']
                        );
                        break;
                }
            }

            /**
             * ✅ STATUS-WISE SORTING (CORE FIX)
             * status=1 → active first
             * status=0 → inactive first
             */
            $sortStatus = isset($filters['status']) ? (string)$filters['status'] : null;

            if ($sortStatus === '0') {
                // inactive first
                $query->orderByRaw("CASE WHEN status = 0 THEN 0 ELSE 1 END");
            } else {
                // active first (default)
                $query->orderByRaw("CASE WHEN status = 1 THEN 0 ELSE 1 END");
            }


            /**
             * DROPDOWN MODE
             */
            if ($dropdown) {
                return $query
                    ->select('id', 'warehouse_name', 'warehouse_code', 'region_id', 'area_id', 'status')
                    ->where('status', 1)
                    ->orderBy('warehouse_name', 'asc')
                    ->get();
            }

            /**
             * SECONDARY SORT (KEEP THIS)
             */
            $query->orderBy('id', 'desc');

            if (is_null($perPage)) {
                return $query->get();
            }

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch warehouses: " . $e->getMessage());
        }
    }
    public function getAllActive(?string $search = null): Collection
    {
        try {
            $user = auth()->user();

            $query = Warehouse::query()
                ->active()
                ->with([
                    'region:id,region_name',
                    'area:id,area_code,area_name,region_id',
                    'createdBy:id,name,username',
                    'updatedBy:id,name,username',
                ]);
            $query = DataAccessHelper::filterWarehouses($query, $user);
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('warehouse_name', 'ILIKE', "%{$search}%")
                        ->orWhere('owner_name', 'ILIKE', "%{$search}%")
                        ->orWhere('warehouse_code', 'ILIKE', "%{$search}%")
                        ->orWhere('owner_email', 'ILIKE', "%{$search}%")
                        ->orWhere('owner_number', 'ILIKE', "%{$search}%")
                        ->orWhere('location', 'ILIKE', "%{$search}%")
                        ->orWhere('city', 'ILIKE', "%{$search}%");
                });
            }

            return $query
                ->orderBy('warehouse_name', 'asc')
                ->get();
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch active warehouses: ' . $e->getMessage());
        }
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
        $user = Auth::user()->id;
        if (empty($data['warehouse_code'])) {
            $lastWarehouse = Warehouse::latest('id')->first();
            $nextId = $lastWarehouse ? $lastWarehouse->id + 1 : 1;
            $data['warehouse_code'] = 'WHC' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
        }
        $data['created_user'] = $user;
        $data['updated_user'] = $user;
        return Warehouse::create($data);
    }

    public function find(string $uuid)
    {
        return Warehouse::with([
            'region:id,region_name,region_code',
            'area:id,area_code,area_name,region_id',
            'createdBy:id,name,username',
            'updatedBy:id,name,username',
            'getCompanyCustomer:id,osa_code,business_name,contact_number',
            'getCompany:id,company_code,company_name,email',
            'locationRelation:id,code,name'
        ])
            ->where('uuid', $uuid)
            ->whereNull('deleted_date')
            ->firstOrFail();
    }

    // public function find(string $uuid)
    // {
    //     return Warehouse::select(
    //         'tbl_warehouse.uuid',
    //         'tbl_warehouse.id',
    //         'tbl_warehouse.warehouse_code',
    //         'tbl_warehouse.warehouse_type',
    //         'tbl_warehouse.warehouse_name',
    //         'tbl_warehouse.owner_name',
    //         'tbl_warehouse.owner_number',
    //         'tbl_warehouse.owner_email',
    //         'tbl_warehouse.agreed_stock_capital',
    //         'locations.name as location',
    //         'locations.id as location_id',
    //         'tbl_warehouse.city',
    //         'tbl_warehouse.warehouse_manager',
    //         'tbl_warehouse.warehouse_manager_contact',
    //         'tbl_warehouse.tin_no',
    //         'tbl_warehouse.company',
    //         'tbl_warehouse.warehouse_email',
    //         'tbl_warehouse.region_id',
    //         'tbl_warehouse.area_id',
    //         'tbl_warehouse.latitude',
    //         'tbl_warehouse.longitude',
    //         'tbl_warehouse.agent_customer',
    //         'tbl_warehouse.town_village',
    //         'tbl_warehouse.street',
    //         'tbl_warehouse.landmark',
    //         'tbl_warehouse.is_efris',
    //         'tbl_warehouse.device_no',
    //         'tbl_warehouse.p12_file',
    //         // 'tbl_warehouse.password',
    //         'tbl_warehouse.is_branch',
    //         'tbl_warehouse.branch_id',
    //         'tbl_warehouse.status'
    //     )
    //         ->with([
    //             'region:id,region_name,region_code',
    //             'area:id,area_code,area_name',
    //             'createdBy:id,name,username',
    //             'updatedBy:id,name,username',
    //             'getCompanyCustomer:id,osa_code,business_name',
    //             'getCompany:id,company_code,company_name,email',
    //         ])
    //         ->leftJoin('locations', 'locations.id', '=', DB::raw('CAST(tbl_warehouse.location AS BIGINT)'))
    //         ->where('tbl_warehouse.uuid', $uuid)
    //         ->whereNull('tbl_warehouse.deleted_date')
    //         ->firstOrFail();
    // }
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
    // public function update(int $id, array $data): Warehouse
    // {
    //     $warehouse = Warehouse::findOrFail($id);
    //     $warehouse->update($data);

    //     return Warehouse::select(
    //         'uuid',
    //         'id',
    //         'warehouse_code',
    //         'warehouse_type',
    //         'warehouse_name',
    //         'owner_name',
    //         'owner_number',
    //         'owner_email',
    //         'agreed_stock_capital',
    //         'location',
    //         'city',
    //         'warehouse_manager',
    //         'warehouse_manager_contact',
    //         'vat_no',
    //         'company',
    //         'warehouse_email',
    //         'region_id',
    //         'area_id',
    //         'latitude',
    //         'longitude',
    //         'agent_customer',
    //         'town_village',
    //         'street',
    //         'landmark',
    //         'is_efris',
    //         'password',
    //         'is_branch',
    //         'branch_id',
    //         'status',
    //         'created_user',
    //         'updated_user',
    //     )->with([
    //         'region:id,region_name',
    //         'area:id,area_code,area_name',
    //         'createdBy:id,name,username',
    //         'updatedBy:id,name,username',
    //         'getCompanyCustomer:id,osa_code,business_name',
    //         'getCompany:id,company_code,company_name'
    //     ])->findOrFail($id);
    // }

    public function update(string $uuid, array $data): Warehouse
    {
        $warehouse = Warehouse::where('uuid', $uuid)->firstOrFail();
        // dd($data);
        $warehouse->update($data);

        return $warehouse->fresh([
            'region:id,region_name,region_code',
            'area:id,area_code,area_name,region_id',
            'createdBy:id,name,username',
            'updatedBy:id,name,username',
            'getCompanyCustomer:id,osa_code,business_name,contact_number',
            'getCompany:id,company_code,company_name'
        ]);
    }
    // public function update(string $uuid, array $data): Warehouse
    // {
    //     $warehouse = Warehouse::where('uuid', $uuid)->firstOrFail();

    //     $warehouse->update($data);

    //     return Warehouse::select(
    //         'uuid',
    //         'id',
    //         'warehouse_code',
    //         'warehouse_type',
    //         'warehouse_name',
    //         'owner_name',
    //         'owner_number',
    //         'owner_email',
    //         'agreed_stock_capital',
    //         'location',
    //         'city',
    //         'warehouse_manager',
    //         'warehouse_manager_contact',
    //         'vat_no',
    //         'company',
    //         'warehouse_email',
    //         'region_id',
    //         'area_id',
    //         'latitude',
    //         'longitude',
    //         'agent_customer',
    //         'town_village',
    //         'street',
    //         'landmark',
    //         'is_efris',
    //         'p12_file',
    //         'device_no',
    //         'password',
    //         'is_branch',
    //         'branch_id',
    //         'status',
    //         'created_user',
    //         'updated_user',
    //     )
    //         ->with([
    //             'region:id,region_name',
    //             'area:id,area_code,area_name',
    //             'createdBy:id,name,username',
    //             'updatedBy:id,name,username',
    //             'getCompanyCustomer:id,osa_code,business_name',
    //             'getCompany:id,company_code,company_name'
    //         ])
    //         ->where('uuid', $uuid)
    //         ->firstOrFail();
    // }




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
                'createdBy:id,name,username',
                'updatedBy:id,name,username',
                'getCompanyCustomer:id,osa_code,business_name,contact_number',
                'getCompany:id,company_code,company_name',
                'locationRelation:id,code,name',

            ]);

            if (!empty($keyword)) {
                $query->where(function ($q) use ($keyword) {
                    $searchableFields = [
                        'warehouse_code',
                        'warehouse_name',
                        'owner_name',
                        'owner_number',
                        'owner_email',
                        'warehouse_manager',
                        'warehouse_manager_contact',
                        'tin_no',
                        'registation_no',
                        'business_type',
                        'warehouse_type',
                        'city',
                        'location',
                        'address',
                        'stock_capital',
                        'deposite_amount',
                        'latitude',
                        'longitude',
                        'device_no',
                        'p12_file',
                        'password',
                        'branch_id',
                        'is_branch',
                        'invoice_sync',
                        'is_efris',
                        'district',
                        'town_village',
                        'street',
                        'landmark',
                        'region_id'
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

                    $q->orWhereHas('region', function ($subQuery) use ($keyword) {
                        $subQuery->where('region_name', 'ILIKE', '%' . $keyword . '%');
                    });
                    $q->orWhereHas('area', function ($subQuery) use ($keyword) {
                        $subQuery->where(function ($a) use ($keyword) {
                            $a->where('area_name', 'ILIKE', '%' . $keyword . '%')
                                ->orWhere('area_code', 'ILIKE', '%' . $keyword . '%');
                        });
                    });
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
    public function warehouseCustomer(Request $request, $id)
    {
        $query = $request->query('query');
        $perPage = $request->get('per_page', 50);
        $warehouseCustomers = AgentCustomer::with([
            'outlet_channel',
            'subcategory',
            'route'
        ])
            ->where('warehouse', $id)
            ->when($query, function ($q) use ($query) {
                $q->where(function ($q2) use ($query) {
                    $q2->where('osa_code', 'LIKE', "%{$query}%")
                        ->orWhereHas('subcategory', function ($q3) use ($query) {
                            $q3->where('customer_sub_category_name', 'LIKE', "%{$query}%");
                        })
                        ->orWhereHas('outlet_channel', function ($q3) use ($query) {
                            $q3->where('outlet_channel', 'LIKE', "%{$query}%")
                                ->orWhere('outlet_channel_code', 'LIKE', "%{$query}%");
                        })
                        ->orWhereHas('route', function ($q3) use ($query) {
                            $q3->where('route_name', 'LIKE', "%{$query}%");
                        });
                });
            })
            ->paginate($perPage);

        return $warehouseCustomers; // <-- Return collection, not JSON
    }


    public function warehouseRoutes(Request $request, $id)
    {
        $query = $request->query('query');
        $perPage = $request->get('per_page', 50);


        $warehouseRoutes = Route::with(['vehicle:id,vehicle_code'])
            ->where('warehouse_id', $id)
            ->when($query, function ($q) use ($query) {
                $q->where(function ($q2) use ($query) {
                    $q2->where('route_code', 'LIKE', "%{$query}%")
                        ->orWhere('route_name', 'LIKE', "%{$query}%")
                        ->orWhere('route_type', 'LIKE', "%{$query}%")
                        ->orWhereHas('vehicle', function ($q3) use ($query) {
                            $q3->where('vehicle_code', 'LIKE', "%{$query}%");
                        });
                });
            })
            ->paginate($perPage);

        return $warehouseRoutes;
    }

    public function warehouseVehicles($id)
    {
        $user_id = Auth::id();
        $warehouseVehicles = Vehicle::where('warehouse_id', $id)
            ->with([
                'warehouse:id,warehouse_code,warehouse_name,owner_name',
                'createdBy:id,name,username',
                'updatedBy:id,name,username',
            ])
            ->get();
        return $warehouseVehicles;
    }
    public function warehouseSalesman(Request $request, $id)
    {
        $query = $request->query('query');
        $perPage = $request->get('per_page', 50);
        $warehouseSalesmen = Salesman::with(['route:id,route_name,route_code,route_type'])
            ->where('warehouse_id', $id)
            ->when($query, function ($q) use ($query) {
                $q->where(function ($q2) use ($query) {
                    $q2->where('osa_code', 'LIKE', "%{$query}%")
                        ->orWhere('name', 'LIKE', "%{$query}%")
                        ->orWhere('type', 'LIKE', "%{$query}%")
                        ->orWhere('designation', 'LIKE', "%{$query}%")
                        ->orWhereHas('route', function ($q3) use ($query) {
                            $q3->where('route_name', 'LIKE', "%{$query}%")
                                ->orWhere('route_code', 'LIKE', "%{$query}%")
                                ->orWhere('route_type', 'LIKE', "%{$query}%");
                        });
                });
            })
            ->paginate($perPage);

        return $warehouseSalesmen;
    }

    // public function getsales($warehouseId, $filters = [], $perPage = 50)
    // {
    //     $query = InvoiceHeader::with(['details', 'salesman', 'route', 'customer'])
    //         ->orderBy('id', 'desc')
    //         ->where('warehouse_id', $warehouseId);
    //     if (!empty($filters['salesman_code'])) {
    //         $salesmanCode = strtolower($filters['salesman_code']);
    //         $query->whereHas('salesman', function ($q) use ($salesmanCode) {
    //             $q->whereRaw('LOWER(osa_code) LIKE ?', ["%{$salesmanCode}%"]);
    //         });
    //     }
    //     if (!empty($filters['route_code'])) {
    //         $routeCode = strtolower($filters['route_code']);
    //         $query->whereHas('route', function ($q) use ($routeCode) {
    //             $q->whereRaw('LOWER(route_code) LIKE ?', ["%{$routeCode}%"]);
    //         });
    //     }
    //     return $query->paginate($perPage);
    // }

    public function getsales($warehouseId, $filters = [], $perPage = 50)
    {
        $fromDate = request()->get('from_date');
        $toDate   = request()->get('to_date');

        $query = InvoiceHeader::with(['details', 'salesman', 'route', 'customer'])
            ->orderBy('id', 'desc')
            ->where('warehouse_id', $warehouseId);

        if (!empty($filters['salesman_code'])) {
            $salesmanCode = strtolower($filters['salesman_code']);
            $query->whereHas('salesman', function ($q) use ($salesmanCode) {
                $q->whereRaw('LOWER(osa_code) LIKE ?', ["%{$salesmanCode}%"]);
            });
        }

        if (!empty($filters['route_code'])) {
            $routeCode = strtolower($filters['route_code']);
            $query->whereHas('route', function ($q) use ($routeCode) {
                $q->whereRaw('LOWER(route_code) LIKE ?', ["%{$routeCode}%"]);
            });
        }

        // ✅ DATE FILTER USING invoice_date
        if ($fromDate && $toDate) {
            $query->whereDate('invoice_date', '>=', $fromDate)
                ->whereDate('invoice_date', '<=', $toDate);
        } elseif ($fromDate) {
            $query->whereDate('invoice_date', '>=', $fromDate);
        } elseif ($toDate) {
            $query->whereDate('invoice_date', '<=', $toDate);
        } else {
            // 🔹 DEFAULT → CURRENT MONTH
            $query->whereBetween('invoice_date', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ]);
        }

        return $query->paginate($perPage);
    }


    public function getreturns($warehouseId, $filters = [], $perPage = 50)
    {
        $query = ReturnHeader::with(['details', 'salesman', 'route', 'customer'])
            ->orderBy('id', 'desc')
            ->where('warehouse_id', $warehouseId);

        // 🔹 Filter by Salesman Code
        if (!empty($filters['salesman_code'])) {
            $salesmanCode = strtolower($filters['salesman_code']);
            $query->whereHas('salesman', function ($q) use ($salesmanCode) {
                $q->whereRaw('LOWER(osa_code) LIKE ?', ["%{$salesmanCode}%"]);
            });
        }

        // 🔹 Filter by Route Code
        if (!empty($filters['route_code'])) {
            $routeCode = strtolower($filters['route_code']);
            $query->whereHas('route', function ($q) use ($routeCode) {
                $q->whereRaw('LOWER(route_code) LIKE ?', ["%{$routeCode}%"]);
            });
        }

        return $query->paginate($perPage);
    }
    // public function getreturns($warehouseId, $perPage = 50)
    //     {
    //          $query = ReturnHeader::with(['details', 'salesman', 'route', 'customer'])
    //                 ->orderBy('id', 'desc');

    //     if (!empty($filters['warehouse_id'])) {
    //         $query->where('warehouse_id', $filters['warehouse_id']);
    //     }

    //     if (!empty($filters['name'])) {
    //         $name = strtolower($filters['name']);
    //         $query->where(function ($q) use ($name) {
    //             $q->whereHas('customer', function ($sub) use ($name) {
    //                     $sub->whereRaw('LOWER(name) LIKE ?', ["%{$name}%"]);
    //                 })
    //               ->orWhereHas('salesman', function ($sub) use ($name) {
    //                     $sub->whereRaw('LOWER(name) LIKE ?', ["%{$name}%"]);
    //                 })
    //               ->orWhereHas('route', function ($sub) use ($name) {
    //                     $sub->whereRaw('LOWER(route_name) LIKE ?', ["%{$name}%"]);
    //                 });
    //         });
    //     }

    //         return $query->paginate($perPage);
    //     }


}
