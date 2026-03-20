<?php

namespace App\Services\V1\Hariss_transaction\Mob;

use App\Models\Hariss_Transaction\Web\PoOrderHeader;
use App\Models\Hariss_Transaction\Web\PoOrderDetail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\CompanyCustomer;
use App\Models\Warehouse;
use App\Models\Region;
use Carbon\Carbon;

class HtOrderService
{
public function createOrder(array $data)
{
    try {
        DB::beginTransaction();
        $customer = CompanyCustomer::findOrFail($data['customer_id']);
        $warehouseId = null;
        $companyId   = null;
        if ($customer->customer_type == 2) {
            $warehouse = Warehouse::where(
                'company_customer_id',
                $customer->id
            )->first();
            if (!$warehouse) {
                throw new \Exception('Warehouse not found for this customer');
            }
            $warehouseId = $warehouse->id;
        }
        if ($customer->customer_type == 4) {

            $region = Region::find($customer->region_id);

            if (!$region) {
                throw new \Exception('Region not found for this customer');
            }

            $companyId = $region->company_id;
        }
        $header = PoOrderHeader::create([
            'sap_id'        => $data['sap_id'] ?? null,
            'sap_msg'       => $data['sap_msg'] ?? null,
            'customer_id'   => $customer->id,
            'warehouse_id'  => $warehouseId,
            'company_id'    => $companyId,
            'delivery_date' => $data['delivery_date'] ?? null,
            'comment'       => $data['comment'] ?? null,
            'order_code'    => $data['order_code'] ?? null,
            'status'        => $data['status'] ?? 1,
            'currency'      => $data['currency'] ?? null,
            'country_id'    => $data['country_id'] ?? null,
            'salesman_id'   => $data['salesman_id'] ?? null,
            'warehouse_id'  => $data['warehouse_id'] ?? null,
            'gross_total'   => $data['gross_total'] ?? 0,
            'discount'      => $data['discount'] ?? 0,
            'pre_vat'       => $data['pre_vat'] ?? 0,
            'vat'           => $data['vat'] ?? 0,
            'excise'        => $data['excise'] ?? 0,
            'net'           => $data['net'] ?? 0,
            'total'         => $data['total'] ?? 0,
            'order_flag'    => $data['order_flag'] ?? 1,
            'order_date'    => $data['order_date'] ?? now(),
        ]);
        foreach ($data['details'] as $detail) {
            PoOrderDetail::create([
                'header_id'  => $header->id,
                'item_id'    => $detail['item_id'],
                'uom_id'     => $detail['uom_id'],
                'quantity'   => $detail['quantity'],
                'item_price' => $detail['item_price'] ?? 0,
                'excise'     => $detail['excise'] ?? 0,
                'discount'   => $detail['discount'] ?? 0,
                'vat'        => $detail['vat'] ?? 0,
                'total'      => $detail['total'] ?? 0,
            ]);
        }

        DB::commit();

        return $header->load('details');

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('PO Order create failed', ['error' => $e->getMessage()]);
        throw $e;
    }
}

}