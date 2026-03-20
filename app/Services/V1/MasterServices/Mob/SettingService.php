<?php

namespace App\Services\V1\MasterServices\Mob;
use Illuminate\Support\Facades\DB;
use App\Models\Salesman;
use App\Models\Warehouse;
use App\Models\PricingHeader;
use App\Models\Item;
use App\Models\Uom;
use App\Models\OutletChannel;
use App\Models\CustomerSubCategory;
use App\Models\ItemCategory;
use App\Models\CustomerCategory;
use App\Models\Country;
use App\Http\Resources\V1\Master\Mob\ItemResource;
use App\Http\Resources\V1\Master\Mob\PricingResource;
class SettingService
{
     public function saveAllData($username)
    {
        $directory = storage_path('app/public/stetic_files');

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        // Fixed file names (no timestamps)
        $itemFile = "{$directory}/items_{$username}.txt";
        $itemCategoryFile = "{$directory}/item_categories_{$username}.txt";
        $customerCategoryFile = "{$directory}/customer_category_{$username}.txt";
        $customerSubCategoryFile = "{$directory}/customer_sub_category_{$username}.txt";
        $outletChannelFile = "{$directory}/outlet_channel_{$username}.txt";
        $pricingHeadersFile = "{$directory}/pricing_headers_{$username}.txt";
        $UomFile ="{$directory}/uom_details_{$username}.txt";
        $countryFile ="{$directory}/country_{$username}.txt";
        
        $items = ItemResource::collection(Item::with('itemUoms')->get());
        file_put_contents($itemFile, json_encode($items));

        $itemCategories = ItemCategory::select('id','category_name','status','category_code')->get();
        file_put_contents($itemCategoryFile, json_encode($itemCategories));
        
        $customerCategory = CustomerCategory::select('id','outlet_channel_id','customer_category_code','customer_category_name','status')->get();
        file_put_contents($customerCategoryFile, json_encode($customerCategory));
        
        $customerSubCategory = CustomerSubCategory::select('id','customer_category_id','customer_sub_category_code','customer_sub_category_name','status')->get();
        file_put_contents($customerSubCategoryFile, json_encode($customerSubCategory));

        $outletChannel = OutletChannel::select('id', 'outlet_channel_code', 'outlet_channel', 'status')->get();
        file_put_contents($outletChannelFile, json_encode($outletChannel));

        $pricingHeaders = PricingResource::collection(PricingHeader::with('details')->get());
        file_put_contents($pricingHeadersFile, json_encode($pricingHeaders));

        $Uoms = Uom::select('id', 'name', 'osa_code', 'sap_name')->get();
        file_put_contents($UomFile, json_encode($Uoms)); 
        
        $country = Country::select('id', 'country_code', 'country_name','status')->get();
        file_put_contents($countryFile, json_encode($country));
        // Short relative path return karo
        return [
            'item_file' => 'storage/stetic_files/items_' . $username . '.txt',
            'customer_category_file' => 'storage/stetic_files/customer_category_' . $username . '.txt',
            'item_category_file' => 'storage/stetic_files/item_categories_' . $username . '.txt',
            'customer_subcategory_file' => 'storage/stetic_files/customer_sub_category_' . $username . '.txt',
            'outlet_channel_file' => 'storage/stetic_files/outlet_channel_' . $username . '.txt',
            'pricing_headers_file' => 'storage/stetic_files/pricing_headers_' . $username . '.txt',
            'uom_file' => 'storage/stetic_files/uom_details_' . $username . '.txt',
            'country_file' => 'storage/stetic_files/country_' . $username . '.txt',
            
        ];
    }
   	  public function getWarehousesBySalesman(int $salesmanId)
    {
        $salesman = Salesman::find($salesmanId);
        if (!$salesman || empty($salesman->warehouse_id)) {
            return collect();
        }
        $warehouseIds = $salesman->warehouse_id;
        if (is_string($warehouseIds)) {
            if (str_contains($warehouseIds, ',')) {
                $warehouseIds = explode(',', $warehouseIds);
            } else {
                $warehouseIds = [$warehouseIds];
            }
        } elseif (is_numeric($warehouseIds)) {
            $warehouseIds = [$warehouseIds];
        } elseif (is_array($warehouseIds)) {
            $warehouseIds = $warehouseIds;
        } else {
            return collect(); 
        }
        $warehouseIds = array_map('intval', $warehouseIds);
        return Warehouse::whereIn('id', $warehouseIds)
            ->with('locationRelation:id,name')
            ->get(['id', 'warehouse_code', 'warehouse_name', 'location','tin_no', 'is_efris','owner_number','warehouse_manager_contact']);
    }

      public function getSalesmenByWarehouse($warehouseId)
    {
        return Salesman::where('warehouse_id', $warehouseId)
            ->with('route:id,route_name')
            ->whereIn('type', [3])
            ->whereNotNull('route_id')
            ->where('route_id', '!=', 0) 
            ->where('status', 1)
            ->get(['id', 'name', 'osa_code','route_id'])
            ->makeHidden(['route']);
    } 
}