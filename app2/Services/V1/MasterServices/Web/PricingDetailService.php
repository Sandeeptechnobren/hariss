<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\PricingDetail;
use App\Models\PricingHeader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class PricingDetailService
{
    public function all(array $filters = [], int $perPage = 10)
    {
        $query = PricingDetail::query();

        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                $query->where($field, $value);
            }
        }

        return $query->paginate($perPage);
    }

    // public function generateOsaCode(): string
    // {
    //     do {
    //         $last = PricingDetail::withTrashed()->latest('id')->first();
    //         $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
    //         $osa_code = 'PD' . str_pad($next, 3, '0', STR_PAD_LEFT);
    //     } while (PricingDetail::withTrashed()->where('osa_code', $osa_code)->exists());

    //     return $osa_code;
    // }

    // public function create(array $data): PricingDetail
    // {
    //     DB::beginTransaction();
    //     try {
    //         $data = array_merge($data, [
    //             'uuid' => $data['uuid'] ?? Str::uuid()->toString(),
    //             'osa_code' => $this->generateOsaCode(),
    //         ]);
    //         $pricingDetail = PricingDetail::create($data);
    //         DB::commit();
    //         return $pricingDetail;
    //     } catch (Throwable $e) {
    //         DB::rollBack();
    //         Log::error('PricingDetail creation failed', ['error' => $e->getMessage(), 'data' => $data]);
    //         throw new \Exception('Failed to create pricing detail: ' . $e->getMessage(), 0, $e);
    //     }
    // }


    public function generateCode(): string
    {
        do {
            $lastPrice = PricingHeader::withTrashed()->latest('id')->first();
            $nextNumber = $lastPrice
                ? ((int) preg_replace('/\D/', '', $lastPrice->code)) + 1
                : 1;

            $code = 'PH' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        } while (PricingHeader::withTrashed()->where('code', $code)->exists());

        return $code;
    }
    public function create(array $data)
    {
        DB::beginTransaction();

        try {
            // 🔹 Convert arrays to comma-separated strings for DB storage
            $multiFields = [
                'warehouse_id',
                'item_type',
                'company_id',
                'region_id',
                'area_id',
                'route_id',
                'item_id',
                'item_category_id',
                'customer_id',
                'customer_category_id',
                'customer_type_id',
                'outlet_channel_id'
            ];

            foreach ($multiFields as $field) {
                if (isset($data[$field]) && is_array($data[$field])) {
                    $data[$field] = implode(',', $data[$field]);
                }
            }

            // 1️⃣ Create header
            $header = PricingHeader::create([
                'uuid' => Str::uuid()->toString(),
                'name' => $data['name'],
                'code' => $this->generateCode(),
                'description' => $data['description'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'apply_on' => $data['apply_on'] ?? null,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'item_type' => $data['item_type'] ?? null,
                'status' => $data['status'] ?? 1,
                'company_id' => $data['company_id'] ?? null,
                'region_id' => $data['region_id'] ?? null,
                'area_id' => $data['area_id'] ?? null,
                'route_id' => $data['route_id'] ?? null,
                'item_category_id' => $data['item_category_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'item_id' => $data['item_id'] ?? null,
                'customer_category_id' => $data['customer_category_id'] ?? null,
                'customer_type_id' => $data['customer_type_id'] ?? null,
                'outlet_channel_id' => $data['outlet_channel_id'] ?? null,
                'created_user' => $data['created_user'] ?? 1,
                'updated_user' => $data['updated_user'] ?? null,
            ]);

            // 2️⃣ Prepare details with sequential osa_code
            $last = PricingDetail::withTrashed()->latest('id')->first();
            $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;

            $details = collect($data['details'] ?? [])->map(function ($detail) use ($data, $header, &$next) {
                $osa_code = 'PD' . str_pad($next, 3, '0', STR_PAD_LEFT);
                $next++;

                return [
                    'uuid' => Str::uuid()->toString(),
                    'osa_code' => $osa_code,
                    'name' => isset($detail['item_id']) ? 'Item ' . $detail['item_id'] : null,
                    'header_id' => $header->id,
                    'item_id' => $detail['item_id'] ?? null,
                    'buom_ctn_price' => $detail['buom_ctn_price'] ?? 0.00,
                    'auom_pc_price' => $detail['auom_pc_price'] ?? 0.00,
                    'status' => $detail['status'] ?? $data['status'] ?? 1,
                    'created_user' => $data['created_user'] ?? 1,
                ];
            })->toArray();

            // 3️⃣ Bulk insert details
            $header->details()->createMany($details);

            DB::commit();

            return $header->load('details.item');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Pricing create failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw new \Exception('Failed to create pricing: ' . $e->getMessage(), 0, $e);
        }
    }




    public function findByUuid(string $uuid): ?PricingDetail
    {
        return PricingDetail::where('uuid', $uuid)->first();
    }

    public function updateByUuid(string $uuid, array $data): PricingHeader
    {
        // Find the header by UUID
        $header = PricingHeader::where('uuid', $uuid)->first();

        if (!$header) {
            throw new \Exception("Pricing header not found");
        }

        DB::beginTransaction();

        try {
            // 🔹 Convert arrays to comma-separated strings for DB storage
            $multiFields = [
                'warehouse_id',
                'item_type',
                'company_id',
                'region_id',
                'area_id',
                'route_id',
                'item_id',
                'item_category_id',
                'customer_id',
                'customer_category_id',
                'customer_type_id',
                'outlet_channel_id'
            ];

            foreach ($multiFields as $field) {
                if (isset($data[$field]) && is_array($data[$field])) {
                    $data[$field] = implode(',', $data[$field]);
                }
            }
            
            // 🔹 Update header fields
            $header->update([
                'name' => $data['name'] ?? $header->name,
                'code' => $data['code'] ?? $header->code,
                'description' => $data['description'] ?? $header->description,
                'start_date' => $data['start_date'] ?? $header->start_date,
                'end_date' => $data['end_date'] ?? $header->end_date,
                'apply_on' => $data['apply_on'] ?? $header->apply_on,
                'warehouse_id' => $data['warehouse_id'] ?? $header->warehouse_id,
                'item_type' => $data['item_type'] ?? $header->item_type,
                'status' => $data['status'] ?? $header->status,
                'company_id' => $data['company_id'] ?? $header->company_id,
                'region_id' => $data['region_id'] ?? $header->region_id,
                'area_id' => $data['area_id'] ?? $header->area_id,
                'item_id' => $data['item_id'] ?? $header->item_id,
                'route_id' => $data['route_id'] ?? $header->route_id,
                'item_category_id' => $data['item_category_id'] ?? $header->item_category_id,
                'customer_id' => $data['customer_id'] ?? $header->customer_id,
                'customer_category_id' => $data['customer_category_id'] ?? $header->customer_category_id,
                'customer_type_id' => $data['customer_type_id'] ?? $header->customer_type_id,
                'outlet_channel_id' => $data['outlet_channel_id'] ?? $header->outlet_channel_id,
                'updated_user' => $data['updated_user'] ?? $header->updated_user,
            ]);

            // 🔹 Update details
            if (!empty($data['details'])) {
                // Delete old details (optional: or you can update individually)
                $header->details()->delete();

                // Generate new osa_code sequence
                $last = PricingDetail::withTrashed()->latest('id')->first();
                $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;

                $details = collect($data['details'])->map(function ($detail) use ($header, &$next, $data) {
                    $osa_code = 'PD' . str_pad($next, 3, '0', STR_PAD_LEFT);
                    $next++;

                    return [
                        'uuid' => Str::uuid()->toString(),
                        'osa_code' => $osa_code,
                        'name' => isset($detail['item_id']) ? 'Item ' . $detail['item_id'] : null,
                        'header_id' => $header->id,
                        'item_id' => $detail['item_id'] ?? null,
                        'buom_ctn_price' => $detail['buom_ctn_price'] ?? 0.00,
                        'auom_pc_price' => $detail['auom_pc_price'] ?? 0.00,
                        'status' => $detail['status'] ?? $data['status'] ?? 1,
                        'created_user' => $data['updated_user'] ?? $header->updated_user ?? 1,
                    ];
                })->toArray();

                $header->details()->createMany($details);
            }

            DB::commit();

            // 🔹 Return updated header with details
            return $header->load('details.item');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Pricing header update failed', [
                'error' => $e->getMessage(),
                'uuid' => $uuid,
                'data' => $data
            ]);
            throw new \Exception('Failed to update pricing header: ' . $e->getMessage(), 0, $e);
        }
    }


    public function deleteByUuid(string $uuid): void
    {
        $detail = $this->findByUuid($uuid);
        if (!$detail) {
            throw new \Exception("Pricing detail not found");
        }

        DB::beginTransaction();
        try {
            $detail->delete();
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('PricingDetail delete failed', ['error' => $e->getMessage(), 'uuid' => $uuid]);
            throw new \Exception('Failed to delete pricing detail: ' . $e->getMessage(), 0, $e);
        }
    }

    public function globalSearch($perPage = 10, $searchTerm = null)
    {
        try {
            $query = PricingDetail::with([
                'item:id,code,name',
                'header:id,code,name',
                'createdBy:id,firstname,lastname,username',
                'updatedBy:id,firstname,lastname,username',
            ]);

            if (!empty($searchTerm)) {
                $searchTerm = strtolower($searchTerm);

                $query->where(function ($q) use ($searchTerm) {
                    $likeSearch = '%' . $searchTerm . '%';

                    $q->orWhereRaw("LOWER(osa_code::text) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(name::text) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(status::text) LIKE ?", [$likeSearch]);
                });
            }

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            throw new \Exception("Failed to search pricing details: " . $e->getMessage());
        }
    }
}
