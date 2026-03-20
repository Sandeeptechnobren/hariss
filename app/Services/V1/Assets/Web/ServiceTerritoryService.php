<?php

namespace App\Services\V1\Assets\Web;

use App\Models\ServiceTerritory;
use App\Models\Region;
use App\Models\Area;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ServiceTerritoryService
{
    public function generateCode(?string $inputCode = null): string
    {
        // If user provided code, just return it
        if (!empty($inputCode)) {
            return $inputCode;
        }

        // Else generate automatically
        do {
            $last = ServiceTerritory::withTrashed()->latest('id')->first();
            $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $code = 'ST' . str_pad($next, 3, '0', STR_PAD_LEFT);
        } while (ServiceTerritory::withTrashed()->where('osa_code', $code)->exists());

        return $code;
    }



    public function getAll(int $perPage = 50, array $filters = [])
    {
        try {
            $query = ServiceTerritory::query();

            if (!empty($filters['warehouse_id'])) {
                $query->where('warehouse_id', $filters['warehouse_id']);
            }

            if (!empty($filters['region_id'])) {
                $query->where('region_id', $filters['region_id']);
            }

            if (!empty($filters['area_id'])) {
                $query->where('area_id', $filters['area_id']);
            }

            if (!empty($filters['technician_id'])) {
                $query->where('technician_id', $filters['technician_id']);
            }

            if (!empty($filters['search'])) {
                $term = strtolower($filters['search']);
                $like = "%{$term}%";

                $query->where(function ($q) use ($like) {
                    $q->orWhereRaw("LOWER(warehouse_id) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(region_id) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(area_id) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(technician_id) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(osa_code) LIKE ?", [$like]);
                });
            }

            $query->orderBy('created_at', 'desc');

            return $query->paginate($perPage);
        } catch (Throwable $e) {

            Log::error("ServiceTerritory filter failed", [
                'error' => $e->getMessage(),
                'filters' => $filters,
            ]);

            throw new \Exception("Failed to fetch Service Territories: " . $e->getMessage());
        }
    }


    public function create(array $data): ServiceTerritory
    {
        DB::beginTransaction();

        try {
            /**
             * ✅ Data is already validated & normalized by Request
             * warehouse_id => [68, 2048]
             * region_id    => [10, 2]
             * area_id      => [1, 2, 3]
             */

            // ✅ Convert arrays / CSV to comma-separated values
            foreach (['warehouse_id', 'region_id', 'area_id'] as $field) {
                if (isset($data[$field])) {

                    // CSV string → array
                    if (is_string($data[$field])) {
                        $data[$field] = array_filter(
                            array_map('trim', explode(',', $data[$field]))
                        );
                    }

                    // Array → CSV
                    if (is_array($data[$field])) {
                        $data[$field] = implode(',', $data[$field]);
                    }
                }
            }

            // ✅ System fields
            $data['uuid']         = Str::uuid()->toString();
            $data['osa_code']     = $data['osa_code'] ?? $this->generateCode();
            $data['created_user'] = Auth::id();

            $record = ServiceTerritory::create($data);

            DB::commit();
            return $record;
        } catch (Throwable $e) {

            DB::rollBack();

            Log::error('ServiceTerritory create failed', [
                'error'   => $e->getMessage(),
                'payload' => $data,
                'user'    => Auth::id(),
            ]);

            throw new \Exception('Failed to create Service Territory', 0, $e);
        }
    }




    public function findByUuid(string $uuid): ?ServiceTerritory
    {
        return ServiceTerritory::where('uuid', $uuid)->first();
    }

    public function ViewData(string $uuid)
    {
        $record = ServiceTerritory::where('uuid', $uuid)->first();

        if (!$record) {
            return null;
        }

        // CSV → arrays
        $regionIds    = $record->region_id ? explode(',', $record->region_id) : [];
        $areaIds      = $record->area_id   ? explode(',', $record->area_id)   : [];
        $warehouseIds = $record->warehouse_id ? explode(',', $record->warehouse_id) : [];

        // Fetch Regions WITH names + codes
        $regions = Region::whereIn('id', $regionIds)
            ->select('id', 'region_code', 'region_name')
            ->orderBy('region_name')
            ->get();

        // Fetch Areas WITH names + codes
        $areas = Area::whereIn('id', $areaIds)
            ->select('id', 'area_code', 'area_name', 'region_id')
            ->orderBy('area_name')
            ->get();

        // Fetch Warehouses WITH names + codes
        $warehouses = Warehouse::whereIn('id', $warehouseIds)
            ->select('id', 'warehouse_code', 'warehouse_name', 'area_id')
            ->orderBy('warehouse_name')
            ->get();

        $final = [];

        foreach ($regions as $region) {

            // Filter areas belonging to this region
            $regionAreas = $areas->where('region_id', $region->id);

            $areaList = [];

            foreach ($regionAreas as $area) {

                // Filter warehouses belonging to this area
                $areaWarehouses = $warehouses->where('area_id', $area->id)->values();

                $areaList[] = [
                    'area_id'      => $area->id,
                    'area_code'    => $area->area_code,
                    'area_name'    => $area->area_name,

                    'warehouses'   => $areaWarehouses->map(function ($w) {
                        return [
                            'warehouse_id'   => $w->id,
                            'warehouse_code' => $w->warehouse_code,
                            'warehouse_name' => $w->warehouse_name,
                        ];
                    }),
                ];
            }

            $final[] = [
                'region_id'   => $region->id,
                'region_code' => $region->region_code,
                'region_name' => $region->region_name,
                'area'        => $areaList,
            ];
        }
// dd($final);
        return [
            'territory' => $record,
            'hierarchy' => $final
        ];
    }



    public function updateByUuid(string $uuid, array $data): ServiceTerritory
    {
        $record = ServiceTerritory::where('uuid', $uuid)->first();

        if (!$record) {
            throw new \Exception('Service Territory not found');
        }

        DB::beginTransaction();

        try {

            foreach (['warehouse_id', 'region_id', 'area_id'] as $field) {
                if (array_key_exists($field, $data)) {

                    // Array → CSV because DB stores comma-separated values
                    if (is_array($data[$field])) {
                        $data[$field] = implode(',', $data[$field]);
                    }
                }
            }

            $data['updated_user'] = Auth::id();

            $record->fill($data)->save();

            DB::commit();
            return $record;
        } catch (Throwable $e) {

            DB::rollBack();

            Log::error('ServiceTerritory update failed', [
                'uuid'    => $uuid,
                'error'   => $e->getMessage(),
                'payload' => $data,
                'user'    => Auth::id(),
            ]);

            throw new \Exception('Failed to update Service Territory', 0, $e);
        }
    }



    public function deleteByUuid(string $uuid): void
    {
        $record = $this->findByUuid($uuid);

        if (!$record) {
            throw new \Exception("Service Territory not found");
        }

        DB::beginTransaction();

        try {
            $record->deleted_user = Auth::id();
            $record->save();
            $record->delete();

            DB::commit();
        } catch (Throwable $e) {

            DB::rollBack();

            throw new \Exception("Failed to delete Service Territory", 0, $e);
        }
    }
}
