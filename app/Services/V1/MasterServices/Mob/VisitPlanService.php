<?php

namespace App\Services\V1\MasterServices\Mob;
use App\Models\VisitPlan;
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
}