<?php

namespace App\Services\V1\MasterServices\Mob;

use App\Models\Salesman;
use App\Models\VersionControll;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Hash;
use App\Models\SalesmanAttendance;
use Carbon\Carbon;
use App\Models\AgentCustomer;
use App\Models\VisitPlan;
use App\Models\CompanyCustomer;
use App\Models\RouteVisit;
use App\Models\Agent_Transaction\LoadHeader;
use App\Models\SalesmanWarehouseHistory;
use Illuminate\Support\Str;
use App\Models\PromotionHeader;
use App\Models\DiscountHeader;
use App\Http\Resources\V1\Master\Mob\PromotionHeaderResource;
use App\Http\Resources\V1\Master\Mob\DiscountHeaderResource;
use Illuminate\Support\Facades\Storage;

class SalesmanMobService
{
public function login($username, $password, $version, $device_token = null, $device_no = null)
    {
        $salesman = Salesman::where('osa_code', $username)->first();
        if (!$salesman) {
            return [
                'status' => false,
                'message' => 'Invalid username.',
            ];
        }
        $storedHash = $salesman->password;
        $isValid = false;
        if (Str::startsWith($storedHash, ['$2y$', '$2b$'])) {
            $isValid = Hash::check($password, $storedHash);
        } else {
            $isValid = md5($password) === $storedHash;
        }
        if (!$isValid) {
            return [
                'status' => false,
                'message' => 'Invalid password.',
            ];
        }
        if (md5($password) === $storedHash) {
            $salesman->password = Hash::make($password);
            $salesman->save();
        }
        if ($salesman->type == 2) {
        $load = LoadHeader::with('route:id,route_name')
            ->where('salesman_id', $salesman->id)
            ->whereDate('created_at', Carbon::today())
            ->first();
        if (!$load) {
            return [
                'status' => false,
                'message' => 'No load available for today. Login not allowed.',
            ];
        }
        $salesman->setRelation('load_route', [
            'id'   => $load->route_id,
            'name' => $load->route?->route_name,
        ]);
        }
        $latestVersion = VersionControll::latest('id')->first();
        if (!$latestVersion || $latestVersion->version !== $version) {
            return [
                'status' => false,
                'message' => 'App version is outdated. Please update to continue.',
                'latest_version' => $latestVersion ? $latestVersion->version : null,
            ];
        }
         if ($device_token || $device_no) {
         $salesman->token_no = $device_token;
         $salesman->device_no = $device_no ?? $salesman->device_no;
         $salesman->save();
         }
        $attendanceRow = SalesmanAttendance::select('uuid', 'attendance_date')
            ->where('salesman_id', $salesman->id)
            ->whereDate('time_in', Carbon::today())
            ->first();
        $attendance = [
            'uuid'     => $attendanceRow?->uuid,
            'date'     =>$attendanceRow?->attendance_date,
            'check_in' => (int) !is_null($attendanceRow),
        ];
        $salesman->attendance = $attendance;
        return [
            'status' => true,
            'message' => 'Login successful.',
            'data' => $salesman,
        ];
    }
public function store(array $data)
    {
        return SalesmanAttendance::create($data);
    }
public function list(array $filters)
    {
        $query = SalesmanAttendance::query();
        if (!empty($filters['salesman_id'])) {
            $query->where('salesman_id', $filters['salesman_id']);
        }
        if (!empty($filters['attendance_date'])) {
            $query->whereDate('attendance_date', $filters['attendance_date']);
        }
        return $query->orderByDesc('id')->paginate(20);
    }
public function updateByUuid(string $uuid, array $data)
{
    $attendance = SalesmanAttendance::where('uuid', $uuid)->firstOrFail();

    // $data['updated_user'] = auth()->id();

    $attendance->update($data);

    return $attendance;
}
public function salesmanrequest(array $data)
    {
        return SalesmanWarehouseHistory::create($data);
    }
// public function getTodayCustomerList($salesman_id)
// {
//     $today     = Carbon::now()->format('l');
//     $todayDate = Carbon::now()->toDateString();
//     $salesman = Salesman::find($salesman_id);
//     if (!$salesman) {
//         return collect();
//     }
//     $warehouseHistory = SalesmanWarehouseHistory::where('salesman_id', $salesman_id)
//         ->whereDate('requested_date', $todayDate)
//         ->latest('id')
//         ->first();
//     $warehouse = $warehouseHistory->warehouse_id ?? null;
//     $allCustomers = collect();
//     if ($salesman->type == 6 && $salesman->sub_type == 6) {
//         if ($warehouse) {
//             $allCustomers = CompanyCustomer::where('merchandiser_id', $salesman_id)
//                 ->where('status', 1)
//                 ->get()
//                 ->merge(
//                     AgentCustomer::whereRaw("? = ANY (string_to_array(warehouse::text, ','))", [$warehouse])->where('status', 1)->get()
//                 );
//                 }
//         else {
//             $allCustomers = CompanyCustomer::where('merchandiser_id', $salesman_id)->where('status', 1)->get();
//         }}
//     elseif ($salesman->type == 6 && $warehouse) {
//         $allCustomers = AgentCustomer::whereRaw("? = ANY (string_to_array(warehouse::text, ','))", [$warehouse])->where('status', 1)->get();
//     } elseif ($warehouse) {
//         $allCustomers = AgentCustomer::whereRaw("? = ANY (string_to_array(warehouse::text, ','))", [$warehouse])->where('status', 1)->get();
//     } else {
//         $customerIds = AgentCustomer::where('route_id', $salesman->route_id)
//             ->orWhere(function ($query) use ($salesman) {
//                 $warehouseIds = explode(',', $salesman->warehouse_id);
//                 foreach ($warehouseIds as $wid) {
//                     $query->orWhereRaw("? = ANY (string_to_array(warehouse::text, ','))", [$wid]);
//                 }})->pluck('id');
//         $allCustomers = AgentCustomer::whereIn('id', $customerIds)
//                                       ->where('status', 1)        
//                                       ->get();
//     }
//     $customerIds = $allCustomers->pluck('id');
//     $todayVisits = RouteVisit::where(function ($q) use ($customerIds, $warehouse) {
//             $q->whereIn('customer_id', $customerIds);
//             if ($warehouse) {
//                 $q->orWhereRaw("? = ANY(string_to_array(warehouse::text, ','))", [$warehouse]);
//             }
//         })
//         ->whereDate('to_date', '>=', now())
//         ->where('status', 1)
//         ->whereRaw("? = ANY(string_to_array(days, ','))", [now()->format('l')])
//         ->pluck('customer_id')
//         ->toArray();
//     return $allCustomers->map(function ($customer) use ($todayVisits) {
//         return [
//             'customer_details' => $customer,
//             'is_sequence'      => in_array($customer->id, $todayVisits) ? 1 : 0,
//         ];
//     });
// }
public function getTodayCustomerList($salesman_id)
    {
        $today     = now()->format('l');
        $todayDate = now()->toDateString();
        $salesman = Salesman::find($salesman_id);
        if (!$salesman) {
            return collect();
        }
        $warehouse = SalesmanWarehouseHistory::where('salesman_id', $salesman_id)
            ->whereDate('requested_date', $todayDate)
            ->latest('id')
            ->value('warehouse_id'); 
        $allCustomers = collect();
        if ($salesman->type == 6 && $salesman->sub_type == 6) {
            $companyCustomers = CompanyCustomer::where('merchandiser_id', $salesman_id)
                ->where('status', 1)
                ->get();
            if ($warehouse) {
                $agentCustomers = AgentCustomer::whereRaw(
                        "? = ANY(string_to_array(warehouse::text, ','))",
                        [$warehouse]
                    )
                    ->where('status', 1)
                    ->get();
                $allCustomers = $companyCustomers->merge($agentCustomers);
            } else {
                $allCustomers = $companyCustomers;
            }
        } elseif ($warehouse) {
            $allCustomers = AgentCustomer::whereRaw(
                    "? = ANY(string_to_array(warehouse::text, ','))",
                    [$warehouse]
                )
                ->where('status', 1)
                ->get();
        } else {
            $warehouseIds = explode(',', $salesman->warehouse_id);
            $customerIds = AgentCustomer::where('route_id', $salesman->route_id)->pluck('id');
            if ($customerIds->isEmpty() && !empty($warehouseIds)) {
                $customerIds = AgentCustomer::where(function ($q) use ($warehouseIds) {
                    foreach ($warehouseIds as $wid) {
                        $q->orWhereRaw(
                            "? = ANY(string_to_array(warehouse::text, ','))",
                            [$wid]
                        );
                    }
                })->pluck('id');
            }
            $allCustomers = AgentCustomer::whereIn('id', $customerIds)
                ->where('status', 1)
                ->get();}
        $customerIds = $allCustomers->pluck('id');
        $todayVisits = RouteVisit::where(function ($q) use ($customerIds, $warehouse) {
                $q->whereIn('customer_id', $customerIds);
                if ($warehouse) {
                    $q->orWhereRaw(
                        "? = ANY(string_to_array(warehouse::text, ','))",
                        [$warehouse]
                    );} })
            ->whereDate('to_date', '>=', now())
            ->where('status', 1)
            ->whereRaw("? = ANY(string_to_array(days, ','))", [now()->format('l')])
            ->pluck('customer_id')
            ->toArray();
        return $allCustomers->map(function ($customer) use ($todayVisits) {
            return [
                'customer_details' => $customer,
                'is_sequence'      => in_array($customer->id, $todayVisits) ? 1 : 0,
            ];
        });
    }
public function generateMasterFiles(int $warehouseId): array
    {
        $files = [];
    $warehouse = Warehouse::select('id','area_id','region_id','company')
        ->findOrFail($warehouseId);
    $locations = [
        'Warehouse' => $warehouseId,
        'Area'      => $warehouse->area_id,
        'Region'    => $warehouse->region_id,
        'Company'   => $warehouse->company,
    ];
    $promotionHeaders = PromotionHeader::with([
            'promotionDetails',
            'offerItems',
            'promotionalSlabs' => function ($q) {
                $q->whereNull('deleted_at');
            }
        ])
        ->where(function ($query) use ($locations) {
            foreach ($locations as $key => $id) {
                if (!$id) {
                    continue;
                }
                $query->orWhere(function ($q) use ($key, $id) {
                    $q->where('key_location', $key)
                      ->whereRaw("? = ANY(string_to_array(location, ',')::int[])", [$id]);
                });
            }
        })
        ->whereDate('from_date', '<=', now())
        ->whereDate('to_date', '>=', now())
        ->whereNull('deleted_at')
        ->where('status', 1)
        ->get();
        if ($promotionHeaders->isNotEmpty()) {
            $json = PromotionHeaderResource::collection($promotionHeaders)
                ->toJson(JSON_UNESCAPED_UNICODE);
            $fileName = 'salesman_files/promotion_master_' . now()->format('Ymd_His') . '.txt';
            Storage::disk('public')->put($fileName, $json);
            $files['promotion_file_url'] = '/storage/' . $fileName;
        }
        return $files;
    }
}