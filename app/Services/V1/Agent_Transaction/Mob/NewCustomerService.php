<?php

namespace app\Services\V1\Agent_Transaction\Mob;

use App\Models\Agent_Transaction\NewCustomer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Throwable;
use App\Models\AgentCustomer;

class NewCustomerService
{
    public function getAll()
    {
        return NewCustomer::with([
            // 'agentCustomer:id,name',
            'customertype:id',
            'outlet_channel:id,outlet_channel',
            'category:id,customer_category_name',
            'subcategory:id,customer_sub_category_name',
            'route:id,route_name',
            'getWarehouse:id,warehouse_name'
        ])->get();
    }

   public function getById($uuid)
{
    return NewCustomer::with([
        'agentCustomer:id,name',
        'customertype:id,name',
        'outlet_channel:id,outlet_channel',
        'category:id,customer_category_name',
        'subcategory:id,customer_sub_category_name',
        'route:id,route_name',
        'getWarehouse:id,warehouse_name'
    ])->where('uuid', $uuid)->first();
}
public function createCustomer(array $data)
{
    DB::beginTransaction();
    try {
        // dd($data);
        $customer = NewCustomer::create($data);
        DB::commit();
        return $customer;
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
    public function update(NewCustomer $customer, array $data)
    {
        $customer->update($data);
        return $customer->fresh([
            'agentCustomer:id,name',
           'customertype:id,name',
            'outlet_channel:id,outlet_channel',
            'category:id,customer_category_name',
            'subcategory:id,customer_sub_category_name',
            'route:id,route_name',
            'getWarehouse:id,warehouse_name'
        ]);
    }

    // public function delete(NewCustomer $customer)
    // {
    //     return $customer->delete();
    // }

public function updateById(int $id, array $validated)
{
    try {
        $customer = AgentCustomer::findOrFail($id);
        $customer->update($validated);
        return $customer;
    } catch (\Exception $e) {
        throw new \Exception($e->getMessage());
    }
}
}
