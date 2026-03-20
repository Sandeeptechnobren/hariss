<?php

namespace App\Helpers;

class DataAccessHelper
{
    public static function resolveHierarchy($user)
    {
        $result = [
            'company'   => $user->company ?? [],
            'region'    => $user->region ?? [],
            'area'      => $user->area ?? [],
            'warehouse' => $user->warehouse ?? [],
            'route'     => $user->route ?? [],
        ];
        if (!empty($result['route'])) {
            return $result;
        }
        if (!empty($result['warehouse'])) {
            $result['route'] = \App\Models\Route::whereIn('warehouse_id', $result['warehouse'])
                ->pluck('id')->toArray();
            return $result;
        }
        if (!empty($result['area'])) {
            $result['warehouse'] = \App\Models\Warehouse::whereIn('area_id', $result['area'])
                ->pluck('id')->toArray();
            $result['route'] = \App\Models\Route::whereIn('warehouse_id', $result['warehouse'])
                ->pluck('id')->toArray();
            return $result;
        }
        if (!empty($result['region'])) {
            $result['area'] = \App\Models\Area::whereIn('region_id', $result['region'])
                ->pluck('id')->toArray();
            $result['warehouse'] = \App\Models\Warehouse::whereIn('area_id', $result['area'])
                ->pluck('id')->toArray();
            $result['route'] = \App\Models\Route::whereIn('warehouse_id', $result['warehouse'])
                ->pluck('id')->toArray();
            return $result;
        }
        if (!empty($result['company'])) {
            $result['region'] = \App\Models\Region::whereIn('company_id', $result['company'])
                ->pluck('id')->toArray();
            $result['area'] = \App\Models\Area::whereIn('region_id', $result['region'])
                ->pluck('id')->toArray();
            $result['warehouse'] = \App\Models\Warehouse::whereIn('area_id', $result['area'])
                ->pluck('id')->toArray();
            $result['route'] = \App\Models\Route::whereIn('warehouse_id', $result['warehouse'])
                ->pluck('id')->toArray();

            return $result;
        }

        return $result;
    }

    public static function applyHierarchyFilter($query, $user, $mapping)
    {
        if ($user->role == 1) {
            return $query;
        }
        $final = self::resolveHierarchy($user);
        $levels = ['route', 'warehouse', 'area', 'region', 'company'];
        foreach ($levels as $level) {
            if (!empty($final[$level]) && isset($mapping[$level])) {
                return $query->whereIn($mapping[$level], $final[$level]);
            }
        }
        return $query;
    }
    public static function filterWarehouses($query, $user)
    {
        return self::applyHierarchyFilter($query, $user, [
            'company'   => 'company_id',
            'region'    => 'region_id',
            'area'      => 'area_id',
            'warehouse' => 'id',
        ]);
    }
    public static function filterRoutes($query, $user)
    {
        return self::applyHierarchyFilter($query, $user, [
            'company'   => 'company_id',
            'region'    => 'region_id',
            'area'      => 'area_id',
            'warehouse' => 'warehouse_id',
            'route'     => 'id',
        ]);
    }
        public static function filterAreas($query, $user)
    {
        return self::applyHierarchyFilter($query, $user, [
            'company'   => 'company_id',
            'region'    => 'region_id',
            'area'      => 'id',
        ]);
    }
    public static function filterVehicles($query, $user)
    {
        return self::applyHierarchyFilter($query, $user, [
            'company'   => 'company_id',
            'region'    => 'region_id',
            'area'      => 'area_id',
            'warehouse' => 'warehouse_id',
        ]);
    }
    public static function filterSalesmen($query, $user)
    {
        return self::applyHierarchyFilter($query, $user, [
            'company'   => 'company_id',
            'region'    => 'region_id',
            'area'      => 'area_id',
            'warehouse' => 'warehouse_id',
            // 'route'     => 'route_id',
        ]);
    }
    public static function filterCompanyCustomers($query, $user)
    {
        return self::applyHierarchyFilter($query, $user, [
            // 'company' => 'company_id',
            'region'  => 'region_id',
            'area'    => 'area_id',
        ]);
    }
    public static function filterAgentCustomers($query, $user)
    {
        return self::applyHierarchyFilter($query, $user, [
            'company'   => 'company',
            'region'    => 'region',
            'area'      => 'area',
            'warehouse' => 'warehouse',
            'route'     => 'route_id',
        ]);
    }
    public static function filterRouteVisit($query, $user)
    {
        return self::applyHierarchyFilter($query, $user, [
            'company'   => 'company',
            'region'    => 'region',
            'area'      => 'area',
            'warehouse' => 'warehouse',
            'route'     => 'route',
        ]);
    }
    public static function filterRegions($query, $user)
    {
        return self::applyHierarchyFilter($query, $user, [
            'company'   => 'company_id',
            'region'    => 'id',
        ]);
    }
    public static function filterAgentTransaction($query, $user)
    {
        return self::applyHierarchyFilter($query, $user, [
            'company'   => 'company_id',
            'region'    => 'region_id',
            'area'      => 'area_id',
            'warehouse' => 'warehouse_id',
            // 'route'     => 'route_id',
        ]);
    }

    public static function filterAssets($query, $user)
    {
        return self::applyHierarchyFilter($query, $user, [
            'company'   => 'company_id',
            'region'    => 'region_id',
            'area'      => 'area_id',
            'warehouse' => 'warehouse_id',
        ]);
    }
}
