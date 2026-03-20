<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\DiscountHeader;
use App\Models\DiscountDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class DiscountService
{
    /**
     * LIST WITH PAGINATION
     */
    public function getAll(int $perPage = 50)
    {
        try {
            return DiscountHeader::with(['details'])
                ->latest()
                ->paginate($perPage);
        } catch (Exception $e) {
            throw new Exception("Failed to fetch discounts: " . $e->getMessage());
        }
    }

    /**
     * GET BY UUID
     */
    public function getByUuid(string $uuid): DiscountHeader
    {
        $discount = DiscountHeader::with(['details'])
            ->where('uuid', $uuid)
            ->first();

        if (!$discount) {
            throw new Exception("Discount not found");
        }

        return $discount;
    }

    /**
     * CREATE DISCOUNT (HEADER + DETAILS)
     */
    public function create(array $data): DiscountHeader
    {
        // dd($data);
        try {
            if (empty($data['osa_code'])) {
                $last = DiscountHeader::withTrashed()->latest('id')->first();
                $nextId = $last ? $last->id + 1 : 1;
                $data['osa_code'] = 'DSC' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
            }

            return DB::transaction(function () use ($data) {

                // 🔹 Normalize array fields to JSON
                $headerPayload = [
                    'uuid'               => \Str::uuid(),
                    'osa_code'           => $data['osa_code'],
                    'discount_name'      => $data['discount_name'],
                    'discount_apply_on'  => $data['discount_apply_on'],
                    'discount_type'      => $data['discount_type'],
                    'bundle_combination' => $data['bundle_combination'] ?? null,

                    'from_date'          => $data['from_date'],
                    'to_date'            => $data['to_date'],
                    'status'             => $data['status'] ?? 1,
                    'order_amount'                 => $data['header']['headerMinAmount'] ?? null,
                    'discount_amount_percentage'   => $data['header']['headerRate'] ?? null,
                    'sales_team_type' => !empty($data['sales_team_type'])
                        ? implode(',', $data['sales_team_type'])
                        : null,

                    'project_list' => !empty($data['project_list'])
                        ? implode(',', $data['project_list'])
                        : null,

                    'uom' => $data['uom'] ?? null,

                    'items' => !empty($data['items'])
                        ? implode(',', $data['items'])
                        : null,

                    'item_category' => !empty($data['item_category'])
                        ? implode(',', $data['item_category'])
                        : null,

                    'location' => !empty($data['location'])
                        ? implode(',', $data['location'])
                        : null,

                    'customer' => !empty($data['customer'])
                        ? implode(',', $data['customer'])
                        : null,

                    'key_location' => !empty($data['key']['Location'])
                        ? implode(',', $data['key']['Location'])
                        : null,

                    'key_customer' => !empty($data['key']['Customer'])
                        ? implode(',', $data['key']['Customer'])
                        : null,

                    'key_item' => !empty($data['key']['Item'])
                        ? implode(',', $data['key']['Item'])
                        : null,
                    'created_user'       => auth()->id(),
                ];

                $header = DiscountHeader::create($headerPayload);

                // 🔹 Insert details
                if (
                    isset($data['discount_details']) &&
                    is_array($data['discount_details'])
                ) {
                    foreach ($data['discount_details'] as $detail) {
                        DiscountDetail::create([
                            'header_id'    => $header->id,
                            'item_id'      => $detail['item_id'] ?? null,
                            'category_id'  => $detail['category_id'] ?? null,
                            'uom'          => $detail['uom'] ?? null,
                            'percentage'   => $detail['percentage'] ?? null,
                            'amount'       => $detail['amount'] ?? null,
                            'created_user' => auth()->id(),
                        ]);
                    }
                }


                return $header->load('details');
            });
        } catch (\Exception $e) {
            // dd($e);
            throw new \Exception("Failed to create discount: " . $e->getMessage());
        }
    }


    /**
     * UPDATE DISCOUNT
     */
    public function update(string $uuid, array $data): DiscountHeader
    {
        try {
            return DB::transaction(function () use ($uuid, $data) {

                $header = DiscountHeader::where('uuid', $uuid)->first();

                if (! $header) {
                    throw new \Exception('Discount not found');
                }

                // 🔹 Normalize payload exactly like create()
                $headerPayload = [

                    'discount_name'      => $data['discount_name'] ?? $header->discount_name,
                    'discount_apply_on'  => $data['discount_apply_on'] ?? $header->discount_apply_on,
                    'discount_type'      => $data['discount_type'] ?? $header->discount_type,
                    'bundle_combination' => $data['bundle_combination'] ?? null,

                    'from_date' => $data['from_date'] ?? $header->from_date,
                    'to_date'   => $data['to_date'] ?? $header->to_date,
                    'status'    => $data['status'] ?? $header->status,

                    'order_amount' => $data['header']['headerMinAmount'] ?? null,
                    'discount_amount_percentage'
                    => $data['header']['headerRate'] ?? null,

                    'sales_team_type' => !empty($data['sales_team_type'])
                        ? implode(',', $data['sales_team_type'])
                        : null,

                    'project_list' => !empty($data['project_list'])
                        ? implode(',', $data['project_list'])
                        : null,

                    'uom' => $data['uom'] ?? null,

                    'items' => !empty($data['items'])
                        ? implode(',', $data['items'])
                        : null,

                    'item_category' => !empty($data['item_category'])
                        ? implode(',', $data['item_category'])
                        : null,

                    'location' => !empty($data['location'])
                        ? implode(',', $data['location'])
                        : null,

                    'customer' => !empty($data['customer'])
                        ? implode(',', $data['customer'])
                        : null,

                    'key_location' => !empty($data['key']['Location'])
                        ? implode(',', $data['key']['Location'])
                        : null,

                    'key_customer' => !empty($data['key']['Customer'])
                        ? implode(',', $data['key']['Customer'])
                        : null,

                    'key_item' => !empty($data['key']['Item'])
                        ? implode(',', $data['key']['Item'])
                        : null,

                    'updated_user' => auth()->id(),
                ];

                // 🔹 Update header
                $header->update($headerPayload);

                // 🔹 Refresh discount details
                if (
                    isset($data['discount_details']) &&
                    is_array($data['discount_details'])
                ) {
                    DiscountDetail::where('header_id', $header->id)->delete();

                    foreach ($data['discount_details'] as $detail) {
                        DiscountDetail::create([
                            'header_id'    => $header->id,
                            'item_id'      => $detail['item_id'] ?? null,
                            'category_id'  => $detail['category_id'] ?? null,
                            'uom'          => $detail['uom'] ?? null,
                            'percentage'   => $detail['percentage'] ?? null,
                            'amount'       => $detail['amount'] ?? null,
                            'created_user' => auth()->id(),
                        ]);
                    }
                }

                return $header->load('details');
            });
        } catch (\Exception $e) {
            throw new \Exception('Failed to update discount: ' . $e->getMessage());
        }
    }

    /**
     * DELETE (SOFT DELETE)
     */
    public function delete(string $uuid): bool
    {
        return DB::transaction(function () use ($uuid) {

            $header = DiscountHeader::where('uuid', $uuid)->first();

            if (!$header) {
                throw new Exception("Discount not found");
            }

            return $header->delete();
        });
    }

    /**
     * GLOBAL SEARCH
     */
    // public function globalSearch(int $perPage = 10, ?string $searchTerm = null)
    // {
    //     try {
    //         $query = DiscountHeader::with('details');

    //         if (!empty($searchTerm)) {
    //             $like = '%' . strtolower($searchTerm) . '%';

    //             $query->where(function ($q) use ($like) {
    //                 $q->orWhereRaw('LOWER(discount_name) LIKE ?', [$like])
    //                     ->orWhereRaw('LOWER(discount_type) LIKE ?', [$like])
    //                     ->orWhereRaw('LOWER(osa_code) LIKE ?', [$like])
    //                     ->orWhereRaw('LOWER(uuid::text) LIKE ?', [$like]);
    //             });
    //         }

    //         return $query->paginate($perPage);
    //     } catch (Exception $e) {
    //         throw new Exception("Failed to search discounts: " . $e->getMessage());
    //     }
    // }

    /**
     * EXPORT DATA
     */
    public function getExportData()
    {
        return DiscountHeader::with('details')->get([
            'id',
            'osa_code',
            'discount_name',
            'discount_type',
            'from_date',
            'to_date',
            'status'
        ]);
    }


    public function globalSearch(int $perPage = 10, ?string $searchTerm = null)
    {
        $query = DiscountHeader::with([
            'details',
        ])
            ->whereNull('deleted_at');

        if ($searchTerm) {

            $query->where(function ($q) use ($searchTerm) {

                $q->where('osa_code', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('discount_name', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('discount_apply_on', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('discount_type', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('bundle_combination', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('key_item', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('key_location', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('key_customer', 'ILIKE', "%{$searchTerm}%");

                $q->orWhereRaw('id::text ILIKE ?', ["%{$searchTerm}%"])
                    ->orWhereRaw('status::text ILIKE ?', ["%{$searchTerm}%"])
                    ->orWhereRaw('uom::text ILIKE ?', ["%{$searchTerm}%"])
                    ->orWhereRaw('from_date::text ILIKE ?', ["%{$searchTerm}%"])
                    ->orWhereRaw('to_date::text ILIKE ?', ["%{$searchTerm}%"]);

                $q->orWhere('order_amount', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('discount_amount_percentage', 'ILIKE', "%{$searchTerm}%");

                $q->orWhere('items', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('item_category', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('location', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('customer', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('sales_team_type', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('project_list', 'ILIKE', "%{$searchTerm}%");

                $q->orWhereHas('details', function ($dq) use ($searchTerm) {
                    $dq->whereNull('deleted_at')
                        ->where(function ($sub) use ($searchTerm) {
                            $sub->where('item_id', 'ILIKE', "%{$searchTerm}%")
                                ->orWhere('category_id', 'ILIKE', "%{$searchTerm}%")
                                ->orWhere('uom', 'ILIKE', "%{$searchTerm}%")
                                ->orWhereRaw('percentage::text ILIKE ?', ["%{$searchTerm}%"])
                                ->orWhereRaw('amount::text ILIKE ?', ["%{$searchTerm}%"]);
                        });
                });
            });
        }

        return $query
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
