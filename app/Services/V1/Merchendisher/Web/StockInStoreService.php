<?php

namespace App\Services\V1\Merchendisher\Web;

use App\Models\StockInStore;
use App\Models\StockInStorePost;
use App\Models\AssignInventory;
use App\Models\CompanyCustomer;
use Illuminate\Support\Facades\Auth;
use App\Helpers\SearchHelper;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
class StockInStoreService
{
  public function create(array $data): StockInStore
{
    $customerIds = $data['assign_customers'] ?? [];
    $existingIds = CompanyCustomer::whereIn('id', $customerIds)
        ->pluck('id')
        ->toArray();

    $invalidIds = array_diff($customerIds, $existingIds);

    if (!empty($invalidIds)) {
        throw ValidationException::withMessages([
            'assign_customers' => ['Some selected customers do not exist.'],
        ]);
    }

    $stock = StockInStore::create($data);

    if (!empty($data['assign_inventory']) && is_array($data['assign_inventory'])) {
        collect($data['assign_inventory'])->each(function ($inventory) use ($stock) {
            AssignInventory::create([
                'uuid'        => Str::uuid()->toString(),
                'item_id'   => $inventory['item_id'],
                'item_uom'  => $inventory['item_uom'],
                'capacity'  => $inventory['capacity'],
                'header_id' => $stock->id,
            ]);
        });
    }

    return $stock;
}
public function getAll()
    {
        $search = request()->input('search');
        $query = StockInStore::latest();
       $query = SearchHelper::applySearch($query, $search, [
        'id',
        'code',
        'uuid',
        'activity_name',
        'date_from',
        'date_to',
        'assign_customers',
        'created_user',
        'updated_user',
        'deleted_user',
        'created_user.name',
        'updated_user.name',
        'created_at',
        'updated_at',
        'deleted_at',
    ]);
        return $query->paginate(request()->get('per_page', 50));
    }

    public function getByUuid(string $uuid): StockInStore
    {
        return StockInStore::where('uuid', $uuid)->firstOrFail();
    }

public function update(string $uuid, array $data): StockInStore
{
    $stock = $this->getByUuid($uuid);
    $customerIds = $data['assign_customers'] ?? [];
    if (!empty($customerIds)) {
        $existingIds = CompanyCustomer::whereIn('id', $customerIds)
            ->pluck('id')
            ->toArray();
        $invalidIds = array_diff($customerIds, $existingIds);
        if (!empty($invalidIds)) {
            throw ValidationException::withMessages([
                'assign_customers' => ['Some selected customers do not exist.'],
            ]);
        }
    }
    $stock->update($data);
    if (isset($data['assign_inventory']) && is_array($data['assign_inventory'])) {
        AssignInventory::where('header_id', $stock->id)->delete();
        collect($data['assign_inventory'])->each(function ($inventory) use ($stock) {
            AssignInventory::create([
                'uuid'      => Str::uuid()->toString(),
                'item_id'   => $inventory['item_id'],
                'item_uom'  => $inventory['item_uom'],
                'capacity'  => $inventory['capacity'],
                'header_id' => $stock->id,
            ]);
        });
    }
    return $stock;
}
    public function delete(string $uuid): void
    {
        $stock = $this->getByUuid($uuid);
        $stock->deleted_user = Auth::id();
        $stock->save();
        $stock->delete();
    }

     public function bulkUpload($file): array
    {
        $rows = Excel::toCollection(null, $file)->first(); 

         $rows = $rows->map(function ($row) {
        return $row->map(function ($val) {
            return $this->cleanValue($val);
        });
    });

    return $rows->skip(1)
    ->map(function ($row) {
               $customerIdsRaw = $this->cleanValue($row[3]);  
                $customerIds = array_map('intval', array_map('trim', explode(',', $customerIdsRaw)));


                $data = [
                    'activity_name'     => $row[0],
                    'date_from'         => $row[1],
                    'date_to'           => $row[2],
                    'assign_customers'  => $customerIds,
                    'assign_inventory'  => [[
                        'item_id'   => (int) $row[4],
                        'item_uom'  => $row[5],
                        'capacity'  => (float) $row[6],
                    ]]
                ];
                //  dd($data);
                return $this->create($data);
            })
            ->toArray();
    }
    protected function cleanValue($val)
    {
        $val = (string) $val;
        $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8');
        $val = utf8_encode($val);
        $val = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $val);
        $val = trim($val, "\xEF\xBB\xBF \t\n\r\0\x0B");
        return $val;
    }

public function getPostsByStockUuid(string $uuid)
    {
        $stock = StockInStore::where('uuid', $uuid)->firstOrFail();
        return StockInStorePost::where('stock_id', $stock->id)
            ->orderBy('id', 'desc')
            ->paginate(request()->get('per_page', 50));
    }
}
