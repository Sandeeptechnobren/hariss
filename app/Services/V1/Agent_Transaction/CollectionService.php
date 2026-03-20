<?php

namespace App\Services\V1\Agent_Transaction;

use App\Models\Agent_Transaction\Collection;
use Illuminate\Support\Facades\DB;
use Exception;

class CollectionService
{
    /**
     * Store a new collection record.
     *
     * @param array $data
     * @return Collection
     * @throws Exception
     */
    public function store(array $data): Collection
    {
        return DB::transaction(function () use ($data) {
            $collection = Collection::create($data);
            return $collection;
        });
    }

    public function list(array $filters = [], int $perPage = 50)
    {
        $query = Collection::query()->with(['invoice', 'customer','salesman','route','warehouse']);
        if (!empty($filters['collection_no'])) {
            $query->where('collection_no', $filters['collection_no']);
        }
        if (!empty($filters['invoice_id'])) {
            $query->where('invoice_id', 'like', '%' . $filters['invoice_id'] . '%');
        }
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }
        if (!empty($filters['salesman_id'])) {
            $query->where('salesman_id', $filters['salesman_id']);
        }
        if (!empty($filters['route_id'])) {
            $query->where('route_id', $filters['route_id']);
        }
        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }
        if (!empty($filters['amount'])) {
            $query->where('amount', $filters['amount']);
        }
        if (!empty($filters['outstanding'])) {
            $query->where('outstanding', $filters['outstanding']);
        }
        $query->orderBy('id', 'desc');
        return $query->paginate($perPage);
    }

    public function getByUuid(string $uuid)
    {
        return Collection::with(['invoice', 'customer','salesman','route','warehouse'])
            ->where('uuid', $uuid)
            ->first();
    }
}