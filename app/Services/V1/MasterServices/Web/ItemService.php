<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\Item;
use App\Models\ItemUOM;
use App\Models\PricingHeader;
use App\Models\PricingDetail;
use App\Models\Agent_Transaction\InvoiceHeader;
use App\Http\Resources\V1\Agent_Transaction\InvoiceHeaderResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class ItemService
{
    // public function getAll(int $perPage = 50, array $filters = [], bool $dropdown = false, bool $allData = false)
    // {
    //     try {
    //         $query = Item::query();
    //         if (!empty($filters['category_id'])) {
    //             if (is_array($filters['category_id'])) {
    //                 $query->whereIn('category_id', $filters['category_id']);
    //             } else {
    //                 $query->where('category_id', $filters['category_id']);
    //             }
    //         }
    //         if (isset($filters['status'])) {
    //             if ($filters['status'] === '0' || $filters['status'] === 0) {
    //                 $query->where('status', 0);
    //             } elseif ($filters['status'] === '1' || $filters['status'] === 1) {
    //                 $query->where('status', 1);
    //             }
    //         }
    //         if ($dropdown) {
    //             return $query->select(['id', 'code', 'name'])
    //                 ->orderBy('name')
    //                 ->get();
    //         }
    //         if ($allData) {
    //             return $query->select([
    //                 'id',
    //                 'uuid',
    //                 'code',
    //                 'erp_code',
    //                 'name',
    //                 'status',
    //             ])
    //                 ->with([
    //                     'itemUoms:id,item_id,uom_type,name,price,is_stock_keeping,upc,enable_for,uom_id',
    //                     'warehouse_stocks:id,item_id,warehouse_id,qty'
    //                 ])
    //                 ->latest()
    //                 ->get();
    //         }
    //         $query->select([
    //             'id',
    //             'uuid',
    //             'code',
    //             'erp_code',
    //             'name',
    //             'description',
    //             'item_weight',
    //             'shelf_life',
    //             'brand',
    //             'category_id',
    //             'sub_category_id',
    //             'image',
    //             'status',
    //             'excise_duty_code',
    //             'commodity_goods_code',
    //             'volume',
    //             'is_taxable',
    //             'has_excies'
    //         ])
    //             ->with([
    //                 'itemCategory:id,category_name',
    //                 'itemSubCategory:id,sub_category_name',
    //             ])
    //             ->latest();
    //         return $query->paginate($perPage);
    //     } catch (\Throwable $e) {
    //         throw new \Exception("Failed to fetch items: " . $e->getMessage());
    //     }
    // }
    // public function getAll(int $perPage = 50, array $filters = [], bool $dropdown = false, bool $allData = false)
    // {
    //     try {
    //         $query = Item::query();
    //         if (!empty($filters['category_id'])) {
    //             is_array($filters['category_id'])
    //                 ? $query->whereIn('category_id', $filters['category_id'])
    //                 : $query->where('category_id', $filters['category_id']);
    //         }
    //         if (isset($filters['status'])) {
    //             $query->where('status', (int) $filters['status']);
    //         }
    //         if (!empty($filters['warehouse_id'])) {
    //             $warehouseId = $filters['warehouse_id'];
    //             $query->whereHas('warehouse_stocks', function ($q) use ($warehouseId) {
    //                 $q->where('warehouse_id', $warehouseId);
    //             });
    //             $query->with(['warehouse_stocks' => function ($q) use ($warehouseId) {
    //                 $q->select(['id', 'item_id', 'warehouse_id', 'qty'])
    //                   ->where('warehouse_id', $warehouseId);
    //             }]);
    //         }
    //         if ($dropdown) {
    //             return $query->select(['id', 'code', 'name'])
    //                 ->orderBy('name')
    //                 ->get();
    //         }
    //         if ($allData) {
    //             return $query->select([
    //                 'id',
    //                 'uuid',
    //                 'code',
    //                 'erp_code',
    //                 'name',
    //                 'status',
    //             ])
    //             ->with([
    //                 'itemUoms:id,item_id,uom_type,name,price,is_stock_keeping,upc,enable_for,uom_id'
    //             ])->latest()->get();
    //         }
    //         $query->select([
    //             'id',
    //             'uuid',
    //             'code',
    //             'erp_code',
    //             'name',
    //             'description',
    //             'item_weight',
    //             'shelf_life',
    //             'brand',
    //             'category_id',
    //             'sub_category_id',
    //             'image',
    //             'status',
    //             'excise_duty_code',
    //             'commodity_goods_code',
    //             'volume',
    //             'is_taxable',
    //             'has_excies'
    //         ])
    //         ->with([
    //             'itemCategory:id,category_name',
    //             'itemSubCategory:id,sub_category_name',
    //         ])->latest();
    //         return $query->paginate($perPage);
    //     } catch (\Throwable $e) {
    //         throw new \Exception("Failed to fetch items: " . $e->getMessage());
    //     }
    // }
    public function getAll(int $perPage = 50, array $filters = [], bool $dropdown = false, bool $allData = false)
    {
        try {
            $query = Item::query();
            if (!empty($filters['category_id'])) {
                is_array($filters['category_id'])
                    ? $query->whereIn('category_id', $filters['category_id'])
                    : $query->where('category_id', $filters['category_id']);
            }
            // if (isset($filters['status'])) {
            //     $query->where('status', (int)$filters['status']);
            // }
            if (!empty($filters['warehouse_id'])) {
                $warehouseId = $filters['warehouse_id'];
                $query->whereHas('warehouse_stocks', function ($q) use ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId);
                });
            }
            if ($dropdown) {
                return $query->select(['id', 'code', 'erp_code', 'name'])
                    ->orderBy('name')
                    ->get();
            }
            if ($allData) {
                $query->select([
                    'id',
                    'uuid',
                    'code',
                    'erp_code',
                    'name',
                    'status',
                ]);
                if (!empty($filters['warehouse_id'])) {
                    $warehouseId = $filters['warehouse_id'];
                    $query->with([
                        'warehouse_stocks' => function ($q) use ($warehouseId) {
                            $q->select(['id', 'item_id', 'warehouse_id', 'qty'])
                                ->where('warehouse_id', $warehouseId);
                        },
                        'itemUoms:id,item_id,uom_type,name,price,is_stock_keeping,upc,enable_for,uom_id',
                        'pricing_details:item_id,buom_pc_price,auom_ctn_price',
                    ]);
                } else {
                    $query->with([
                        'warehouse_stocks:id,item_id,warehouse_id,qty',
                        'itemUoms:id,item_id,uom_type,name,price,is_stock_keeping,upc,enable_for,uom_id',
                        'pricing_details:item_id,buom_pc_price,auom_ctn_price',
                    ]);
                }
                return $query->latest()->get();
            }
            $query->select([
                'id',
                'uuid',
                'code',
                'erp_code',
                'name',
                'description',
                'item_weight',
                'shelf_life',
                'brand',
                'category_id',
                'sub_category_id',
                'image',
                'status',
                'excise_duty_code',
                'commodity_goods_code',
                'volume',
                'is_taxable',
                'has_excies',
                'discount_status'
            ])
                ->with([
                    'itemCategory:id,category_name',
                    'itemSubCategory:id,sub_category_name',
                    'pricing_details:item_id,buom_pc_price,auom_ctn_price',
                ]);
            if (!empty($filters['warehouse_id'])) {
                $warehouseId = $filters['warehouse_id'];

                $query->with([
                    'warehouse_stocks' => function ($q) use ($warehouseId) {
                        $q->select(['id', 'item_id', 'warehouse_id', 'qty'])
                            ->where('warehouse_id', $warehouseId);
                    }
                ]);
            }
            if (isset($filters['status']) && (int)$filters['status'] === 0) {

                $query->orderBy('status', 'asc')
                    ->orderBy('id', 'desc');
            } else {

                $query->orderBy('status', 'desc')
                    ->orderBy('id', 'desc');
            }
            /* ======================= */
            return $query->paginate($perPage);
        } catch (\Throwable $e) {
            throw new \Exception("Failed to fetch items: " . $e->getMessage());
        }
    }

    public function getById($uuid)
    {
        return Item::where('uuid', $uuid)->firstorFail();
    }
    public function create(array $data)
    {
        DB::beginTransaction();

        try {

            $data['created_user'] = Auth::id();
            $data['updated_user'] = Auth::id();
            if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
                $path = $data['image']->store('public/image');
                $appUrl = rtrim(config('app.url'), '/');
                $relativePath = str_replace('public/', '', $path);
                $fullUrl = $appUrl . '/storage/app/public/' . $relativePath;

                $data['image'] = $fullUrl;
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
                    'item_id'           => $item->id,
                    'uom_id'              => $uom['uom'],
                    'uom_type'          => $uom['uom_type'],
                    'upc'               => $uom['upc'] ?? null,
                    'price'             => $uom['price'],
                    'is_stock_keeping'  => $uom['is_stock_keeping'] ?? false,
                    'enable_for'        => $uom['enable_for'],
                    'status'            => 1,
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
    public function updateItem(array $requestData, string $uuid)
    {
        // dd($requestData);
        DB::beginTransaction();

        try {
            $data = $requestData;

            // fields allowed for update (image removed)
            $fieldsToUpdate = [
                'code',
                'erp_code',
                'name',
                'description',
                'brand',
                'category_id',
                'sub_category_id',
                'item_weight',
                'shelf_life',
                'volume',
                'is_promotional',
                'caps_promo',
                'is_taxable',
                'has_excies',
                'status',
                'commodity_goods_code',
                'excise_duty_code',
                'discount_status'
            ];

            $updateData = [];

            foreach ($fieldsToUpdate as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }

            // boolean casting
            foreach (['status', 'is_taxable', 'has_excies', 'is_promotional'] as $f) {
                if (isset($updateData[$f])) {
                    $updateData[$f] = in_array($updateData[$f], [1, "1", true, "true"], true) ? 1 : 0;
                }
            }

            if (!empty($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {

                // extension detect
                $extension = $data['image']->getClientOriginalExtension()
                    ?: $data['image']->extension()
                    ?: str_replace('image/', '', $data['image']->getMimeType());

                // filename
                $filename = Str::random(40) . '.' . $extension;

                // relative folder path
                $relativePath = 'image/' . $filename;

                // save file to storage/app/public/image/
                $data['image']->storeAs('image', $filename, 'public');

                // PUBLIC URL using symlink
                $appUrl = rtrim(config('app.url'), '/');
                $updateData['image'] = $appUrl . '/storage/app/public/' . $relativePath;
            }


            // audit
            $updateData['updated_user'] = auth()->id();
            $updateData['updated_at']   = now();

            // update item
            DB::table('items')->where('uuid', $uuid)->update($updateData);

            // ----------------- UOM LOGIC SAME -------------------

            if (!empty($data['uoms']) && is_array($data['uoms'])) {

                $item = Item::where('uuid', $uuid)->first();

                $existingIds = DB::table('item_uoms')
                    ->where('item_id', $item->id)
                    ->pluck('id')
                    ->toArray();

                $processedIds = [];

                foreach ($data['uoms'] as $u) {
                    $uomData = [
                        'item_id'          => $item->id,
                        'uom_id'           => $u['uom'] ?? null,
                        'uom_type'         => $u['uom_type'] ?? null,
                        'upc'              => $u['upc'] ?? null,
                        'price'            => $u['price'] ?? null,
                        'is_stock_keeping' => isset($u['is_stock_keeping']) ? (int)$u['is_stock_keeping'] : 0,
                        'enable_for'       => $u['enable_for'] ?? null,
                        'status'           => isset($u['status']) ? (int)$u['status'] : 1,
                        'keeping_quantity' => $u['keeping_quantity'] ?? null,
                        'updated_at'       => now()
                    ];

                    $uomData = array_filter($uomData, fn($v) => $v !== null && $v !== '');

                    if (!empty($u['id'])) {
                        DB::table('item_uoms')->where('id', $u['id'])->update($uomData);
                        $processedIds[] = $u['id'];
                    } else {
                        $newId = DB::table('item_uoms')->insertGetId(array_merge($uomData, [
                            'created_at' => now()
                        ]));
                        $processedIds[] = $newId;
                    }
                }

                $toDelete = array_diff($existingIds, $processedIds);
                if (!empty($toDelete)) {
                    DB::table('item_uoms')->whereIn('id', $toDelete)->delete();
                }
            }

            DB::commit();

            return Item::with('itemUoms')->where('uuid', $uuid)->first();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw new \Exception("Failed to update item: " . $e->getMessage());
        }
    }

    public function delete(Item $item)
    {
        $item->delete();
        return true;
    }
    public function globalSearch(int $perPage = 50, ?string $searchTerm = null)
    {
        try {
            $query = Item::with([
                'itemCategory:id,category_name,category_code',
                'itemSubCategory:id,sub_category_name,sub_category_code',
                'createdUser:id,name',
                'updatedUser:id,name',
                'itemUoms:id,item_id,price,uom_type,name,uom_id'
            ]);
            $query->where('status', 1);

            if (!empty($searchTerm)) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('code', 'ILIKE', "%{$searchTerm}%")
                        ->orWhere('erp_code', 'ILIKE', "%{$searchTerm}%")
                        ->orWhere('name', 'ILIKE', "%{$searchTerm}%")
                        ->orWhere('description', 'ILIKE', "%{$searchTerm}%")
                        ->orWhereRaw("CAST(vat AS TEXT) ILIKE ?", ["%{$searchTerm}%"])
                        ->orWhereRaw("CAST(shelf_life AS TEXT) ILIKE ?", ["%{$searchTerm}%"]);
                });
            }
            // if (!empty($searchTerm)) {
            //     $searchTerm = strtolower($searchTerm);
            //     $query->where(function ($q) use ($searchTerm) {
            //         $likeSearch = '%' . $searchTerm . '%';
            //         $q->orWhereRaw("LOWER(code) LIKE ?", [$likeSearch])
            //             ->orWhereRaw("CAST(erp_code AS TEXT) LIKE ?", [$likeSearch])
            //             ->orWhereRaw("LOWER(name) LIKE ?", [$likeSearch])
            //             ->orWhereRaw("LOWER(description) LIKE ?", [$likeSearch])
            //             ->orWhereRaw("CAST(vat AS TEXT) LIKE ?", [$likeSearch])
            //             ->orWhereRaw("CAST(shelf_life AS TEXT) LIKE ?", [$likeSearch]);
            //     });
            // }
            $items = $query->paginate($perPage);
            $items->getCollection()->transform(function ($item) {
                $itemUoms = $item->itemUoms->map(function ($uom) {
                    return [
                        'id'        => $uom->id,
                        'name'      => $uom->name,
                        'uom_price' => $uom->price,
                        'uom_type'  => $uom->uom_type,
                        'uom_id'    => $uom->uom_id,
                        'uom_name'  => $uom->uom?->name ?? '',
                    ];
                })->toArray();
                $pricingHeader = \App\Models\PricingHeader::where(function ($q) use ($item) {
                    $q->whereRaw("item_id::text = ?", [(string) $item->id])
                        ->orWhereRaw("item_id ILIKE ?", ['%,' . $item->id . ',%'])
                        ->orWhereRaw("item_id ILIKE ?", [$item->id . ',%'])
                        ->orWhereRaw("item_id ILIKE ?", ['%,' . $item->id]);
                })
                    ->whereNull('deleted_at')
                    ->select('id', 'code', 'name')
                    ->first();

                $pricing = null;
                if ($pricingHeader) {
                    $pricingDetails = \App\Models\PricingDetail::where('header_id', $pricingHeader->id)
                        ->select('buom_pc_price', 'auom_ctn_price')
                        ->first();
                    $pricing = $pricingDetails ? $pricingDetails->toArray() : null;
                }

                return [
                    'id'                 => $item->id,
                    'uuid'               => $item->uuid,
                    'erp_code'           => $item->erp_code,
                    'code'               => $item->code,
                    'name'               => $item->name,
                    'description'        => $item->description,
                    'image'              => $item->image,
                    'category_id'        => $item->category_id,
                    'sub_category_id'    => $item->sub_category_id,
                    'shelf_life'         => $item->shelf_life,
                    'status'             => $item->status,
                    'created_user'       => $item->createdUser ? [
                        'id'   => $item->createdUser->id,
                        'name' => $item->createdUser->name,
                    ] : null,
                    'updated_user'       => $item->updatedUser ? [
                        'id'   => $item->updatedUser->id,
                        'name' => $item->updatedUser->name,
                    ] : null,
                    'created_at'         => $item->created_at,
                    'updated_at'         => $item->updated_at,
                    'deleted_at'         => $item->deleted_at,
                    'brand'              => $item->brand,
                    'item_weight'        => $item->item_weight,
                    'volume'             => $item->volume,
                    'is_promotional'     => $item->is_promotional,
                    'is_taxable'         => $item->is_taxable,
                    'has_excies'         => $item->has_excies,
                    'commodity_goods_code' => $item->commodity_goods_code,
                    'excise_duty_code'   => $item->excise_duty_code,
                    'customer_code'      => $item->customer_code,
                    'base_uom_vol'       => $item->base_uom_vol,
                    'alter_base_uom_vol' => $item->alter_base_uom_vol,
                    'item_category'      => $item->itemCategory ? [
                        'id'            => $item->itemCategory->id,
                        'category_name' => $item->itemCategory->category_name,
                        'category_code' => $item->itemCategory->category_code,
                    ] : null,
                    'distribution_code'  => $item->distribution_code,
                    'barcode'            => $item->barcode,
                    'net_weight'         => $item->net_weight,
                    'tax'                => $item->tax,
                    'vat'                => $item->vat,
                    'excise'             => $item->excise,
                    'uom_efris_code'     => $item->uom_efris_code,
                    'altuom_efris_code'  => $item->altuom_efris_code,
                    'item_group'         => $item->item_group,
                    'item_group_desc'    => $item->item_group_desc,
                    'caps_promo'         => $item->caps_promo,
                    'sequence_no'        => $item->sequence_no,
                    'item_uoms'          => $itemUoms,
                    'pricing'            => $pricing,
                    'item_sub_category'  => $item->itemSubCategory ? [
                        'id'                => $item->itemSubCategory->id,
                        'sub_category_name' => $item->itemSubCategory->sub_category_name,
                        'sub_category_code' => $item->itemSubCategory->sub_category_code,
                    ] : null,
                ];
            });

            return $items;
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to perform global search: ' . $e->getMessage()
            ], 500);
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
                'name',
                'description',
                'uom',
                'upc',
                'category_id',
                'sub_category_id',
                'vat',
                'excies',
                'shelf_life',
                'community_code',
                'excise_code',
                'status',
                'erp_code'
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

    // public function globalSearchItems($perPage = 50, $keyword = null)
    //     {
    //         try {
    //             $query = Item::query()
    //                 ->select('items.*')
    //                 ->leftJoin('item_categories', 'items.category_id', '=', 'item_categories.id')
    //                 ->leftJoin('item_sub_categories', 'items.sub_category_id', '=', 'item_sub_categories.id')
    //                 ->leftJoin('users as createdUser', 'items.created_user', '=', 'createdUser.id')
    //                 ->leftJoin('users as updatedUser', 'items.updated_user', '=', 'updatedUser.id');
    //             if (!empty($keyword)) {
    //                 $query->where(function ($q) use ($keyword) {
    //                     $textFields = [
    //                         'items.name',
    //                         'items.code',
    //                         'items.erp_code',
    //                         'items.description',
    //                         'items.brand',
    //                         'items.shelf_life',
    //                         'items.commodity_goods_code',
    //                         'items.excise_duty_code',
    //                         'items.customer_code',
    //                         'items.item_category',
    //                         'items.distribution_code',
    //                         'items.barcode',
    //                         'items.tax',
    //                         'items.vat',
    //                         'items.excise',
    //                         'items.uom_efris_code',
    //                         'items.altuom_efris_code',
    //                         'items.item_group',
    //                         'items.item_group_desc'
    //                     ];

    //                     foreach ($textFields as $field) {
    //                         $q->orWhere($field, 'ILIKE', "%{$keyword}%");
    //                     }
    //                     $numericFields = ['items.id', 'items.category_id', 'items.sub_category_id', 'items.status', 'items.created_user', 'items.updated_user', 'items.caps_promo', 'items.sequence_no', 'items.volume', 'items.item_weight', 'items.base_uom_vol', 'items.alter_base_uom_vol'];
    //                     foreach ($numericFields as $field) {
    //                         $q->orWhereRaw("CAST({$field} AS TEXT) ILIKE ?", ['%' . $keyword . '%']);
    //                     }
    //                     $q->orWhere('item_categories.category_name', 'ILIKE', "%{$keyword}%");
    //                     $q->orWhere('item_sub_categories.sub_category_name', 'ILIKE', "%{$keyword}%");
    //                     $q->orWhere('createdUser.name', 'ILIKE', "%{$keyword}%");
    //                     $q->orWhere('updatedUser.name', 'ILIKE', "%{$keyword}%");
    //                 });
    //             }
    //             $query->with([
    //                 'itemCategory:id,category_name,category_code',
    //                 'itemSubCategory:id,sub_category_name,sub_category_code',
    //                 'createdUser:id,name,username',
    //                 'updatedUser:id,name,username',
    //                 'itemUoms' // if you need all UOMs
    //             ]);

    //             return $query->paginate($perPage);
    //         } catch (\Exception $e) {
    //             throw new \Exception("Failed to search items: " . $e->getMessage());
    //         }
    //     }



    public function exportItems($startDate = null, $endDate = null)
    {
        $items = Item::with(['category', 'subcategory'])
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ]);
            })
            ->get();

        return $items->map(function ($item) {
            return [
                'ERP Code'            => $item->erp_code,
                'Code'                => $item->code,
                'Name'                => $item->name,
                'Description'         => $item->description,
                'Category'            => $item->category->category_name ?? null,
                'Sub Category'        => $item->subcategory->sub_category_name ?? null,
                'Brand'               => $item->brand,
                'Item Weight'         => $item->item_weight,
                'Volume'              => $item->volume,
                'Shelf Life'          => $item->shelf_life,
                'Is Promotional'      => $item->is_promotional ? 'Yes' : 'No',
                'Is Taxable'          => $item->is_taxable ? 'Yes' : 'No',
                'Has Excise'          => $item->has_excies ? 'Yes' : 'No',
                'Commodity Code'      => $item->commodity_goods_code,
                'Excise Duty Code'    => $item->excise_duty_code,
                'Customer Code'       => $item->customer_code,
                'Base UOM Vol'        => $item->base_uom_vol,
                'Alt Base UOM Vol'    => $item->alter_base_uom_vol,
                'Item Category'       => $item->item_category,
                'Distribution Code'   => $item->distribution_code,
                'Barcode'             => $item->barcode,
                'Net Weight'          => $item->net_weight,
                'Tax'                 => $item->tax,
                'VAT'                 => $item->vat,
                'Excise'              => $item->excise,
                'UOM Efris Code'      => $item->uom_efris_code,
                'Alt UOM Efris Code'  => $item->altuom_efris_code,
                'Item Group'          => $item->item_group,
                'Item Group Desc'     => $item->item_group_desc,
                'Status'              => $item->status == 1 ? 'Active' : 'Inactive',
                'Created At'          => $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : null,
            ];
        });
    }
    // public function getItemInvoices(int $itemId, int $perPage = 50)
    // {
    //     try {
    //         return InvoiceHeader::whereHas('details', function ($q) use ($itemId) {
    //             $q->where('item_id', $itemId);
    //         })
    //             ->with([
    //                 'details' => function ($q) use ($itemId) {
    //                     $q->where('item_id', $itemId);
    //                 }
    //             ])
    //             ->paginate($perPage);
    //     } catch (\Throwable $e) {
    //         throw new \Exception("Failed to fetch invoices: " . $e->getMessage());
    //     }
    // }
    public function getItemInvoices(int $itemId, int $perPage = 50)
    {
        try {
            $fromDate = request()->get('from_date'); // YYYY-MM-DD
            $toDate   = request()->get('to_date');   // YYYY-MM-DD

            return InvoiceHeader::whereHas('details', function ($q) use ($itemId) {
                $q->where('item_id', $itemId);
            })

                // 🔹 If both dates provided → date range
                ->when($fromDate && $toDate, function ($q) use ($fromDate, $toDate) {
                    $q->whereBetween('invoice_date', [$fromDate, $toDate]);
                })

                // 🔹 If only from_date
                ->when($fromDate && !$toDate, function ($q) use ($fromDate) {
                    $q->whereDate('invoice_date', '>=', $fromDate);
                })

                // 🔹 If only to_date
                ->when(!$fromDate && $toDate, function ($q) use ($toDate) {
                    $q->whereDate('invoice_date', '<=', $toDate);
                })

                // 🔹 Default = Current Month (when no date filter)
                ->when(!$fromDate && !$toDate, function ($q) {
                    $q->whereBetween('invoice_date', [
                        now()->startOfMonth()->toDateString(),
                        now()->endOfMonth()->toDateString()
                    ]);
                })

                ->with([
                    'details' => function ($q) use ($itemId) {
                        $q->where('item_id', $itemId);
                    }
                ])

                ->paginate($perPage);
        } catch (\Throwable $e) {
            throw new \Exception("Failed to fetch invoices: " . $e->getMessage());
        }
    }


    public function getItemReturns(int $perPage = 50, int $itemId)
    {
        try {
            $query = DB::table('return_details as d')
                ->join('return_header as h', 'd.header_id', '=', 'h.id')
                ->join('items as i', 'd.item_id', '=', 'i.id')
                ->leftJoin('item_uoms as iu', 'iu.id', '=', 'd.uom_id')
                ->leftJoin('uom as u', 'u.id', '=', 'iu.uom_id')
                ->select(
                    'h.id as header_id',
                    'h.uuid as header_uuid',
                    'h.osa_code as header_code',
                    // 'h.customer_id',
                    // 'h.warehouse_id',
                    // 'h.total as header_total',
                    // 'h.status as header_status',
                    'd.id as detail_id',
                    'd.uuid as detail_uuid',
                    'd.item_id',
                    'i.name as item_name',
                    'i.code as item_code',
                    'd.uom_id as uom',
                    // 'iu.id as uoms',     
                    'u.id as uom_id',
                    'u.name as name',
                    'u.osa_code as code',
                    'd.item_price',
                    'd.item_quantity',
                    // 'd.vat',
                    // 'd.discount',
                    // 'd.gross_total',
                    // 'd.net_total',
                    // 'd.total',
                    // 'd.is_promotional',
                    // 'd.parent_id',
                    // 'd.status as detail_status'
                )
                ->where('d.item_id', $itemId)
                ->whereNull('d.deleted_at')
                ->whereNull('h.deleted_at')
                ->orderBy('h.created_at', 'desc');

            return $query->paginate($perPage);
        } catch (\Throwable $e) {
            throw new \Exception("Failed to fetch returns: " . $e->getMessage());
        }
    }
}
