<?php

namespace App\Services\V1\Settings\Web;

use App\Models\RouteType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;

class RouteTypeService
{

    // public function getAll($perPage = 50, $filters = [])
    // {
    //     try {
    //         $query = RouteType::with([
    //             'createdBy' => function ($q) {
    //                 $q->select('id','name', 'username');
    //             },
    //             'updatedBy' => function ($q) {
    //                 $q->select('id','name', 'username');
    //             }
    //         ])->orderByDesc('id');  

    //         foreach ($filters as $field => $value) {
    //             if (!empty($value)) {
    //                 if (in_array($field, ['route_type_name', 'route_type_code'])) {
    //                     $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
    //                 } else {
    //                     $query->where($field, $value);
    //                 }
    //             }
    //         }

    //         return $query->paginate($perPage);
    //     } catch (Exception $e) {
    //         throw new Exception("Failed to fetch route types: " . $e->getMessage());
    //     }
    // }
public function getAll(
    $perPage = 50,
    array $filters = [],
    bool $dropdown = false
) {
    try {
        $query = RouteType::query()
            ->orderByDesc('id');

        // 🔹 DROPDOWN MODE (unchanged)
        if ($dropdown) {
            return $query
                ->select('id', 'route_type_code', 'route_type_name', 'status')
                ->where('status', 1)
                ->orderBy('route_type_name')
                ->get();
        }

        // 🔹 NORMAL LIST MODE (unchanged)
        $query->with([
            'createdBy:id,name,username',
            'updatedBy:id,name,username',
        ]);

        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                if (in_array($field, ['route_type_name', 'route_type_code'])) {
                    $query->whereRaw(
                        "LOWER({$field}) LIKE ?",
                        ['%' . strtolower($value) . '%']
                    );
                } else {
                    $query->where($field, $value);
                }
            }
        }
        if (is_null($perPage)) {
            return $query->get();
        }

        return $query->paginate($perPage);

    } catch (Exception $e) {
        throw new Exception(
            "Failed to fetch route types: " . $e->getMessage()
        );
    }
}


    public function getById($id)
    {
        try {
            return RouteType::findOrFail($id);
        } catch (Exception $e) {
            throw new Exception("Route type not found: " . $e->getMessage());
        }
    }

    public function create(array $data, $userId)
    {
        DB::beginTransaction();
        try {
            $lastRouteType = RouteType::withTrashed()->latest('id')->first();
            $nextNumber = $lastRouteType ? $lastRouteType->id + 1 : 1;
            $autoCode = 'RTC' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $data['route_type_code'] = $autoCode;
            $data['created_user'] = $userId;
            $data['updated_user'] = $userId;

            $routeType = RouteType::create($data);

            DB::commit();
            return $routeType;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Failed to create route type: " . $e->getMessage());
        }
    }


    // private function generateRouteTypeCode()
    //     {
    //         $lastRouteType = RouteType::get();
    //         dd($lastRouteType);
    //         $nextId = $lastRouteType ? $lastRouteType->id + 1 : 1;
    //         return 'RTC' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
    //     }

    public function update($id, array $data, $userId)
    {
        DB::beginTransaction();
        try {
            $routeType = RouteType::findOrFail($id);
            $data['updated_user'] = $userId;
            unset($data['code']);

            $routeType->update($data);

            DB::commit();
            return $routeType;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to update route type: " . $e->getMessage());
        }
    }


    public function delete($id)
    {
        DB::beginTransaction();
        try {
            $routeType = RouteType::findOrFail($id);
            $deleted = $routeType->delete();
            if ($deleted) {
                $deletedDate = $routeType->deleted_date ?? now();
            }
            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to delete route type: " . $e->getMessage());
        }
    }
}
