<?php

// namespace App\Services\V1\MasterServices\Web;

// use App\Models\Item;
// use App\Models\ItemUOM;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Str;
// use PhpOffice\PhpSpreadsheet\IOFactory;
// use Throwable;

// class ItemService
// {
// public function getAll(int $perPage = 50, array $filters = [], bool $dropdown = false)
// {
//     if ($dropdown) {
//         $query = Item::select(['id', 'code', 'name']);
            
//         $data = $query->get();
//         return $data;
//     } else { 
//         $query = Item::select([
//             'id', 'code','erp_code', 'name', 'description','item_weight', 'shelf_life','brand',
//             'category_id', 'sub_category_id', 'image','status','excise_duty_code','commodity_goods_code','item_weight','volume','is_taxable','has_excies'
//         ])
//         ->with([
//             'itemCategory:id,category_name,category_code',
//             'itemSubCategory:id,sub_category_name,sub_category_code',
//             'itemUoms:id,item_id,uom_type,name,price,is_stock_keeping,upc,enable_for'  
//         ])->latest();
//     }
//     if (!empty($filters['category_id'])) {
//         $query->where('category_id', $filters['category_id']);
//     }
//     return $query->paginate($perPage);
// }



// public function getById($id)
//     {
//         return Item::findOrFail($id);
//     }

// public function create(array $data)
// {
//     DB::beginTransaction();
//     try {
//         $data['created_user'] = Auth::id();
//         $data['updated_user'] = Auth::id();
//         if (!empty($data['code'])) {
//             if (Item::where('code', $data['code'])->exists()) {
//                 throw new \Exception("The code '{$data['code']}' already exists.");
//             }
//         } else {
//             do {
//                 $lastItem = Item::withTrashed()->orderBy('id', 'desc')->first();
//                 $nextNumber = $lastItem
//                     ? ((int) preg_replace('/\D/', '', $lastItem->code)) + 1
//                     : 1;
//                 $code = 'IT' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
//             } while (Item::where('code', $code)->exists());
//             $data['code'] = $code;
//         }
//         if (!empty($data['erp_code'])) {
//             if (Item::where('erp_code', $data['erp_code'])->exists()) {
//                 throw new \Exception("The erp_code '{$data['erp_code']}' already exists.");
//             }
//         } else {
//             do {
//                 $lastItem = Item::withTrashed()->orderBy('id', 'desc')->first();
//                 $nextNumber = $lastItem
//                     ? ((int) preg_replace('/\D/', '', $lastItem->erp_code)) + 1
//                     : 1;

//                 $erp_code = 'SAP' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
//             } while (Item::where('erp_code', $erp_code)->exists());

//             $data['erp_code'] = $erp_code;
//         }
//         $data['uuid'] = Str::uuid()->toString();
//         $uoms = $data['uoms'] ?? [];
//         unset($data['uoms']);
//         $item = Item::create($data);
//         foreach ($uoms as $uom) {
//             $itemUomData = [
//                 'item_id' => $item->id,
//                 'name' =>$uom['uom'],
//                 'uom_type' => $uom['uom_type'],
//                 'upc' => $uom['upc'] ?? null,
//                 'price' => $uom['price'],
//                 'is_stock_keeping' => $uom['is_stock_keeping'] ?? false,
//                 'enable_for' => $uom['enable_for'],
//                 'status' => 1,
//             ];
//             if (!empty($itemUomData['is_stock_keeping']) && isset($uom['keeping_quantity'])) {
//                 $itemUomData['keeping_quantity'] = $uom['keeping_quantity'];
//             }
//             ItemUOM::create($itemUomData);
//         }
//         DB::commit();
//         return $item;
//     } catch (\Exception $e) {
//         DB::rollBack();
//         throw new \Exception("Failed to create item: " . $e->getMessage());
//     }
// }

// public function update(Item $item, array $data)
//     {
//         DB::beginTransaction();
//         try {
//             $data['updated_user'] = Auth::id();
//             $item->update($data);
//             DB::commit();
//             return $item;
//         } catch (Throwable $e) {
//             DB::rollBack();
//             throw new \Exception("Failed to update item: " . $e->getMessage());
//         }
//     }

// public function delete(Item $item)
//     {
//         $item->delete(); // Soft delete
//         return true;
//     }
// public function globalSearch(int $perPage = 10, ?string $searchTerm = null)
//     {
//         try {
//             $query = Item::with([
//                 'itemCategory:id,category_name,category_code',
//                 'itemSubCategory:id,sub_category_name,sub_category_code',
//                 'createdUser:id,name',
//                 'updatedUser:id,name'
//             ]);

//             if (!empty($searchTerm)) {
//                 $searchTerm = strtolower($searchTerm);

//                 $query->where(function ($q) use ($searchTerm) {
//                     $likeSearch = '%' . $searchTerm . '%';

//                     $q->orWhereRaw("LOWER(code) LIKE ?", [$likeSearch])
//                       ->orWhereRaw("LOWER(name) LIKE ?", [$likeSearch])
//                       ->orWhereRaw("LOWER(description) LIKE ?", [$likeSearch])
//                       ->orWhereRaw("CAST(vat AS TEXT) LIKE ?", [$likeSearch])
//                       ->orWhereRaw("CAST(shelf_life AS TEXT) LIKE ?", [$likeSearch]);
//                 });
//             }

//             return $query->paginate($perPage);
//         } catch (\Exception $e) {
//             throw new \Exception("Failed to perform global search: " . $e->getMessage());
//         }
//     }

// public function bulkUpload($file)
// {
//     DB::beginTransaction();
//     try {
//         $userId = Auth::id();
//         $spreadsheet = IOFactory::load($file->getRealPath());
//         $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
//         if (empty($sheetData) || count($sheetData) < 2) {
//             throw new \Exception("Excel file is empty or invalid.");
//         }
//         $header = array_map('strtolower', array_map('trim', $sheetData[1]));
//         unset($sheetData[1]);
//         $expectedHeaders = [
//             'name', 'description', 'uom', 'upc', 'category_id',
//             'sub_category_id', 'vat', 'excies', 'shelf_life',
//             'community_code', 'excise_code', 'status', 'erp_code'
//         ];
//         foreach ($expectedHeaders as $expected) {
//             if (!in_array($expected, $header)) {
//                 throw new \Exception("Missing required header: {$expected}");
//             }
//         }
//         $createdItems = [];
//         foreach ($sheetData as $row) {
//             $data = array_combine($header, array_values($row));
//             if (!array_filter($data)) continue;
//             $required = ['name', 'description', 'uom', 'upc', 'category_id', 'sub_category_id', 'vat', 'excies', 'shelf_life', 'community_code', 'excise_code', 'status'];
//             foreach ($required as $field) {
//                 if (empty($data[$field])) {
//                     throw new \Exception("Row missing required field: {$field}");
//                 }
//             }
//             if (!empty($data['erp_code'])) {
//                 if (Item::where('erp_code', $data['erp_code'])->exists()) {
//                     throw new \Exception("The erp_code '{$data['erp_code']}' already exists.");
//                 }
//                 $erp_code = $data['erp_code'];
//             } else {
//                 do {
//                     $lastItem = Item::withTrashed()->orderBy('id', 'desc')->first();
//                     $nextSapNumber = $lastItem
//                         ? ((int) preg_replace('/\D/', '', $lastItem->erp_code)) + 1
//                         : 1;
//                     $erp_code = 'SAP' . str_pad($nextSapNumber, 4, '0', STR_PAD_LEFT);
//                 } while (Item::where('erp_code', $erp_code)->exists());
//             }
//             do {
//                 $lastItem = Item::withTrashed()->orderBy('id', 'desc')->first();
//                 $nextCodeNumber = $lastItem
//                     ? ((int) preg_replace('/\D/', '', $lastItem->code)) + 1
//                     : 1;
//                 $code = 'IT' . str_pad($nextCodeNumber, 4, '0', STR_PAD_LEFT);
//             } while (Item::where('code', $code)->exists());
//             $itemData = [
//                 'uuid' => Str::uuid()->toString(),
//                 'erp_code' => $erp_code,
//                 'code' => $code,
//                 'name' => $data['name'],
//                 'description' => $data['description'] ?? null,
//                 'uom' => (int) $data['uom'],
//                 'upc' => (int) $data['upc'],
//                 'category_id' => (int) $data['category_id'],
//                 'sub_category_id' => (int) $data['sub_category_id'],
//                 'vat' => (int) $data['vat'],
//                 'excies' => (int) $data['excies'],
//                 'shelf_life' => $data['shelf_life'] ?? null,
//                 'community_code' => $data['community_code'] ?? null,
//                 'excise_code' => $data['excise_code'] ?? null,
//                 'status' => (int) $data['status'] ?? 1,
//                 'created_user' => $userId,
//                 'updated_user' => $userId,
//             ];

//             $createdItems[] = Item::create($itemData);
//         }
//         DB::commit();
//         return $createdItems;
//     } catch (\Exception $e) {
//         DB::rollBack();
//         return response()->json([
//             'status' => 'error',
//             'message' => 'Bulk upload failed: ' . $e->getMessage(),
//         ], 500);
//     }
// }


// }

namespace App\Services\V1\MasterServices\Web;

use App\Models\Item;
use App\Models\ItemUOM;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class ItemService
{
public function getAll(int $perPage = 50, array $filters = [], bool $dropdown = false)
{
    if ($dropdown) {
        $query = Item::select(['id', 'code', 'name']);
            
        $data = $query->get();
        return $data;
    } else {
        $query = Item::select([
            'id', 'code','erp_code', 'name', 'description','item_weight', 'shelf_life','brand',
            'category_id', 'sub_category_id', 'image','status','excise_duty_code','commodity_goods_code','item_weight','volume','is_taxable','has_excies'
        ])
        ->with([
            'itemCategory:id,category_name,category_code',
            'itemSubCategory:id,sub_category_name,sub_category_code',
            'itemUoms:id,item_id,uom_type,name,price,is_stock_keeping,upc,enable_for'  
        ])->latest();
    }
    if (!empty($filters['category_id'])) {
        $query->where('category_id', $filters['category_id']);
    }
    return $query->paginate($perPage);
}



public function getById($id)
    {
        return Item::findOrFail($id);
    }

public function create(array $data)
{
    DB::beginTransaction();
    try {
        $data['created_user'] = Auth::id();
        $data['updated_user'] = Auth::id();

     if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
        $path = $data['image']->store('public/image');
        $data['image'] = str_replace('public/', 'storage/', $path); 
    }
        if (!empty($data['code'])) {
            if (Item::where('code', $data['code'])->exists()) {
                throw new \Exception("The code '{$data['code']}' already exists.");
            }
        } else {
            do {
                $lastItem = Item::withTrashed()->orderBy('id', 'desc')->first();
                $nextNumber = $lastItem
                    ? ((int) preg_replace('/\D/', '', $lastItem->code)) + 1
                    : 1;
                $code = 'IT' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            } while (Item::where('code', $code)->exists());
            $data['code'] = $code;
        }
        if (!empty($data['erp_code'])) {
            if (Item::where('erp_code', $data['erp_code'])->exists()) {
                throw new \Exception("The erp_code '{$data['erp_code']}' already exists.");
            }
        } else {
            do {
                $lastItem = Item::withTrashed()->orderBy('id', 'desc')->first();
                $nextNumber = $lastItem
                    ? ((int) preg_replace('/\D/', '', $lastItem->erp_code)) + 1
                    : 1;

                $erp_code = 'SAP' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            } while (Item::where('erp_code', $erp_code)->exists());

            $data['erp_code'] = $erp_code;
        }
        $data['uuid'] = Str::uuid()->toString();
        $uoms = $data['uoms'] ?? [];
        unset($data['uoms']);
        $item = Item::create($data);
        foreach ($uoms as $uom) {
            $itemUomData = [
                'item_id' => $item->id,
                'name' =>$uom['uom'],
                'uom_type' => $uom['uom_type'],
                'upc' => $uom['upc'] ?? null,
                'price' => $uom['price'],
                'is_stock_keeping' => $uom['is_stock_keeping'] ?? false,
                'enable_for' => $uom['enable_for'],
                'status' => 1,
            ];
            if (!empty($itemUomData['is_stock_keeping']) && isset($uom['keeping_quantity'])) {
                $itemUomData['keeping_quantity'] = $uom['keeping_quantity'];
            }
            ItemUOM::create($itemUomData);
        }
        DB::commit();
        return $item;
    } catch (\Exception $e) {
        DB::rollBack();
        throw new \Exception("Failed to create item: " . $e->getMessage());
    }
}

public function update(Item $item, array $data)
    {
        DB::beginTransaction();
        try {
            $data['updated_user'] = Auth::id();
            $item->update($data);
            DB::commit();
            return $item;
        } catch (Throwable $e) {
            DB::rollBack();
            throw new \Exception("Failed to update item: " . $e->getMessage());
        }
    }

public function delete(Item $item)
    {
        $item->delete(); // Soft delete
        return true;
    }
public function globalSearch(int $perPage = 10, ?string $searchTerm = null)
    {
        try {
            $query = Item::with([
                'itemCategory:id,category_name,category_code',
                'itemSubCategory:id,sub_category_name,sub_category_code',
                'createdUser:id,name',
                'updatedUser:id,name'
            ]);

            if (!empty($searchTerm)) {
                $searchTerm = strtolower($searchTerm);

                $query->where(function ($q) use ($searchTerm) {
                    $likeSearch = '%' . $searchTerm . '%';

                    $q->orWhereRaw("LOWER(code) LIKE ?", [$likeSearch])
                      ->orWhereRaw("LOWER(name) LIKE ?", [$likeSearch])
                      ->orWhereRaw("LOWER(description) LIKE ?", [$likeSearch])
                      ->orWhereRaw("CAST(vat AS TEXT) LIKE ?", [$likeSearch])
                      ->orWhereRaw("CAST(shelf_life AS TEXT) LIKE ?", [$likeSearch]);
                });
            }

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            throw new \Exception("Failed to perform global search: " . $e->getMessage());
        }
    }

public function bulkUpload($file)
{
    DB::beginTransaction();
    try {
        $userId = Auth::id();
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        if (empty($sheetData) || count($sheetData) < 2) {
            throw new \Exception("Excel file is empty or invalid.");
        }
        $header = array_map('strtolower', array_map('trim', $sheetData[1]));
        unset($sheetData[1]);
        $expectedHeaders = [
            'name', 'description', 'uom', 'upc', 'category_id',
            'sub_category_id', 'vat', 'excies', 'shelf_life',
            'community_code', 'excise_code', 'status', 'erp_code'
        ];
        foreach ($expectedHeaders as $expected) {
            if (!in_array($expected, $header)) {
                throw new \Exception("Missing required header: {$expected}");
            }
        }
        $createdItems = [];
        foreach ($sheetData as $row) {
            $data = array_combine($header, array_values($row));
            if (!array_filter($data)) continue;
            $required = ['name', 'description', 'uom', 'upc', 'category_id', 'sub_category_id', 'vat', 'excies', 'shelf_life', 'community_code', 'excise_code', 'status'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new \Exception("Row missing required field: {$field}");
                }
            }
            if (!empty($data['erp_code'])) {
                if (Item::where('erp_code', $data['erp_code'])->exists()) {
                    throw new \Exception("The erp_code '{$data['erp_code']}' already exists.");
                }
                $erp_code = $data['erp_code'];
            } else {
                do {
                    $lastItem = Item::withTrashed()->orderBy('id', 'desc')->first();
                    $nextSapNumber = $lastItem
                        ? ((int) preg_replace('/\D/', '', $lastItem->erp_code)) + 1
                        : 1;
                    $erp_code = 'SAP' . str_pad($nextSapNumber, 4, '0', STR_PAD_LEFT);
                } while (Item::where('erp_code', $erp_code)->exists());
            }
            do {
                $lastItem = Item::withTrashed()->orderBy('id', 'desc')->first();
                $nextCodeNumber = $lastItem
                    ? ((int) preg_replace('/\D/', '', $lastItem->code)) + 1
                    : 1;
                $code = 'IT' . str_pad($nextCodeNumber, 4, '0', STR_PAD_LEFT);
            } while (Item::where('code', $code)->exists());
            $itemData = [
                'uuid' => Str::uuid()->toString(),
                'erp_code' => $erp_code,
                'code' => $code,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'uom' => (int) $data['uom'],
                'upc' => (int) $data['upc'],
                'category_id' => (int) $data['category_id'],
                'sub_category_id' => (int) $data['sub_category_id'],
                'vat' => (int) $data['vat'],
                'excies' => (int) $data['excies'],
                'shelf_life' => $data['shelf_life'] ?? null,
                'community_code' => $data['community_code'] ?? null,
                'excise_code' => $data['excise_code'] ?? null,
                'status' => (int) $data['status'] ?? 1,
                'created_user' => $userId,
                'updated_user' => $userId,
            ];

            $createdItems[] = Item::create($itemData);
        }
        DB::commit();
        return $createdItems;
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => 'error',
            'message' => 'Bulk upload failed: ' . $e->getMessage(),
        ], 500);
    }
}

public function updateItemsStatus(array $itemIds, $status)
{
    $updated = Item::whereIn('id', $itemIds)->update(['status' => $status]);
    return $updated > 0;
}
}