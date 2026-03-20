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

    public function create(array $data)
    {
        return NewCustomer::create($data);
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

public function updateByUuid(string $uuid, array $validated)
    {
        $customer = AgentCustomer::where('uuid', $uuid)->first();
        if (!$customer) {
            throw new Exception("Customer not found");
        }
        $customer->update($validated);
        return $customer;
    }
}
