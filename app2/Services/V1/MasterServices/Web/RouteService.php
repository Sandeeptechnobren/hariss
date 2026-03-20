<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\Route;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Log;

class RouteService
{
    protected function generateRouteCode(): string
    {
        $lastRoute = Route::orderByDesc('id')->first();
        $nextId = $lastRoute ? $lastRoute->id + 1 : 1;
        return 'RT' . str_pad($nextId, 2, '0', STR_PAD_LEFT);
    }

    public function create(array $data): Route
    {
        DB::beginTransaction();
        try {
            $data['created_user'] = Auth::id();
            $data['updated_user'] = Auth::id();

            if (!isset($data['route_code']) || empty($data['route_code'])) {
                $data['route_code'] = $this->generateRouteCode();
            }
            if (isset($data['route_type']) && is_array($data['route_type'])) {
                $data['route_type'] = json_encode($data['route_type']);
            }
            if (isset($data['route_type']) && is_array($data['route_type'])) {
                $data['route_type'] = json_encode($data['route_type']);
            }
            if (isset($data['route_type']) && is_array($data['route_type'])) {
                $data['route_type'] = json_encode($data['route_type']);
            }
            $route = Route::create($data);
            DB::commit();
            return $route->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Route create failed: ' . $e->getMessage(), ['data' => $data]);
            throw $e;
        }
    }



    public function update(Route $route, array $data): Route
    {
        DB::beginTransaction();
        try {
            $data['updated_user'] = Auth::id();
            if (isset($data['route_code'])) {
                unset($data['route_code']);
            }
            $route->fill($data);
            $route->save();

            DB::commit();

            return $route->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Route update failed: ' . $e->getMessage(), [
                'route_id' => $route->id ?? null,
                'data' => $data
            ]);
            throw $e;
        }
    }

    public function delete(Route $route): void
    {
        DB::beginTransaction();
        try {
            // dd($route->delete());
            $route->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Route delete failed: ' . $e->getMessage(), ['route_id' => $route->id ?? null]);
            throw $e;
        }
    }

    public function getAll($perPage = 10, $filters = [])
    {
        $query = Route::with([
            'vehicle' => function ($q) {
                $q->select('id', 'vehicle_code', 'number_plat');
            },
            'warehouse' => function ($q) {
                $q->select('id', 'warehouse_code', 'warehouse_name', 'owner_name');
            },
            'getrouteType' => function ($q) {
                $q->select('id', 'route_type_code', 'route_type_name');
            },
            'createdBy' => function ($q) {
                $q->select('id', 'firstname', 'lastname', 'username');
            },
            'updatedBy' => function ($q) {
                $q->select('id', 'firstname', 'lastname', 'username');
            },
            'getrouteType' => function ($q) {
                $q->select('id', 'route_type_code', 'route_type_name');
            }
        ]);
        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                if (in_array($field, ['route_name', 'route_code'])) {
                    $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                } else {
                    $query->where($field, $value);
                }
            }
        }
        // dd($query->paginate($perPage)); 
        return $query->paginate($perPage);
    }


    public function getById(int $id): Route
    {
        return Route::with([
            'vehicle' => function ($q) {
                $q->select('id', 'vehicle_code', 'number_plat');
            },
            'warehouse' => function ($q) {
                $q->select('id', 'warehouse_code', 'warehouse_name', 'owner_name');
            },
            'getrouteType' => function ($q) {
                $q->select('id', 'route_type_code', 'route_type_name');
            },
            'createdBy' => function ($q) {
                $q->select('id', 'firstname', 'lastname', 'username');
            },
            'updatedBy' => function ($q) {
                $q->select('id', 'firstname', 'lastname', 'username');
            },
            'getrouteType' => function ($q) {
                $q->select('id', 'route_type_code', 'route_type_name');
            }
        ])->findOrFail($id);
    }



    public function globalSearch($perPage = 10, $searchTerm = null)
    {
        try {
            $query = Route::with([
                'vehicle' => function ($q) {
                    $q->select('id', 'vehicle_code', 'number_plat');
                },
                'warehouse' => function ($q) {
                    $q->select('id', 'warehouse_code', 'warehouse_name', 'owner_name');
                },
                'getrouteType' => function ($q) {
                    $q->select('id', 'route_type_code', 'route_type_name');
                },
                'createdBy' => function ($q) {
                    $q->select('id', 'firstname', 'lastname', 'username');
                },
                'updatedBy' => function ($q) {
                    $q->select('id', 'firstname', 'lastname', 'username');
                },
                'getrouteType' => function ($q) {
                    $q->select('id', 'route_type_code', 'route_type_name');
                }
            ]);

            if (!empty($searchTerm)) {
                $searchTerm = strtolower($searchTerm);

                $query->where(function ($q) use ($searchTerm) {
                    $likeSearch = '%' . $searchTerm . '%';

                    $q->orWhereRaw("LOWER(route_name) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(route_code) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(description) LIKE ?", [$likeSearch])
                        ->orWhereRaw("CAST(warehouse_id AS TEXT) LIKE ?", [$likeSearch])
                        ->orWhereRaw("CAST(route_type AS TEXT) LIKE ?", [$likeSearch])
                        ->orWhereRaw("CAST(status AS TEXT) LIKE ?", [$likeSearch])
                        ->orWhereRaw("CAST(created_user AS TEXT) LIKE ?", [$likeSearch])
                        ->orWhereRaw("CAST(updated_user AS TEXT) LIKE ?", [$likeSearch]);
                });
            }

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            throw new \Exception("Failed to search routes: " . $e->getMessage());
        }
    }


    public function exportRoutes($startDate, $endDate)
    {
        $routes = Route::with(['warehouse']) // if there's a relation
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ]);
            })
            ->get();

        return $routes->map(function ($route) {
            return [
                'route_code'     => $route->route_code,
                'route_name'     => $route->route_name,
                'description'    => $route->description,
                'warehouse_name' => $route->warehouse->warehouse_name ?? null,
                'route_type'     => $route->getrouteType->route_type_name,
                'vehicle_name'   => $route->vehicle->vehicle_code ?? null,
                'status'         => $route->status
            ];
        });
    }

    public function bulkUpdateStatus(array $ids, $status): int
    {
        // Update the 'status' for multiple routes at once, return the number affected
        return Route::whereIn('id', $ids)->update(['status' => $status]);
    }
}
