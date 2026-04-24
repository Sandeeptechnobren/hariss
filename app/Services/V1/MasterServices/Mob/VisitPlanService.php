<?php

namespace App\Services\V1\MasterServices\Mob;
use App\Models\VisitPlan;
use App\Models\SalesmanLocation;
// use App\Models\Warehouse;

class VisitPlanService
{
public function getAll()
    {
        return VisitPlan::orderByDesc('id')->get();
    }
public function getById($id)
    {
        return VisitPlan::find($id);
    }
public function create(array $data)
    {
        return VisitPlan::create($data);
    }
public function update($id, array $data)
    {
        $plan = VisitPlan::find($id);
        if (!$plan) {
            return null;
        }
        $plan->update($data);
        return $plan;
    }
public function delete($id)
    {
        $plan = VisitPlan::find($id);
        if (!$plan) {
            return false;
        }
        $plan->delete();
        return true;
    }
public function storeLocations(array $data)
{
    \DB::beginTransaction();

    try {
        $location = SalesmanLocation::create([
            'salesman_id'  => $data['salesman_id'],
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'route_id'     => $data['route_id'] ?? null,
            'location'    => $data['location'], // ✅ full JSON
        ]);
        \DB::commit();
        return $location;
    } catch (\Throwable $e) {
        \DB::rollBack();
        throw new \Exception($e->getMessage());
    }
}
}