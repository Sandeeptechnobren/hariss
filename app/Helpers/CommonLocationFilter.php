<?php

// namespace App\Helpers;

// use App\Models\Warehouse;

// class CommonLocationFilter
// {
//     public static function resolveWarehouseIds(array $filter): array
//     {
//         // Priority: route > warehouse > area > region > company

//         if (!empty($filter['route'])) {
//             return Warehouse::whereIn('id', self::ids($filter['route']))
//                 ->pluck('id')
//                 ->toArray();
//         }

//         if (!empty($filter['warehouse'])) {
//             return self::ids($filter['warehouse']);
//         }

//         if (!empty($filter['area'])) {
//             return Warehouse::whereIn('area_id', self::ids($filter['area']))
//                 ->pluck('id')
//                 ->toArray();
//         }

//         if (!empty($filter['region'])) {
//             return Warehouse::whereHas('area', function ($q) use ($filter) {
//                 $q->whereIn('region_id', self::ids($filter['region']));
//             })->pluck('id')->toArray();
//         }

//         if (!empty($filter['company'])) {
//             return Warehouse::whereHas('area.region', function ($q) use ($filter) {
//                 $q->whereIn('company_id', self::ids($filter['company']));
//             })->pluck('id')->toArray();
//         }

//         return [];
//     }

//     /**
//      * Normalize IDs:
//      * - array: [1,2]
//      * - string: "1,2"
//      * - string: "1"
//      */
//     private static function ids($value): array
//     {
//         if (empty($value)) {
//             return [];
//         }

//         // If already array → clean & return
//         if (is_array($value)) {
//             return array_values(
//                 array_filter(
//                     array_map('intval', $value)
//                 )
//             );
//         }

//         // If comma-separated string
//         return array_values(
//             array_filter(
//                 array_map('intval', explode(',', (string) $value))
//             )
//         );
//     }
// }


// namespace App\Helpers;

// use App\Models\Route;
// use App\Models\Warehouse;

// class CommonLocationFilter
// {
//     public static function resolveWarehouseIds(array $filter): array
//     {
//         // Priority: route > warehouse > area > region > company

//         if (!empty($filter['route_id'])) {
//             return Route::whereIn('id', self::ids($filter['route_id']))
//                 ->pluck('warehouse_id')   // route se warehouse nikalo
//                 ->unique()
//                 ->toArray();
//         }


//         if (!empty($filter['warehouse_id'])) {
//             return self::ids($filter['warehouse_id']);
//         }

//         if (!empty($filter['area_id'])) {
//             return Warehouse::whereIn('area_id', self::ids($filter['area_id']))
//                 ->pluck('id')
//                 ->toArray();
//         }

//         if (!empty($filter['region_id'])) {
//             return Warehouse::whereHas('area', function ($q) use ($filter) {
//                 $q->whereIn('region_id', self::ids($filter['region_id']));
//             })->pluck('id')->toArray();
//         }

//         if (!empty($filter['company_id'])) {
//             return Warehouse::whereHas('area.region', function ($q) use ($filter) {
//                 $q->whereIn('company_id', self::ids($filter['company_id']));
//             })->pluck('id')->toArray();
//         }

//         return [];
//     }

//     private static function ids($value): array
//     {
//         if (empty($value)) return [];

//         if (is_array($value)) {
//             return array_values(array_filter(array_map('intval', $value)));
//         }

//         return array_values(array_filter(array_map('intval', explode(',', (string)$value))));
//     }
// }

namespace App\Helpers;

use App\Models\Route;
use App\Models\Warehouse;

class CommonLocationFilter
{
    public static function normalizeIds($value): array
    {
        if (empty($value)) return [];

        if (is_array($value)) {
            return array_values(array_filter(array_map('intval', $value)));
        }

        return array_values(array_filter(array_map('intval', explode(',', $value))));
    }

    public static function resolveWarehouseIds(array $filter): array
    {
        if (!empty($filter['route_id'])) {
            return Route::whereIn('id', self::normalizeIds($filter['route_id']))
                ->pluck('warehouse_id')
                ->unique()
                ->toArray();
        }

        if (!empty($filter['warehouse_id'])) {
            return self::normalizeIds($filter['warehouse_id']);
        }

        if (!empty($filter['area_id'])) {
            return Warehouse::whereIn('area_id', self::normalizeIds($filter['area_id']))
                ->pluck('id')->toArray();
        }

        if (!empty($filter['region_id'])) {
            return Warehouse::whereHas(
                'area',
                fn($q) =>
                $q->whereIn('region_id', self::normalizeIds($filter['region_id']))
            )->pluck('id')->toArray();
        }

        if (!empty($filter['company_id'])) {
            return Warehouse::whereHas(
                'area.region',
                fn($q) =>
                $q->whereIn('company_id', self::normalizeIds($filter['company_id']))
            )->pluck('id')->toArray();
        }

        return [];
    }
}
