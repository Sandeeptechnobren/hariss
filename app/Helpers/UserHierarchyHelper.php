<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\Warehouse;
use App\Models\Area;
use App\Models\Region;

class UserHierarchyHelper
{

    public static function getAsmByWarehouse($warehouseId)
    {
        // 1️⃣ direct warehouse match
        $user = User::whereJsonContains('warehouse', $warehouseId)
            ->where('role', 91)
            ->first();

        if ($user) {
            return $user->username;
        }

        // 2️⃣ check area
        $warehouse = Warehouse::find($warehouseId);

        if ($warehouse && $warehouse->area_id) {

            $user = User::whereJsonContains('area', $warehouse->area_id)
                ->where('role', 91)
                ->first();

            if ($user) {
                return $user->username;
            }
        }

        // 3️⃣ check region
        if ($warehouse && $warehouse->region_id) {

            $user = User::whereJsonContains('region', $warehouse->region_id)
                ->where('role', 91)
                ->first();

            if ($user) {
                return $user->username;
            }
        }

        return null;
    }



    public static function getRsmByWarehouse($warehouseId)
    {
        // 1️⃣ direct warehouse match
        $user = User::whereJsonContains('warehouse', $warehouseId)
            ->where('role', 92)
            ->first();

        if ($user) {
            return $user->username;
        }

        // 2️⃣ check area
        $warehouse = Warehouse::find($warehouseId);

        if ($warehouse && $warehouse->area_id) {

            $user = User::whereJsonContains('area', $warehouse->area_id)
                ->where('role', 92)
                ->first();

            if ($user) {
                return $user->username;
            }
        }

        // 3️⃣ check region
        if ($warehouse && $warehouse->region_id) {

            $user = User::whereJsonContains('region', $warehouse->region_id)
                ->where('role', 92)
                ->first();

            if ($user) {
                return $user->username;
            }
        }

        return null;
    }
}
