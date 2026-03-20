<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\Vehicle;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Exports\VehiclesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Helpers\DataAccessHelper;
use App\Helpers\LogHelper;

class VehicleService
{

    // public function getAll(int $perPage = 50, array $filters = [], bool $dropdown = false)
    // {
    //     try {
    //         $user = auth()->user();

    //         // ✅ Sorting decision (ONLY 0 / 1)
    //         $inactiveFirst = isset($filters['status']) && (string)$filters['status'] === '0';

    //         /**
    //          * DROPDOWN MODE
    //          */
    //         if ($dropdown) {
    //             $query = Vehicle::select([
    //                 'id',
    //                 'vehicle_code',
    //                 'number_plat',
    //                 'status'
    //             ])
    //                 ->where('status', 1) // dropdown shows active only
    //                 ->orderBy('number_plat', 'asc');

    //             return $query->get();
    //         }

    //         /**
    //          * NORMAL MODE
    //          */
    //         $query = Vehicle::with([
    //             'warehouse:id,warehouse_code,warehouse_name,owner_name',
    //             'createdBy:id,name,username',
    //             'updatedBy:id,name,username'
    //         ]);

    //        $query = DataAccessHelper::filterVehicles($query, $user);

    //         // ✅ STATUS FILTER
    //         if (isset($filters['status']) && $filters['status'] !== '') {
    //             $query->where('status', $filters['status']);
    //         }
    //         if (!empty($filters['warehouse_id'])) {
    //             $query->where('warehouse_id', $filters['warehouse_id']);
    //         }
    //         // ✅ STATUS SORTING
    //         $inactiveFirst = isset($filters['status']) && (string)$filters['status'] === '0';

    //         $query->orderByRaw(
    //             $inactiveFirst
    //                 ? "CASE WHEN status = 0 THEN 1 WHEN status = 1 THEN 2 END"
    //                 : "CASE WHEN status = 1 THEN 1 WHEN status = 0 THEN 2 END"
    //         );

    //         $query->orderBy('id', 'desc');

    //         return $query->paginate($perPage);
    //     } catch (\Exception $e) {
    //         throw new \Exception('Failed to fetch vehicles: ' . $e->getMessage());
    //     }
    // }
    // public function getAll(int $perPage = 50, array $filters = [], bool $dropdown = false, int $page = 1)
    // {
    //     $user = auth()->user();

    //     if ($dropdown) {
    //         return Vehicle::select(['id', 'vehicle_code', 'number_plat', 'status'])
    //             ->where('status', 1)
    //             ->orderBy('number_plat', 'asc')
    //             ->get();
    //     }

    //     $query = Vehicle::with([
    //         'warehouse:id,warehouse_code,warehouse_name,owner_name',
    //         'createdBy:id,name,username',
    //         'updatedBy:id,name,username'
    //     ]);

    //     $query = DataAccessHelper::filterVehicles($query, $user);

    //     if (!empty($filters['status']) || $filters['status'] === '0') {
    //         $query->where('status', $filters['status']);
    //     }

    //     if (!empty($filters['warehouse_id'])) {

    //         $warehouseIds = array_values(array_filter(
    //             array_map('trim', explode(',', $filters['warehouse_id']))
    //         ));
    //         if ($warehouseIds) {
    //             $query->whereIn('warehouse_id', $warehouseIds);
    //         }
    //     }

    //     if (!empty($filters['vehicle_code'])) {
    //         $query->whereRaw('LOWER(vehicle_code) LIKE ?', ['%' . strtolower($filters['vehicle_code']) . '%']);
    //     }

    //     if (!empty($filters['number_plat'])) {
    //         $query->whereRaw('LOWER(number_plat) LIKE ?', ['%' . strtolower($filters['number_plat']) . '%']);
    //     }

    //     $inactiveFirst = isset($filters['status']) && (string) $filters['status'] === '0';

    //     $query->orderByRaw(
    //         $inactiveFirst
    //             ? "CASE WHEN status = 0 THEN 1 WHEN status = 1 THEN 2 END"
    //             : "CASE WHEN status = 1 THEN 1 WHEN status = 0 THEN 2 END"
    //     );

    //     $query->orderBy('id', 'desc');

    //     return $query->paginate($perPage, ['*'], 'page', $page);
    // }
    public function getAll(
        int $perPage = 50,
        array $filters = [],
        bool $dropdown = false,
        int $page = 1
    ) {
        $user = auth()->user();
        $query = Vehicle::query();

        if ($dropdown) {
            $query->select(['id', 'vehicle_code', 'number_plat', 'status', 'warehouse_id']);
        } else {
            $query->with([
                'warehouse:id,warehouse_code,warehouse_name,owner_name',
                'createdBy:id,name,username',
                'updatedBy:id,name,username'
            ]);
        }
        $query = DataAccessHelper::filterVehicles($query, $user);
        if ($dropdown) {

            // dropdown behaviour same as before
            if (!empty($filters['status']) || $filters['status'] === '0') {
                $query->where('status', $filters['status']);
            } else {
                $query->where('status', 1);
            }
        }
        if (!empty($filters['warehouse_id'])) {
            $warehouseIds = array_values(array_filter(
                array_map('trim', explode(',', $filters['warehouse_id']))
            ));

            if ($warehouseIds) {
                $query->whereIn('warehouse_id', $warehouseIds);
            }
        }

        if (!$dropdown) {

            if (!empty($filters['vehicle_code'])) {
                $query->whereRaw(
                    'LOWER(vehicle_code) LIKE ?',
                    ['%' . strtolower($filters['vehicle_code']) . '%']
                );
            }

            if (!empty($filters['number_plat'])) {
                $query->whereRaw(
                    'LOWER(number_plat) LIKE ?',
                    ['%' . strtolower($filters['number_plat']) . '%']
                );
            }
        }
        if (!$dropdown) {

            $inactiveFirst = isset($filters['status']) && (string)$filters['status'] === '0';

            $query->orderByRaw(
                $inactiveFirst
                    ? "CASE WHEN status = 0 THEN 1 WHEN status = 1 THEN 2 END"
                    : "CASE WHEN status = 1 THEN 1 WHEN status = 0 THEN 2 END"
            );

            $query->orderBy('id', 'desc');

            return $query->paginate($perPage, ['*'], 'page', $page);
        }

        return $query->orderBy('number_plat', 'asc')->get();
    }


    public function findByUuid(string $uuid): ?Vehicle
    {
        try {
            return Vehicle::with(['warehouse:id,warehouse_name'])
                ->where('uuid', $uuid)
                ->first(); // fetch by UUID
        } catch (Exception $e) {
            throw new Exception("Failed to fetch vehicle: " . $e->getMessage());
        }
    }
    protected function generateVehicleCode(): string
    {
        $lastVehicle = Vehicle::withTrashed()
            ->select('vehicle_code')
            ->orderByDesc('vehicle_code')
            ->first();
        if ($lastVehicle) {
            $lastNumber = (int) Str::after($lastVehicle->vehicle_code, 'VC');
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        return 'VC' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    public function create(array $data): Vehicle
    {
        DB::beginTransaction();

        try {
            $userId = Auth::id();
            if (!$userId) {
                throw new Exception("Unauthenticated: No user logged in.");
            }

            if (empty($data['vehicle_code'])) {
                $data['vehicle_code'] = $this->generateVehicleCode();
            }

            $data['created_user'] = $userId;
            $data['updated_user'] = $userId;

            $vehicle = Vehicle::create($data);

            DB::commit();
            LogHelper::store(
                '7',
                '18',
                'add',
                null,
                $vehicle->toArray(),
                $userId
            );

            return $vehicle->fresh()->load('warehouse:id,warehouse_name');
        } catch (Exception $e) {

            DB::rollBack();
            Log::error('Vehicle create failed: ' . $e->getMessage(), ['data' => $data]);

            throw new Exception("Failed to create vehicle: " . $e->getMessage());
        }
    }

    public function update(Vehicle $vehicle, array $data): Vehicle
    {
        DB::beginTransaction();
        try {
            $userId = Auth::id();
            if (!$userId) {
                throw new Exception("Unauthenticated: No user logged in.");
            }
            $data['updated_user'] = $userId;
            if (isset($data['vehicle_code'])) {
                unset($data['vehicle_code']);
            }
            $vehicle->fill($data);
            $vehicle->save();
            DB::commit();

            return $vehicle->fresh()->load('warehouse:id,warehouse_name');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Vehicle update failed: ' . $e->getMessage(), [
                'vehicle_id' => $vehicle->id ?? null,
                'data' => $data
            ]);
            throw new Exception("Failed to update vehicle: " . $e->getMessage());
        }
    }

    public function delete(Vehicle $vehicle): bool
    {
        try {
            return $vehicle->delete();
        } catch (Exception $e) {
            throw new Exception("Failed to delete vehicle: " . $e->getMessage());
        }
    }

    public function globalSearch($perPage = 10, $searchTerm = null)
    {
        try {
            $query = Vehicle::with([
                'warehouse:id,warehouse_code,warehouse_name,owner_name',
                'createdBy:id,name,username',
                'updatedBy:id,name,username',
            ]);

            if (!empty($searchTerm)) {
                $query->where(function ($q) use ($searchTerm) {
                    $likeSearch = '%' . strtolower($searchTerm) . '%';

                    $q->orWhereRaw("LOWER(vehicle_code) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(number_plat) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(vehicle_chesis_no) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(vehicle_type) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(vehicle_brand) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(description) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(capacity) LIKE ?", [$likeSearch]);
                });
            }

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch vehicles: " . $e->getMessage());
        }
    }
    // public function export($filters, $format, $search = null)
    //     {
    //         $filename = 'vehicles_' . now()->format('Ymd_His');
    //         $filePath = "exports/$filename";
    //         $query = Vehicle::with(['warehouse', 'createdBy', 'updatedBy']);
    //         if (!empty($search)) {
    //             $likeSearch = '%' . strtolower($search) . '%';
    //             $query->where(function ($q) use ($likeSearch) {
    //                 $q->orWhereRaw('LOWER(vehicle_code) LIKE ?', [$likeSearch])
    //                 ->orWhereRaw('LOWER(number_plat) LIKE ?', [$likeSearch])
    //                 ->orWhereRaw('LOWER(vehicle_chesis_no) LIKE ?', [$likeSearch])
    //                 ->orWhereRaw('LOWER(vehicle_type) LIKE ?', [$likeSearch])
    //                 ->orWhereRaw('LOWER(vehicle_brand) LIKE ?', [$likeSearch])
    //                 ->orWhereRaw('LOWER(description) LIKE ?', [$likeSearch])
    //                 ->orWhereRaw('LOWER(capacity) LIKE ?', [$likeSearch]);
    //             });
    //         }
    //         foreach ($filters as $field => $value) {
    //             if ($value !== null && $value !== '') {
    //                 if (in_array($field, ['vehicle_name', 'vehicle_code'])) {
    //                     $query->whereRaw(
    //                         "LOWER({$field}) LIKE ?",
    //                         ['%' . strtolower($value) . '%']
    //                     );
    //                 } elseif ($field === 'status') {
    //                     $query->where('status', $value);
    //                 } else {
    //                     $query->where($field, $value);
    //                 }
    //             }
    //         }
    //         if ($format === 'pdf') {
    //             $data = $query->get();
    //             $pdf = Pdf::loadView('pdf.vehicles', ['data' => $data]);
    //             $filePath .= '.pdf';
    //             Storage::disk('public')->put($filePath, $pdf->output());
    //         } elseif ($format === 'xlsx') {
    //             $data = $query->get();
    //             $filePath .= '.xlsx';
    //             Excel::store(
    //                 new VehiclesExport($query),
    //                 $filePath,
    //                 'public',
    //                 \Maatwebsite\Excel\Excel::XLSX
    //             );
    //         } else {
    //         $data = $query->get();
    //                 $filePath .= '.csv';
    //                 Excel::store(
    //                     new VehiclesExport($filters, $search), 
    //                     $filePath,
    //                     'public',
    //                     \Maatwebsite\Excel\Excel::CSV
    //                 );
    //             }
    //         return rtrim(config('app.url'), '/') . '/storage/app/public/' . $filePath;
    //     }
    // public function export(array $filters, string $format, ?string $search = null)
    // {
    //     $filename = 'vehicles_' . now()->format('Ymd_His');
    //     $filePath = "exports/{$filename}";

    //     $query = Vehicle::with(['warehouse', 'createdBy', 'updatedBy']);

    //     if ($search) {
    //         $like = '%' . strtolower($search) . '%';
    //         $query->where(function ($q) use ($like) {
    //             $q->whereRaw('LOWER(vehicle_code) LIKE ?', [$like])
    //               ->orWhereRaw('LOWER(number_plat) LIKE ?', [$like])
    //               ->orWhereRaw('LOWER(vehicle_chesis_no) LIKE ?', [$like])
    //               ->orWhereRaw('LOWER(vehicle_type) LIKE ?', [$like])
    //               ->orWhereRaw('LOWER(vehicle_brand) LIKE ?', [$like])
    //               ->orWhereRaw('LOWER(description) LIKE ?', [$like])
    //               ->orWhereRaw('LOWER(capacity) LIKE ?', [$like]);
    //         });
    //     }

    //     foreach ($filters as $field => $value) {
    //         if ($value === null || $value === '') {
    //             continue;
    //         }
    //         if (in_array($field, ['vehicle_name', 'vehicle_code'])) {
    //             $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
    //             } else {
    //             $query->where($field, $value);
    //         }
    //     }

    //     if ($format === 'pdf') {
    //         $data = $query->get();
    //         $pdf = Pdf::loadView('pdf.vehicles', compact('data'));
    //         $filePath .= '.pdf';
    //         Storage::disk('public')->put($filePath, $pdf->output());
    //     } elseif ($format === 'xlsx') {

    //             $filePath .= '.xlsx';
    //             Excel::store(
    //                 new VehiclesExport($query),
    //                 $filePath,
    //                 'public',
    //                 \Maatwebsite\Excel\Excel::XLSX
    //                 );
    //     } else {

    //         $filePath .= '.csv';

    //         $pass=Excel::store(
    //             new VehiclesExport($query),
    //             $filePath,
    //             'public',
    //             \Maatwebsite\Excel\Excel::CSV
    //             );

    //     }

    //     return rtrim(config('app.url'), '/') . '/storage/app/public/' . $filePath;
    // }
    public function export(array $filters, string $format, ?string $search = null)
    {
        $filename = 'vehicles_' . now()->format('Ymd_His');
        $filePath = "exports/{$filename}";

        if ($format === 'xslx') {
            $format = 'xlsx';
        }

        /** 🔹 Build filtered query ONCE */
        $query = $this->buildVehicleQuery($filters, $search);

        if ($format === 'pdf') {

            $data = $query->get();
            $pdf  = Pdf::loadView('pdf.vehicles', compact('data'));

            $filePath .= '.pdf';
            Storage::disk('public')->put($filePath, $pdf->output());
        } elseif ($format === 'xlsx') {

            $filePath .= '.xlsx';
            Excel::store(
                new VehiclesExport($query),
                $filePath,
                'public',
                \Maatwebsite\Excel\Excel::XLSX
            );
        } else {

            $filePath .= '.csv';
            Excel::store(
                new VehiclesExport($query),
                $filePath,
                'public',
                \Maatwebsite\Excel\Excel::CSV
            );
        }

        return rtrim(config('app.url'), '/') . '/storage/app/public/' . $filePath;
    }

    private function buildVehicleQuery(array $filters, ?string $search)
    {
        $query = Vehicle::with([
            'warehouse:id,warehouse_code,warehouse_name,owner_name',
            'createdBy:id,name,username',
            'updatedBy:id,name,username',
        ]);

        $query = DataAccessHelper::filterVehicles($query, Auth::user());

        if ($search) {
            $like = '%' . strtolower($search) . '%';

            $query->where(function ($q) use ($like) {
                $q->whereRaw('LOWER(vehicle_code) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(number_plat) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(vehicle_chesis_no) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(vehicle_type) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(vehicle_brand) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(capacity) LIKE ?', [$like]);
            });
        }

        foreach ($filters as $field => $value) {

            if ($value === null || $value === '') {
                continue;
            }

            if (in_array($field, ['vehicle_name', 'vehicle_code'])) {

                $query->whereRaw(
                    "LOWER({$field}) LIKE ?",
                    ['%' . strtolower($value) . '%']
                );
            } elseif ($field === 'warehouse_id') {

                $warehouseIds = is_array($value)
                    ? $value
                    : array_filter(explode(',', $value));

                $query->whereIn('warehouse_id', $warehouseIds);
            } else {

                $query->where($field, $value);
            }
        }

        return $query;
    }


    public function updateVehiclesStatus(array $vehicleIds, $status)
    {
        $updated = Vehicle::whereIn('id', $vehicleIds)->update(['status' => $status]);
        return $updated > 0;
    }
}
