<?php

namespace App\Services\V1\MasterServices\Mob;
use Illuminate\Support\Facades\DB;

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
        $customerCategoryFile = "{$directory}/customer_category_{$username}.txt";
        $customerSubCategoryFile = "{$directory}/customer_sub_category_{$username}.txt";
        $outletChannelFile = "{$directory}/outlet_channel_{$username}.txt";
        $pricingHeadersFile = "{$directory}/pricing_headers_{$username}.txt";

        // Agar file exist nahi karti tabhi likho
        if (!file_exists($itemFile)) {
            $items = DB::table('items')->get();
            file_put_contents($itemFile, json_encode($items));
        }

        if (!file_exists($customerCategoryFile)) {
            $customerCategory = DB::table('customer_categories')->get();
            file_put_contents($customerCategoryFile, json_encode($customerCategory));
        }

        if (!file_exists($customerSubCategoryFile)) {
            $customerSubCategory = DB::table('customer_sub_categories')->get();
            file_put_contents($customerSubCategoryFile, json_encode($customerSubCategory));
        }

        if (!file_exists($outletChannelFile)) {
            $outletChannel = DB::table('outlet_channel')->get();
            file_put_contents($outletChannelFile, json_encode($outletChannel));
        }

        if (!file_exists($pricingHeadersFile)) {
            $pricingHeaders = DB::table('pricing_headers')->get();
            file_put_contents($pricingHeadersFile, json_encode($pricingHeaders));
        }

        // Short relative path return karo
        return [
            'item_file' => '/storage/stetic_files/items_' . $username . '.txt',
            'customer_category_file' => '/storage/stetic_files/customer_category_' . $username . '.txt',
            'customer_subcategory_file' => '/storage/stetic_files/customer_sub_category_' . $username . '.txt',
            'outlet_channel_file' => '/storage/stetic_files/outlet_channel_' . $username . '.txt',
            'pricing_headers_file' => '/storage/stetic_files/pricing_headers_' . $username . '.txt',
            
        ];
    }
    public function getWarehouses()
    {
        return DB::table('tbl_warehouse')
            ->select('id', 'warehouse_code', 'warehouse_name')
            ->get();
    }
}
