<?php
namespace App\Services\V1\Merchendisher\Web;

use App\Models\AssignInventory;
use App\Models\Item;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Models\StockInStore;
use Illuminate\Support\Facades\Auth;
use App\Helpers\SearchHelper;

class AsignInventoryService
{

     public function index()
    {
         $search = request()->input('search');
        $query = AssignInventory::with(['item','StockInStore',])->latest();
        $query = SearchHelper::applySearch($query, $search, [
            'id',
            'uuid',
            'item.code',
            'item.name',
            'item.uom',
            'StockInStore.activity_name',
            'StockInStore.code',
            'capacity',
            'created_user.firstname',
            'updated_user.firstname',
        ]);

        return $query->paginate(request()->get('per_page', 10));
    }

    public function store(array $data)
    {
        // Fetch item from items table
        $item = Item::findOrFail($data['item_id']);

        $customerItem = AssignInventory::create([
            'uuid'        => Str::uuid()->toString(),
            'item_code'   => $item->code,
            'item_name'   => $item->name,
            'item_uom'    => $item->uom,
            'customer_id' => $data['customer_id'],
            'capacity'    => $data['capacity'],
        ]);

        return $customerItem;
    }

    public function show($uuid)
    {
        return AssignInventory::where('uuid', $uuid)->firstOrFail();
    }

    public function update(array $data, $uuid)
    {
        $assignInventory = AssignInventory::where('uuid', $uuid)->firstOrFail();

        $assignInventory->update([
            'capacity'     => $data['capacity'] ?? $assignInventory->capacity,
            'updated_user' => auth()->id() ?? null,
        ]);

        return $assignInventory;
    }

    public function destroy($uuid)
    {
        $assignInventory = AssignInventory::where('uuid', $uuid)->firstOrFail();
        return $assignInventory->delete();
    }

 public function getExportData()
{
    $userId = Auth::user()->id;

    $assignInventoryData = AssignInventory::where('created_user', $userId)
        ->whereNull('deleted_at')
        ->get();

    $customerIds = $assignInventoryData->pluck('customer_id')->toArray();

    $stockInStoreData = StockInStore::whereIn('id', $customerIds)
        ->whereNull('deleted_at')
        ->get();

    $mergedData = $this->mergeData($assignInventoryData, $stockInStoreData);

    return collect($mergedData); 
}

private function mergeData($assignInventoryData, $stockInStoreData)
{
    $merged = [];

    $stockLookup = $stockInStoreData->keyBy('id');

    foreach ($assignInventoryData as $assign) {

        if (isset($stockLookup[$assign->customer_id])) {
            $stock = $stockLookup[$assign->customer_id]; 

            $merged[] = [
                'code' => $stock->code,
                'activity_name' => $stock->activity_name,
                'date_from' => $stock->date_from,
                'date_to' => $stock->date_to,
                'item_code' => $assign->item_code,
                'item_uom' => $assign->item_uom,
                'capacity' => $assign->capacity,
                'item_name' => $assign->item_name,
            ];
        }
    }

    return $merged;
}

     
    public function bulkUpload($file)
    {
        $path = $file->getRealPath();
        if ($file->getClientOriginalExtension() === 'csv') {
            $data = array_map('str_getcsv', file($path));
        } else {
            $data = Excel::toArray([], $file);
            $data = $data[0]; 
        }
        $header = array_shift($data);

        $inserted = [];
        foreach ($data as $row) {
            $rowData = array_combine($header, $row);
            $validator = Validator::make($rowData, [
                'item_id'     => 'required|exists:items,id',
                'customer_id' => 'required|exists:stock_in_store,id',
                'capacity'    => 'required|numeric|min:1',
            ]);

            if ($validator->fails()) {
                continue; 
            }

            $inserted[] = $this->store($rowData);
        }
        return $inserted;
    }
}