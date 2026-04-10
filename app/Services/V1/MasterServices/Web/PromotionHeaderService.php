<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\PromotionHeader;
use App\Models\PromotionDetail;
use App\Models\PromotionOfferItem;
use App\Models\PromotionalSlab;
use App\Models\AgentCustomer;
use App\Models\Warehouse;
use App\Models\Agent_Transaction\InvoiceHeader;
use App\Models\Area;
use App\Models\Region;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PromotionHeaderService
{
    /**
     * CREATE
     */
    // public function create(array $data): PromotionHeader
    // {
    //     DB::beginTransaction();

    //     try {
    //         $osaCode = !empty($data['osa_code'])
    //             ? $data['osa_code']
    //             : $this->generateOsaCode();

    //         $offer      = $data['offer_items'][0] ?? [];
    //         $percentage = $data['percentage_discounts'][0] ?? [];

    //         $promotion = PromotionHeader::create([
    //             'uuid'               => Str::uuid(),
    //             'osa_code'           => $osaCode,
    //             'promotion_name'     => $data['promotion_name'],
    //             'promotion_type'     => $data['promotion_type'] ?? null,
    //             'bundle_combination' => $data['bundle_combination'] ?? null,
    //             'from_date'          => $data['from_date'],
    //             'to_date'            => $data['to_date'],
    //             'status'             => $data['status'],

    //             'offer_item_id' => $offer['item_id'],
    //             'offer_uom'     => $offer['uom'] ?? null,

    //             // ARRAY → CSV
    //             'sales_team_type' => isset($data['sales_team_type'])
    //                 ? implode(',', $data['sales_team_type'])
    //                 : null,

    //             'project_list' => !empty($data['project_list'])
    //                 ? implode(',', $data['project_list'])
    //                 : null,

    //             'uom' => $data['uom'] ?? null,

    //             'items'         => implode(',', $data['items'] ?? []),
    //             'item_category' => implode(',', $data['item_category'] ?? []),
    //             'location'      => implode(',', $data['location'] ?? []),
    //             'customer'      => implode(',', $data['customer'] ?? []),

    //             'key_location' => isset($data['key']['Location'])
    //                 ? implode(',', $data['key']['Location'])
    //                 : null,

    //             'key_customer' => isset($data['key']['Customer'])
    //                 ? implode(',', $data['key']['Customer'])
    //                 : null,

    //             // PERCENTAGE
    //             'percentage_item_id'       => $percentage['percentage_item_id'] ?? null,
    //             'percentage_item_category' => $percentage['percentage_item_category'] ?? null,
    //             'percentage'               => $percentage['percentage'] ?? null,

    //             'created_user' => auth()->id(),
    //         ]);

    //         foreach ($data['promotion_details'] as $index => $detail) {
    //             PromotionDetail::create([
    //                 'header_id'    => $promotion->id,
    //                 'from_qty'     => (int) $detail['from_qty'],
    //                 'to_qty'       => (int) $detail['to_qty'],
    //                 'free_qty'     => (int) $detail['free_qty'],
    //                 'created_user' => auth()->id(),
    //             ]);
    //         }

    //         DB::commit();

    //         return $promotion->load('promotionDetails');
    //     } catch (\Throwable $e) {

    //         DB::rollBack();

    //         \Log::error('[PROMOTION CREATE FAILED]', [
    //             'error' => $e->getMessage(),
    //             'data'  => $data,
    //         ]);

    //         throw new \Exception(
    //             'Failed to create promotion. Please try again later.',
    //             500,
    //             $e
    //         );
    //     }
    // }

    public function create(array $data): PromotionHeader
    {
        DB::beginTransaction();

        try {

            $osaCode = $data['osa_code'] ?? $this->generateOsaCode();

            /*
        |--------------------------------------------------------------------------
        | 1. PROMOTION HEADER
        |--------------------------------------------------------------------------
        */
            $promotion = PromotionHeader::create([
                'uuid'               => Str::uuid(),
                'osa_code'           => $osaCode,
                'promotion_name'     => $data['promotion_name'],
                'promotion_type'     => $data['promotion_type'],
                'bundle_combination' => $data['bundle_combination'],
                'from_date'          => $data['from_date'],
                'to_date'            => $data['to_date'],
                'status'             => $data['status'],

                'sales_team_type' => !empty($data['sales_team_type'])
                    ? implode(',', $data['sales_team_type'])
                    : null,

                'project_list' => !empty($data['project_list'])
                    ? implode(',', $data['project_list'])
                    : null,

                'uom' => $data['uom'] ?? null,

                'items'         => implode(',', $data['items'] ?? []),
                'item_category' => implode(',', $data['item_category'] ?? []),
                'location'      => implode(',', $data['location'] ?? []),
                'customer'      => implode(',', $data['customer'] ?? []),

                'key_location' => $data['key']['Location'][0] ?? null,
                'key_customer' => $data['key']['Customer'][0] ?? null,
                'key_item' => $data['key']['Item'][0] ?? null,

                'created_user' => auth()->id(),
            ]);

            $details     = $data['promotion_details'] ?? [];
            $offers      = $data['offer_items'] ?? [];
            $percentages = $data['percentage_discounts'] ?? [];

            /*
        |--------------------------------------------------------------------------
        | 2. PROMOTION DETAILS (QTY SLABS)
        |--------------------------------------------------------------------------
        */
            foreach ($details as $detail) {

                $promotionDetail = PromotionDetail::create([
                    'header_id'   => $promotion->id,
                    'from_qty'    => (int) $detail['from_qty'],
                    'to_qty'      => (int) $detail['to_qty'],
                    'free_qty'    => (int) $detail['free_qty'],
                    'created_user' => auth()->id(),
                ]);

                /*
            |--------------------------------------------------------------------------
            | 3. PROMOTIONAL ITEMS DETAIL
            |--------------------------------------------------------------------------
            */
                foreach ($offers as $offer) {

                    if (!empty($percentages)) {
                        foreach ($percentages as $percentage) {

                            PromotionOfferItem::create([
                                'promotion_header_id' => $promotion->id,
                                'offer_item_id'       => $offer['item_id'],
                                'uom'                 => $offer['uom'],

                                'percentage_item_id'     => $percentage['percentage_item_id'],
                                'percentage_category_id' => $percentage['percentage_item_category'],
                                'percentage_uom'         => $offer['uom'],

                                'created_user' => auth()->id(),
                            ]);
                        }
                    } else {

                        PromotionOfferItem::create([
                            'promotion_header_id' => $promotion->id,
                            'offer_item_id'       => $offer['item_id'],
                            'uom'                 => $offer['uom'],
                            'created_user'        => auth()->id(),
                        ]);
                    }
                }
            }

            /*
        |--------------------------------------------------------------------------
        | 4. PROMOTIONAL SLABS (ONLY FOR SLAB TYPE)
        |--------------------------------------------------------------------------
        */
            if ($data['bundle_combination'] === 'slab') {

                foreach ($percentages as $percentage) {

                    PromotionalSlab::create([
                        'promotion_header_id' => $promotion->id,
                        'category'            => $percentage['percentage_item_category'],
                        'item_id'             => $percentage['percentage_item_id'],
                        'percentage'       => $percentage['percentage'],
                        'created_user'        => auth()->id(),
                    ]);
                }
            }

            DB::commit();

            return $promotion->load([
                'promotionDetails',
                'offerItems',
                'promotionalSlabs'
            ]);
        } catch (\Throwable $e) {
            dd($e);
            DB::rollBack();

            \Log::error('[PROMOTION CREATE FAILED]', [
                'error' => $e->getMessage(),
                'payload' => $data,
            ]);

            throw new \Exception(
                'Failed to create promotion. Please try again later.',
                500,
                $e
            );
        }
    }




    /**
     * UPDATE
     */
    // public function update(string $uuid, array $data): PromotionHeader
    // {
    //     DB::beginTransaction();

    //     try {
    //         $promotion = PromotionHeader::where('uuid', $uuid)->firstOrFail();

    //         $offer      = $data['offer_items'][0] ?? [];
    //         // $percentage = $data['percentage_discounts'][0] ?? [];

    //         $promotion->update([
    //             'promotion_name'     => $data['promotion_name'] ?? $promotion->promotion_name,
    //             'promotion_type'     => $data['promotion_type'] ?? $promotion->promotion_type,
    //             'bundle_combination' => $data['bundle_combination'] ?? $promotion->bundle_combination,
    //             'from_date'          => $data['from_date'] ?? $promotion->from_date,
    //             'to_date'            => $data['to_date'] ?? $promotion->to_date,
    //             'status'             => $data['status'] ?? $promotion->status,

    //             // OFFER
    //             'offer_item_id' => $offer['item_id'] ?? $promotion->offer_item_id,
    //             'offer_uom'     => $offer['uom'] ?? $promotion->offer_uom,

    //             // ARRAY → CSV
    //             'sales_team_type' => isset($data['sales_team_type'])
    //                 ? implode(',', $data['sales_team_type'])
    //                 : $promotion->sales_team_type,

    //             'project_list' => isset($data['project_list'])
    //                 ? implode(',', $data['project_list'])
    //                 : $promotion->project_list,

    //             'uom' => $data['uom'] ?? $promotion->uom,

    //             'items'         => isset($data['items']) ? implode(',', $data['items']) : $promotion->items,
    //             'item_category' => isset($data['item_category']) ? implode(',', $data['item_category']) : $promotion->item_category,
    //             'location'      => isset($data['location']) ? implode(',', $data['location']) : $promotion->location,
    //             'customer'      => isset($data['customer']) ? implode(',', $data['customer']) : $promotion->customer,

    //             'key_location' => isset($data['key']['Location'])
    //                 ? implode(',', $data['key']['Location'])
    //                 : $promotion->key_location,

    //             'key_customer' => isset($data['key']['Customer'])
    //                 ? implode(',', $data['key']['Customer'])
    //                 : $promotion->key_customer,

    //             // PERCENTAGE
    //             'percentage_item_id'       => $percentage['percentage_item_id'] ?? $promotion->percentage_item_id,
    //             'percentage_item_category' => $percentage['percentage_item_category'] ?? $promotion->percentage_item_category,
    //             'percentage'               => $percentage['percentage'] ?? $promotion->percentage,

    //             'updated_user' => auth()->id(),
    //         ]);

    //         // DETAILS — replace only if sent
    //         if (array_key_exists('promotion_details', $data)) {
    //             $promotion->promotionDetails()->delete();

    //             foreach ($data['promotion_details'] ?? [] as $detail) {
    //                 PromotionDetail::create([
    //                     'header_id'    => $promotion->id,
    //                     'from_qty'     => (int) $detail['from_qty'],
    //                     'to_qty'       => (int) $detail['to_qty'],
    //                     'free_qty'     => (int) $detail['free_qty'],
    //                     'created_user' => auth()->id(),
    //                 ]);
    //             }
    //         }


    //         DB::commit();

    //         return $promotion->load('promotionDetails');
    //     } catch (\Throwable $e) {

    //         DB::rollBack();

    //         \Log::error('[PROMOTION UPDATE FAILED]', [
    //             'uuid'  => $uuid,
    //             'error' => $e->getMessage(),
    //             'data'  => $data,
    //         ]);

    //         throw new \Exception(
    //             'Failed to update promotion. Please try again later.',
    //             500,
    //             $e
    //         );
    //     }
    // }

    public function update(string $uuid, array $data): PromotionHeader
    {
        DB::beginTransaction();

        try {

            $promotion = PromotionHeader::where('uuid', $uuid)->firstOrFail();

            /*
        |--------------------------------------------------------------------------
        | 1. UPDATE PROMOTION HEADER
        |--------------------------------------------------------------------------
        */
            $promotion->update([
                'promotion_name'     => $data['promotion_name']     ?? $promotion->promotion_name,
                'promotion_type'     => $data['promotion_type']     ?? $promotion->promotion_type,
                'bundle_combination' => $data['bundle_combination'] ?? $promotion->bundle_combination,
                'from_date'          => $data['from_date']          ?? $promotion->from_date,
                'to_date'            => $data['to_date']            ?? $promotion->to_date,
                'status'             => $data['status']             ?? $promotion->status,

                'sales_team_type' => array_key_exists('sales_team_type', $data)
                    ? implode(',', $data['sales_team_type'])
                    : $promotion->sales_team_type,

                'project_list' => array_key_exists('project_list', $data)
                    ? implode(',', $data['project_list'])
                    : $promotion->project_list,

                'uom' => $data['uom'] ?? $promotion->uom,

                'items' => array_key_exists('items', $data)
                    ? implode(',', $data['items'])
                    : $promotion->items,

                'item_category' => array_key_exists('item_category', $data)
                    ? implode(',', $data['item_category'])
                    : $promotion->item_category,

                'location' => array_key_exists('location', $data)
                    ? implode(',', $data['location'])
                    : $promotion->location,

                'customer' => array_key_exists('customer', $data)
                    ? implode(',', $data['customer'])
                    : $promotion->customer,

                'key_location' => $data['key']['Location'][0] ?? $promotion->key_location,
                'key_customer' => $data['key']['Customer'][0] ?? $promotion->key_customer,

                'key_item' => $data['key']['Item'][0] ?? $promotion->key_item,

                'updated_user' => auth()->id(),
            ]);

            /*
        |--------------------------------------------------------------------------
        | 2. SOFT DELETE OLD CHILD DATA (REPLACE MODE)
        |--------------------------------------------------------------------------
        */
            PromotionDetail::where('header_id', $promotion->id)
                ->update([
                    'deleted_at'   => now(),
                    'deleted_user' => auth()->id(),
                ]);

            PromotionOfferItem::where('promotion_header_id', $promotion->id)
                ->update([
                    'deleted_at'   => now(),
                    'deleted_user' => auth()->id(),
                ]);

            PromotionalSlab::where('promotion_header_id', $promotion->id)
                ->update([
                    'deleted_at'   => now(),
                    'deleted_user' => auth()->id(),
                ]);

            /*
        |--------------------------------------------------------------------------
        | 3. INSERT NEW DETAILS + OFFER ITEMS
        |--------------------------------------------------------------------------
        */
            $details     = $data['promotion_details'] ?? [];
            $offers      = $data['offer_items'] ?? [];
            $percentages = $data['percentage_discounts'] ?? [];

            foreach ($details as $detail) {

                PromotionDetail::create([
                    'header_id'    => $promotion->id,
                    'from_qty'     => (int) $detail['from_qty'],
                    'to_qty'       => (int) $detail['to_qty'],
                    'free_qty'     => (int) $detail['free_qty'],
                    'created_user' => auth()->id(),
                ]);
            }

            foreach ($offers as $offer) {

                if (!empty($percentages)) {
                    foreach ($percentages as $percentage) {

                        PromotionOfferItem::create([
                            'promotion_header_id'   => $promotion->id,
                            'offer_item_id'         => $offer['item_id'],
                            'uom'                   => $offer['uom'],
                            'percentage_item_id'     => $percentage['percentage_item_id'],
                            'percentage_category_id' => $percentage['percentage_item_category'],
                            'percentage_uom'         => $offer['uom'],
                            'created_user'           => auth()->id(),
                        ]);
                    }
                } else {

                    PromotionOfferItem::create([
                        'promotion_header_id' => $promotion->id,
                        'offer_item_id'       => $offer['item_id'],
                        'uom'                 => $offer['uom'],
                        'created_user'        => auth()->id(),
                    ]);
                }
            }

            /*
        |--------------------------------------------------------------------------
        | 4. INSERT PROMOTIONAL SLABS (ONLY IF SLAB)
        |--------------------------------------------------------------------------
        */
            if ($promotion->bundle_combination === 'slab') {

                foreach ($percentages as $percentage) {

                    PromotionalSlab::create([
                        'promotion_header_id' => $promotion->id,
                        'category'            => $percentage['percentage_item_category'],
                        'item_id'             => $percentage['percentage_item_id'],
                        'percentage'          => $percentage['percentage'],
                        'created_user'        => auth()->id(),
                    ]);
                }
            }

            DB::commit();

            return $promotion->load([
                'promotionDetails',
                'offerItems',
                'promotionalSlabs',
            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            \Log::error('[PROMOTION UPDATE FAILED]', [
                'uuid'    => $uuid,
                'error'   => $e->getMessage(),
                'payload' => $data,
            ]);

            throw new \Exception(
                'Failed to update promotion. Please try again later.',
                500,
                $e
            );
        }
    }



    /**
     * LIST
  
     * LIST PROMOTIONS
     */
    public function list(array $filters)
    {
        try {
            $query = PromotionHeader::with('promotionDetails', 'offerItems', 'promotionalSlabs')
                ->orderByDesc('id');

            if (!empty($filters['id'])) {
                $query->where('id', $filters['id']);
            }

            if (!empty($filters['promtion_name'])) {
                $query->where('promotion_name', 'ILIKE', '%' . $filters['promtion_name'] . '%');
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            $limit = $filters['limit'] ?? 50;

            return $query->paginate($limit);
        } catch (\Throwable $e) {

            \Log::error('[PROMOTION LIST FAILED]', [
                'filters' => $filters,
                'error'   => $e->getMessage(),
            ]);

            throw new \Exception(
                'Failed to fetch promotion list. Please try again later.',
                500,
                $e
            );
        }
    }


    /**
     * SHOW
     */
    public function show(string $uuid): ?PromotionHeader
    {
        return PromotionHeader::with('promotionDetails', 'offerItems', 'promotionalSlabs')
            ->where('uuid', $uuid)
            ->first();
    }


    /**
     * DELETE (Soft Delete)
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {

            $promotion = PromotionHeader::findOrFail($id);

            $promotion->update([
                'deleted_user' => auth()->id(),
            ]);

            return $promotion->delete();
        });
    }

    /**
     * OSA CODE GENERATOR
     */
    private function generateOsaCode(): string
    {
        $last = PromotionHeader::withTrashed()
            ->orderByDesc('id')
            ->first();

        $next = $last
            ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1
            : 1;

        return 'PR' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }



    public function getByWarehouseId(int $warehouseId)
    {
        /**
         * 🔹 STEP 1: Fetch Warehouse
         */
        $warehouse = Warehouse::query()
            ->select('id', 'area_id', 'region_id', 'company')
            ->where('id', $warehouseId)
            ->first();

        if (! $warehouse) {
            return collect();
        }

        $today = Carbon::today()->toDateString();

        /**
         * 🔹 STEP 2: Promotion Query
         */
        return PromotionHeader::query()
            ->whereNull('deleted_at')
            ->where('status', 1)

            // 🔹 Date validity
            ->whereDate('from_date', '<=', $today)
            ->whereDate('to_date', '>=', $today)

            // 🔹 Location match
            ->where(function ($q) use ($warehouse) {

                // Warehouse
                $q->orWhere(function ($w) use ($warehouse) {
                    $w->where('key_location', 'Warehouse')
                        ->whereRaw(
                            "? = ANY (string_to_array(location, ','))",
                            [(string) $warehouse->id]
                        );
                });

                // Area
                if ($warehouse->area_id) {
                    $q->orWhere(function ($a) use ($warehouse) {
                        $a->where('key_location', 'Area')
                            ->whereRaw(
                                "? = ANY (string_to_array(location, ','))",
                                [(string) $warehouse->area_id]
                            );
                    });
                }

                // Region
                if ($warehouse->region_id) {
                    $q->orWhere(function ($r) use ($warehouse) {
                        $r->where('key_location', 'Region')
                            ->whereRaw(
                                "? = ANY (string_to_array(location, ','))",
                                [(string) $warehouse->region_id]
                            );
                    });
                }

                // Company
                if ($warehouse->company) {
                    $q->orWhere(function ($c) use ($warehouse) {
                        $c->where('key_location', 'Company')
                            ->whereRaw(
                                "? = ANY (string_to_array(location, ','))",
                                [(string) $warehouse->company]
                            );
                    });
                }
            })

            /**
             * 🔹 Eager Load Relations
             */
            ->with([
                'promotionDetails' => fn($q) => $q->whereNull('deleted_at'),

                'offerItems' => fn($q) => $q
                    ->whereNull('deleted_at')
                    ->with(
                        'offerItem:id,name,erp_code',
                        'percentageItem:id,name,erp_code'
                    ),

                'promotionalSlabs' => fn($q) => $q->whereNull('deleted_at'),
            ])

            ->orderByDesc('id')
            ->get()
            ->unique('id')
            ->values();
    }
    // public function getByWarehouseId(int $warehouseId)
    // {
    //     /**
    //      * 🔹 STEP 1: Fetch Warehouse (Area / Region / Company)
    //      */
    //     $warehouse = Warehouse::query()
    //         ->select('id', 'area_id', 'region_id', 'company') // company column exists in your table
    //         ->where('id', $warehouseId)
    //         ->first();

    //     if (! $warehouse) {
    //         return collect();
    //     }

    //     $today = Carbon::today()->toDateString();

    //     /**
    //      * 🔹 STEP 2: Promotion Query (ONLY CURRENT PROMOTIONS)
    //      */
    //     return PromotionHeader::query()
    //         ->whereNull('deleted_at')
    //         ->where('status', 1)

    //         // 🔹 Date validity
    //         ->whereDate('from_date', '<=', $today)
    //         ->whereDate('to_date', '>=', $today)

    //         // 🔹 Location + key_location STRICT MATCH
    //         ->where(function ($q) use ($warehouse) {

    //             // 🔹 Warehouse-level promotions
    //             $q->orWhere(function ($w) use ($warehouse) {
    //                 $w->where('key_location', 'Warehouse')
    //                     ->whereRaw(
    //                         "? = ANY (string_to_array(location, ','))",
    //                         [(string) $warehouse->id]
    //                     );
    //             });

    //             // 🔹 Area-level promotions
    //             if ($warehouse->area_id) {
    //                 $q->orWhere(function ($a) use ($warehouse) {
    //                     $a->where('key_location', 'Area')
    //                         ->whereRaw(
    //                             "? = ANY (string_to_array(location, ','))",
    //                             [(string) $warehouse->area_id]
    //                         );
    //                 });
    //             }

    //             // 🔹 Region-level promotions
    //             if ($warehouse->region_id) {
    //                 $q->orWhere(function ($r) use ($warehouse) {
    //                     $r->where('key_location', 'Region')
    //                         ->whereRaw(
    //                             "? = ANY (string_to_array(location, ','))",
    //                             [(string) $warehouse->region_id]
    //                         );
    //                 });
    //             }

    //             // 🔹 Company-level promotions
    //             if ($warehouse->company) {
    //                 $q->orWhere(function ($c) use ($warehouse) {
    //                     $c->where('key_location', 'Company')
    //                         ->whereRaw(
    //                             "? = ANY (string_to_array(location, ','))",
    //                             [(string) $warehouse->company]
    //                         );
    //                 });
    //             }
    //         })

    //         ->with([
    //             'promotionDetails' => fn($q) => $q->whereNull('deleted_at'),
    //             'offerItems'       => fn($q) => $q->whereNull('deleted_at'),
    //             'promotionalSlabs' => fn($q) => $q->whereNull('deleted_at'),
    //         ])

    //         ->orderByDesc('id')
    //         ->get()
    //         ->unique('id')
    //         ->values();
    // }


    // public function fetchApplicablePromotions(array $payload)
    // {
    //     $customerId = $payload['customer_id'] ?? null;
    //     $items      = $payload['items'] ?? [];
    //     $perPage    = $payload['per_page'] ?? null;
    //     $page       = $payload['page'] ?? null;

    //     /**
    //      * 🔹 STEP 1: Fetch Agent Customer
    //      */
    //     $agentCustomer = AgentCustomer::query()
    //         ->where('id', $customerId)
    //         ->where('status', 1)
    //         ->where('enable_promotion', 1)
    //         ->whereNull('deleted_at')
    //         ->first();

    //     // 🔹 Always return paginator
    //     if (! $agentCustomer) {
    //         return new LengthAwarePaginator([], 0, $perPage, $page, [
    //             'path' => request()->url()
    //         ]);
    //     }

    //     /**
    //      * 🔹 BASE QUERY (COMMON CONDITIONS)
    //      */
    //     $baseQuery = PromotionHeader::query()
    //         ->whereNull('deleted_at')
    //         ->where('status', 1)
    //         ->whereDate('from_date', '<=', now())
    //         ->whereDate('to_date', '>=', now());

    //     /**
    //      * 🔹 Quantity slab validation (ANY item matches)
    //      */
    //     if (! empty($items)) {
    //         $baseQuery->whereHas('promotionDetails', function ($q) use ($items) {
    //             $q->whereNull('deleted_at')
    //                 ->where(function ($qq) use ($items) {
    //                     foreach ($items as $item) {
    //                         if (! isset($item['item_qty'])) {
    //                             continue;
    //                         }

    //                         $qty = $item['item_qty'];

    //                         $qq->orWhere(function ($qqq) use ($qty) {
    //                             $qqq->where('from_qty', '<=', $qty)
    //                                 ->where('to_qty', '>=', $qty);
    //                         });
    //                     }
    //                 });
    //         })
    //             ->with(['promotionDetails' => function ($q) use ($items) {
    //                 $q->whereNull('deleted_at')
    //                     ->where(function ($qq) use ($items) {
    //                         foreach ($items as $item) {
    //                             if (! isset($item['item_qty'])) {
    //                                 continue;
    //                             }

    //                             $qty = $item['item_qty'];

    //                             $qq->orWhere(function ($qqq) use ($qty) {
    //                                 $qqq->where('from_qty', '<=', $qty)
    //                                     ->where('to_qty', '>=', $qty);
    //                             });
    //                         }
    //                     });
    //             }]);
    //     }

    //     /**
    //      * 🔹 STEP 2: CUSTOMER-BASED PROMOTIONS
    //      */
    //     $customerPromoQuery = (clone $baseQuery)
    //         ->where('key_customer', 'Customer')
    //         ->whereRaw(
    //             "(',' || customer || ',') LIKE ?",
    //             ['%,' . $customerId . ',%']
    //         );

    //     if ((clone $customerPromoQuery)->exists()) {
    //         return $customerPromoQuery
    //             ->select([
    //                 'id',
    //                 'uuid',
    //                 'osa_code',
    //                 'promotion_name',
    //                 'promotion_type',
    //                 'bundle_combination',
    //             ])
    //             ->paginate($perPage);
    //     }

    //     /**
    //      * 🔹 STEP 3: WAREHOUSE-BASED FALLBACK
    //      */
    //     $warehouseId = $agentCustomer->warehouse;

    //     if (! $warehouseId) {
    //         return new LengthAwarePaginator([], 0, $perPage, $page, [
    //             'path' => request()->url()
    //         ]);
    //     }

    //     return (clone $baseQuery)
    //         ->where('key_location', 'Warehouse')
    //         ->whereRaw(
    //             "(',' || location || ',') LIKE ?",
    //             ['%,' . $warehouseId . ',%']
    //         )
    //         ->select([
    //             'id',
    //             'uuid',
    //             'osa_code',
    //             'promotion_name',
    //             'promotion_type',
    //             'bundle_combination',
    //         ])
    //         ->paginate($perPage);
    // }


    public function globalSearch(int $perPage = 10, ?string $searchTerm = null)
    {
        $query = PromotionHeader::with([
            'promotionDetails',
            'offerItems',
            'promotionalSlabs',
        ])
            ->whereNull('deleted_at');

        if ($searchTerm) {

            $query->where(function ($q) use ($searchTerm) {

                $q->where('osa_code', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('promotion_name', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('promotion_type', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('bundle_combination', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('key_item', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('key_location', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('key_customer', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('status', 'ILIKE', "%{$searchTerm}%");

                $q->orWhereRaw('id::text ILIKE ?', ["%{$searchTerm}%"])
                    ->orWhereRaw('status::text ILIKE ?', ["%{$searchTerm}%"])
                    ->orWhereRaw('uom::text ILIKE ?', ["%{$searchTerm}%"])
                    ->orWhereRaw('from_date::text ILIKE ?', ["%{$searchTerm}%"])
                    ->orWhereRaw('to_date::text ILIKE ?', ["%{$searchTerm}%"]);

                $q->orWhere('items', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('item_category', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('location', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('customer', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('sales_team_type', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('project_list', 'ILIKE', "%{$searchTerm}%");

                $q->orWhereHas('offerItems', function ($iq) use ($searchTerm) {
                    $iq->whereNull('deleted_at')
                        ->where(function ($sub) use ($searchTerm) {
                            $sub->where('offer_item_id', 'ILIKE', "%{$searchTerm}%")
                                ->orWhere('uom', 'ILIKE', "%{$searchTerm}%")
                                ->orWhere('percentage_item_id', 'ILIKE', "%{$searchTerm}%")
                                ->orWhere('percentage_category_id', 'ILIKE', "%{$searchTerm}%")
                                ->orWhere('percentage_uom', 'ILIKE', "%{$searchTerm}%");
                        });
                });

                $q->orWhereHas('promotionalSlabs', function ($sq) use ($searchTerm) {
                    $sq->whereNull('deleted_at')
                        ->where(function ($sub) use ($searchTerm) {
                            $sub->where('category', 'ILIKE', "%{$searchTerm}%")
                                ->orWhere('item_id', 'ILIKE', "%{$searchTerm}%")
                                ->orWhereRaw('percentage::text ILIKE ?', ["%{$searchTerm}%"]);
                        });
                });
            });
        }

        return $query
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function fetchApplicablePromotions(array $payload)
    {
        $customerId = $payload['customer_id'] ?? null;
        $items      = $payload['items'] ?? [];
        $perPage    = $payload['per_page'] ?? 10;
        $page       = $payload['page'] ?? 1;

        /**
         * 🔹 STEP 1: Fetch Agent Customer
         */
        $agentCustomer = AgentCustomer::query()
            ->where('id', $customerId)
            ->where('status', 1)
            ->where('enable_promotion', 1)
            ->whereNull('deleted_at')
            ->first();
        if (! $agentCustomer) {
            return new LengthAwarePaginator([], 0, $perPage, $page, [
                'path' => request()->url()
            ]);
        }

        /**
         * 🔹 BASE QUERY (COMMON CONDITIONS)
         */
        $baseQuery = PromotionHeader::query()
            ->whereNull('deleted_at')
            ->where('status', 1)
            ->whereDate('from_date', '<=', now())
            ->whereDate('to_date', '>=', now());

// dd($baseQuery);
        /**
         * 🔹 Quantity slab validation (ANY item matches)
         */
        if (! empty($items)) {
            $baseQuery
                ->whereHas('promotionDetails', function ($q) use ($items) {
                    $q->whereNull('deleted_at')
                        ->where(function ($qq) use ($items) {
                            foreach ($items as $item) {
                                if (! isset($item['item_qty'])) {
                                    continue;
                                }

                                $qty = $item['item_qty'];

                                $qq->orWhere(function ($qqq) use ($qty) {
                                    $qqq->where('from_qty', '<=', $qty)
                                        ->where('to_qty', '>=', $qty);
                                });
                            }
                        });
                })
                ->with(['promotionDetails' => function ($q) use ($items) {
                    $q->whereNull('deleted_at')
                        ->where(function ($qq) use ($items) {
                            foreach ($items as $item) {
                                if (! isset($item['item_qty'])) {
                                    continue;
                                }

                                $qty = $item['item_qty'];

                                $qq->orWhere(function ($qqq) use ($qty) {
                                    $qqq->where('from_qty', '<=', $qty)
                                        ->where('to_qty', '>=', $qty);
                                });
                            }
                        });
                }]);
        }

        /**
         * 🔹 STEP 2: CUSTOMER-BASED PROMOTIONS
         */
        $customerPromoQuery = (clone $baseQuery)
            ->where('key_customer', 'Customer')
            ->whereRaw(
                "(',' || customer || ',') LIKE ?",
                ['%,' . $customerId . ',%']
            );
        // dd($customerPromoQuery);
        if ((clone $customerPromoQuery)->exists()) {
            return $customerPromoQuery
                ->select([
                    'id',
                    'uuid',
                    'osa_code',
                    'promotion_name',
                    'promotion_type',
                    'bundle_combination',
                ])
                ->paginate($perPage);
        }

        /**
         * 🔹 STEP 3: LOCATION HIERARCHY
         * Warehouse → Area → Region → Company
         */
        $warehouseId = $agentCustomer->warehouse;

        if (! $warehouseId) {
            return new LengthAwarePaginator([], 0, $perPage, $page, [
                'path' => request()->url()
            ]);
        }

        $warehouse = Warehouse::query()
            ->select('id', 'area_id')
            ->find($warehouseId);

        $areaId = $warehouse?->area_id;

        $area = $areaId
            ? Area::query()->select('id', 'region_id')->find($areaId)
            : null;

        $regionId = $area?->region_id;

        $region = $regionId
            ? Region::query()->select('id', 'company_id')->find($regionId)
            : null;

        $companyId = $region?->company_id;

        /**
         * 🔹 STEP 4: LOCATION-BASED FALLBACK SEQUENCE
         */

        // Warehouse
        $warehousePromos = $this->fetchByLocation(
            $baseQuery,
            'Warehouse',
            $warehouseId,
            $perPage
        );
        // dd($warehousePromos);
        if ($warehousePromos->total() > 0) {
            return $warehousePromos;
        }

        // Area
        if ($areaId) {
            $areaPromos = $this->fetchByLocation(
                $baseQuery,
                'Area',
                $areaId,
                $perPage
            );

            if ($areaPromos->total() > 0) {
                return $areaPromos;
            }
        }

        // Region
        if ($regionId) {
            $regionPromos = $this->fetchByLocation(
                $baseQuery,
                'Region',
                $regionId,
                $perPage
            );

            if ($regionPromos->total() > 0) {
                return $regionPromos;
            }
        }

        // Company
        if ($companyId) {
            return $this->fetchByLocation(
                $baseQuery,
                'Company',
                $companyId,
                $perPage
            );
        }

        /**
         * 🔹 FINAL FALLBACK
         */
        return new LengthAwarePaginator([], 0, $perPage, $page, [
            'path' => request()->url()
        ]);
    }

    private function fetchByLocation($baseQuery, string $key, int $locationId, int $perPage)
    {
        return (clone $baseQuery)
            ->where('key_location', $key)
            ->whereRaw(
                "(',' || location || ',') LIKE ?",
                ['%,' . $locationId . ',%']
            )
            ->select([
                'id',
                'uuid',
                'osa_code',
                'promotion_name',
                'promotion_type',
                'bundle_combination',
            ])
            ->paginate($perPage);
    }




    /* ---------------------------------------------
     | ENTRY POINT
     |---------------------------------------------*/
    public function getApplicablePromotions(array $payload): array
    {
        $items       = $payload['items'];
        $customerId  = $payload['customer_id'] ?? null;
        $warehouseId = $payload['warehouse_id'];

        $query     = $this->basePromotionQuery();
        $locations = $this->resolveLocationHierarchy($warehouseId);

        /* 1️⃣ CUSTOMER FIRST */
        /* 1️⃣ CUSTOMER FIRST */
        if ($customerId) {
            $customerPromos = $this->customerPromotions($query, $customerId);

            // 🔥 NEW LINE (Item relevance filter)
            $customerPromos = $this->filterPromotionsByItems($customerPromos, $items);

            if ($customerPromos->isNotEmpty()) {
                return $this->buildResponse(
                    $this->applyPriorityRules($customerPromos, $items),
                    $items
                );
            }
        }

        /* 2️⃣ LOCATION FALLBACK */
        $locationPromos = $this->locationPromotions($query, $locations);

        // 🔥 NEW LINE (Item relevance filter)
        $locationPromos = $this->filterPromotionsByItems($locationPromos, $items);

        return $this->buildResponse(
            $this->applyPriorityRules($locationPromos, $items),
            $items
        );
    }

    private function filterPromotionsByItems(Collection $promos, array $items): Collection
    {
        $orderItemIds = collect($items)
            ->pluck('item_id')
            ->map(fn($id) => (int) $id);

        return $promos->filter(function ($promo) use ($orderItemIds) {

            if (empty($promo->items)) return false;

            $promoItemIds = collect(explode(',', $promo->items))
                ->map(fn($id) => (int) trim($id));

            // At least one payload item must exist in promo
            return $promoItemIds->intersect($orderItemIds)->isNotEmpty();
        })->values();
    }


    /* ================= BASE QUERY ================= */

    private function basePromotionQuery()
    {
        return PromotionHeader::query()
            ->where('status', 1)
            ->whereDate('from_date', '<=', now())
            ->whereDate('to_date', '>=', now())
            ->with(['promotionDetails', 'freeItems.item', 'freeItems.uomMaster', 'slabs']);
    }

    /* ================= LOCATION ================= */

    private function resolveLocationHierarchy($warehouseId): array
    {
        $warehouse = Warehouse::with('area.region')->find($warehouseId);

        return [
            'Warehouse' => $warehouse?->id,
            'Area'      => $warehouse?->area?->id,
            'Region'    => $warehouse?->area?->region?->id,
            'Company'   => $warehouse?->area?->region?->company_id,
        ];
    }

    /* ================= CUSTOMER ================= */

    private function customerPromotions($query, $customerId)
    {
        return (clone $query)
            ->where('key_customer', 'Customer')
            ->whereRaw("? = ANY(string_to_array(customer, ','))", [$customerId])
            ->get();
    }

    /* ================= LOCATION ================= */

    private function locationPromotions($query, $locations)
    {
        foreach ($locations as $key => $id) {
            if (!$id) continue;

            $promo = (clone $query)
                ->where('key_location', $key)
                ->whereRaw("? = ANY(string_to_array(location, ','))", [$id])
                ->get();

            if ($promo->isNotEmpty()) return $promo;
        }
        return collect();
    }

    /* ================= PRIORITY ================= */

    private function applyPriorityRules(Collection $promos, array $items)
    {
        if ($slab = $this->applySlab($promos, $items)) return collect([$slab]);
        if ($range = $this->applyRange($promos, $items)) return collect([$range]);
        return $this->applyNormal($promos, $items);
    }

    /* ================= SLAB ================= */

    private function applySlab($promos, $items)
    {
        $totalQty = collect($items)->sum('item_qty');

        foreach ($promos as $promo) {
            if ($promo->bundle_combination !== 'slab') continue;

            foreach ($promo->promotionDetails as $detail) {
                if ($totalQty >= $detail->from_qty && $totalQty <= $detail->to_qty) {
                    return $promo;
                }
            }
        }
        return null;
    }

    /* ================= RANGE ================= */

    private function applyRange($promos, $items)
    {
        foreach ($items as $item) {
            foreach ($promos as $promo) {
                if ($promo->bundle_combination !== 'range') {
                    continue;
                }

                foreach ($promo->promotionDetails as $detail) {
                    if (
                        $item['item_qty'] >= $detail->from_qty &&
                        $item['item_qty'] <= $detail->to_qty
                    ) {
                        $promo->calculated_free_qty = (int) $detail->free_qty;
                        return $promo;
                    }
                }
            }
        }
        return null;
    }

    /* ================= NORMAL (GROUP LOGIC) ================= */
    private function applyNormal(Collection $promos, array $items)
    {
        // Normalize order items
        $orderItems = collect($items)->map(fn($i) => [
            'item_id' => (int) $i['item_id'],
            'qty'     => (int) $i['item_qty'],
            'uom'     => (int) $i['item_uom_id'],
        ]);

        /* ================= GROUP PROMOTIONS ================= */
        $groupPromos = $promos->filter(function ($promo) use ($orderItems) {

            if (empty($promo->items)) return false;

            $promoItemIds = collect(explode(',', $promo->items))
                ->map(fn($id) => (int) trim($id))
                ->values();
            // Must be group
            if ($promoItemIds->count() <= 1) return false;
            // All promo items must exist in order
            return $promoItemIds->diff($orderItems->pluck('item_id'))->isEmpty();
        });
        if ($groupPromos->isNotEmpty()) {
            return $groupPromos->map(function ($promo) use ($orderItems) {

                $promoItemIds = collect(explode(',', $promo->items))
                    ->map(fn($id) => (int) trim($id));

                // 🔥 Sum qty ONLY for promo items + UOM match
                $matchedQty = $orderItems
                    ->filter(fn($i) => $promoItemIds->contains($i['item_id']))
                    ->sum('qty');

                $detail = $promo->promotionDetails->first();

                $promo->calculated_free_qty = ($detail && $detail->from_qty > 0)
                    ? intdiv($matchedQty, $detail->from_qty) * $detail->free_qty
                    : 0;

                return $promo;
            })->values();
        }

        /* ================= SINGLE ITEM PROMOTIONS ================= */
        return $promos->filter(function ($promo) use ($orderItems) {

            if (empty($promo->items)) return false;

            $promoItemIds = collect(explode(',', $promo->items))
                ->map(fn($id) => (int) trim($id))
                ->values();

            return $promoItemIds->count() !== 1 &&
                $orderItems->pluck('item_id')->contains($promoItemIds->first());
        })->map(function ($promo) use ($orderItems) {

            $promoItemId = (int) trim($promo->items);

            // 🔥 Sum qty ONLY for that item + UOM match
            $matchedQty = $orderItems
                ->filter(fn($i) => $i['item_id'] === $promoItemId)
                ->sum('qty');

            $detail = $promo->promotionDetails->first();

            $promo->calculated_free_qty = ($detail && $detail->from_qty > 0)
                ? intdiv($matchedQty, $detail->from_qty) * $detail->free_qty
                : 0;

            return $promo;
        })->values();
    }

    /* ================= RESPONSE ================= */

    // private function buildResponse(Collection $promos, array $items): array
    // {
    //     return $promos->map(function ($promo) {

    //         $detail = $promo->promotionDetails->first();

    //         return [
    //             'id' => $promo->id,
    //             'name' => $promo->promotion_name,
    //             'bundle_combination' => $promo->bundle_combination,
    //             'promotion_type' => $promo->promotion_type,
    //             'FocQty' => $promo->calculated_free_qty ?? 0,
    //             'promotion_items' => $promo->freeItems
    //                 ->unique(fn($free) => $free->item?->id)
    //                 ->values()
    //                 ->map(function ($free) {
    //                     return [
    //                         'id'          => $free->item?->id,
    //                         'item_code'   => $free->item?->code,
    //                         'item_name'   => $free->item?->name,
    //                         'item_uom_id' => $free->uom,
    //                         'name'        => $free->uomMaster?->name ?? '',
    //                     ];
    //                 }),
    //         ];
    //     })->values()->toArray();
    // }

    private function buildResponse(Collection $promos, array $items): array
    {
        return $promos
            // 🔴 REMOVE promotions where no free qty
            ->filter(function ($promo) {
                return (int) ($promo->calculated_free_qty ?? 0) > 0;
            })

            ->map(function ($promo) {

                $focQty = (int) $promo->calculated_free_qty;

                return [
                    'id'                 => $promo->id,
                    'name'               => $promo->promotion_name,
                    'bundle_combination' => $promo->bundle_combination,
                    'promotion_type'     => $promo->promotion_type,
                    'FocQty'             => $focQty,

                    'promotion_items' => $promo->freeItems
                        ->unique(fn($free) => $free->item?->id)
                        ->values()
                        ->map(function ($free) {
                            return [
                                'id'          => $free->item?->id,
                                'item_code'   => $free->item?->code,
                                'item_name'   => $free->item?->name,
                                'item_uom_id' => $free->uom,
                                'name'        => $free->uomMaster?->name ?? '',
                            ];
                        })
                        ->toArray(),
                ];
            })
            ->values()
            ->toArray();
    }

    public function invoice_promo(array $filters)
    {
        try {
             $query = PromotionHeader::select('id','promotion_name')->with('invoices')->orderByDesc('id');

                if (!empty($filters['promotion_id'])) {
                    $query->where('id', $filters['promotion_id']);
                }
                
                $limit = $filters['limit'] ?? 10;
                return $query->paginate($limit);
        } catch (\Throwable $e) {
            throw new \Exception('Failed to fetch Invoice list. Please try again later.',500,$e);
        }
    }
}
