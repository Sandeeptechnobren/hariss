<?php

namespace App\Services\V1\Merchendisher\Web;

use App\Models\ShelfItem;
use App\Models\Damage;
use App\Models\ExpiryShelfItem;
use App\Models\ViewStockPost;

class ShelveItemService
{
public function list(int $perPage = 50, ?int $shelfId = null)
    {
        $query = ShelfItem::query();
        if ($shelfId) {
            $query->where('shelf_id', $shelfId);
        }
        return $query->paginate($perPage);
    }
public function create(array $data)
    {
        $data['created_user'] = auth()->id();
        return ShelfItem::create($data);
    }
public function update($uuid, array $data)
    {
        $item = ShelfItem::where('uuid', $uuid)->firstOrFail();
        $data['updated_user'] = auth()->id();
        $item->update($data);
        return $item;
    }
public function delete($uuid)
    {
        $item = ShelfItem::where('uuid', $uuid)->firstOrFail();
        $item->deleted_user = auth()->id();
        $item->save();
        $item->delete(); // Soft delete
        return true;
    }
public function damagelist(int $perPage = 50, ?int $shelfId = null)
    {
        $query = Damage::query();
        if ($shelfId) {
            $query->where('shelf_id', $shelfId);
        }
        return $query->paginate($perPage);
    } 
public function expiry(int $perPage = 50, ?int $shelfId = null)
    {
        $query = ExpiryShelfItem::query();
        if ($shelfId) {
            $query->where('shelf_id', $shelfId);
        }
        return $query->paginate($perPage);
    } 
public function viewstock(int $perPage = 50, ?int $shelfId = null)
    {
        $query = ViewStockPost::query();
        if ($shelfId) {
            $query->where('shelf_id', $shelfId);
        }
        return $query->paginate($perPage);
    }         
}
