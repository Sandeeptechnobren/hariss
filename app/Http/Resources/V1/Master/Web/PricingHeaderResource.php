<?php

namespace App\Http\Resources\V1\Master\Web;

// use App\Http\Controllers\V1\Settings\Web\ItemCategory;
// use App\Http\Requests\V1\Settings\Web\CustomerCategory;

use App\Models\AgentCustomer;
use App\Models\Area;
use App\Models\Company;
use App\Models\ItemCategory;
use App\Models\CustomerCategory;
use App\Models\Item;
use App\Models\OutletChannel;
use App\Models\Region;
use App\Models\Route;
use App\Models\Warehouse;
use Illuminate\Http\Resources\Json\JsonResource;

class PricingHeaderResource extends JsonResource
{
    // public function toArray($request): array
    // {
    //     return [
    //         'id'          => $this->id,
    //         'uuid'        => $this->uuid,
    //         'name'        => $this->name,
    //         'code'        => $this->code,
    //         'description' => $this->description,
    //         'start_date'  => $this->start_date,
    //         'end_date'    => $this->end_date,
    //         'apply_on'    => $this->apply_on,

    //         'company' => $this->company_id ? Company::whereIn(
    //             'id',
    //             explode(',', $this->company_id)
    //         )->get(['id', 'company_name as name', 'company_code as code']) : [],

    //         'region' => $this->region_id ? Region::whereIn(
    //             'id',
    //             explode(',', $this->region_id)
    //         )->get(['id', 'region_name as name', 'region_code as code']) : [],

    //         'area' => $this->area_id ? Area::whereIn(
    //             'id',
    //             explode(',', $this->area_id)
    //         )->get(['id', 'area_name as name', 'area_code as code']) : [],

    //         'warehouse' => $this->warehouse_id ? Warehouse::whereIn(
    //             'id',
    //             explode(',', $this->warehouse_id)
    //         )->get(['id', 'warehouse_name', 'warehouse_code']) : [],

    //         'route' => $this->route_id ? Route::whereIn(
    //             'id',
    //             explode(',', $this->route_id)
    //         )->get(['id', 'route_name as name', 'route_code as code']) : [],

    //         'customer_category' => $this->customer_category_id ? CustomerCategory::whereIn(
    //             'id',
    //             explode(',', $this->customer_category_id)
    //         )->get(['id', 'customer_category_name as name', 'customer_category_code as code']) : [],

    //         'customer' => $this->customer_id ? AgentCustomer::whereIn(
    //             'id',
    //             explode(',', $this->customer_id)
    //         )->get(['id', 'name as name', 'osa_code as code']) : [],


    //         'outlet_channel' => $this->outlet_channel_id ? OutletChannel::whereIn(
    //             'id',
    //             explode(',', $this->outlet_channel_id)
    //         )->get(['id', 'outlet_channel as name', 'outlet_channel_code as code']) : [],

    //         'item_category' => $this->item_category_id ? ItemCategory::whereIn(
    //             'id',
    //             explode(',', $this->item_category_id)
    //         )->get(['id', 'category_name', 'category_code']) : [],

    //         'item' => $this->item_id ? Item::whereIn(
    //             'id',
    //             explode(',', $this->item_id)
    //         )->get(['id', 'erp_code', 'name']) : [],



    //         'status' => $this->status,
    //     ];
    // }

    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'uuid'        => $this->uuid,
            'name'        => $this->name,
            'code'        => $this->code,
            'description' => $this->description,
            'start_date'  => $this->start_date,
            'end_date'    => $this->end_date,
            'apply_on'    => $this->apply_on,

            'company' => $this->company_id ? Company::whereIn(
                'id',
                explode(',', $this->company_id)
            )->get(['id', 'company_name as name', 'company_code as code']) : [],

            'region' => $this->region_id ? Region::whereIn(
                'id',
                explode(',', $this->region_id)
            )->get(['id', 'region_name as name', 'region_code as code']) : [],

            'area' => $this->area_id ? Area::whereIn(
                'id',
                explode(',', $this->area_id)
            )->get(['id', 'area_name as name', 'area_code as code']) : [],

            'warehouse' => $this->warehouse_id ? Warehouse::whereIn(
                'id',
                explode(',', $this->warehouse_id)
            )->get(['id', 'warehouse_name', 'warehouse_code']) : [],

            'route' => $this->route_id ? Route::whereIn(
                'id',
                explode(',', $this->route_id)
            )->get(['id', 'route_name as name', 'route_code as code']) : [],

            'customer_category' => $this->customer_category_id ? CustomerCategory::whereIn(
                'id',
                explode(',', $this->customer_category_id)
            )->get(['id', 'customer_category_name as name', 'customer_category_code as code']) : [],

            'customer' => $this->customer_id ? AgentCustomer::whereIn(
                'id',
                explode(',', $this->customer_id)
            )->get(['id', 'name as name', 'osa_code as code']) : [],

            'outlet_channel' => $this->outlet_channel_id ? OutletChannel::whereIn(
                'id',
                explode(',', $this->outlet_channel_id)
            )->get(['id', 'outlet_channel as name', 'outlet_channel_code as code']) : [],

            'item_category' => $this->item_category_id ? ItemCategory::whereIn(
                'id',
                explode(',', $this->item_category_id)
            )->get(['id', 'category_name', 'category_code']) : [],

            'item' => $this->item_id ? Item::whereIn(
                'id',
                explode(',', $this->item_id)
            )->get(['id', 'erp_code', 'name']) : [],

            // ✅ Include Pricing Details
            
            'applicable_for'    => $this->applicable_for,
            'status' => $this->status,
            'details' => PricingDetailResource::collection($this->whenLoaded('details')),

        ];
    }
}
