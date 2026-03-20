<?php

namespace App\Services\V1\Settings\Web;

use App\Models\SalesmanType;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class SalesmanTypeService
{

    // public function getAll(array $filters = [], int $perPage = 10): LengthAwarePaginator
    // {
    //     $query = SalesmanType::query()
    //         ->select(['id', 'salesman_type_code', 'salesman_type_name', 'salesman_type_status', 'salesman_created_user', 'salesman_updated_user', 'salesman_created_date', 'salesman_updated_date'])
    //         ->with([
    //             'createdBy' => function ($q) {
    //                 $q->select('id', 'name', 'username');
    //             },
    //             'updatedBy' => function ($q) {
    //                 $q->select('id', 'name', 'username');
    //             }
    //         ])->where('salesman_type_status', 1)->orderByDesc('id');

    //     foreach ($filters as $field => $value) {
    //         if (!empty($value)) {
    //             $query->where($field, $value);
    //         }
    //     }

    //     return $query->orderByDesc('id')->paginate($perPage);
    // }

public function getAll(
    array $filters = [],
    int $perPage = 10,
    bool $dropdown = false
) {
    $query = SalesmanType::query()
        ->orderByDesc('id');

    // 🔹 STATUS FILTER (default = 1)
    if (array_key_exists('status', $filters)) {
        $query->where('salesman_type_status', $filters['status']);
    } else {
        $query->where('salesman_type_status', 1);
    }

    // 🔹 DROPDOWN MODE
    if ($dropdown) {
        return $query
            ->select(
                'id',
                'salesman_type_code',
                'salesman_type_name',
                'salesman_type_status'
            )
            ->orderBy('salesman_type_name')
            ->get();
    }

    // 🔹 NORMAL LIST MODE
    $query->select([
        'id',
        'salesman_type_code',
        'salesman_type_name',
        'salesman_type_status',
        'salesman_created_user',
        'salesman_updated_user',
        'salesman_created_date',
        'salesman_updated_date'
    ])->with([
        'createdBy:id,name,username',
        'updatedBy:id,name,username',
    ]);

    // 🔹 OTHER FILTERS (skip status to avoid duplicate)
    foreach ($filters as $field => $value) {
        if (!empty($value) && $field !== 'salesman_type_status') {
            $query->where($field, $value);
        }
    }

    return $query->paginate($perPage);
}


    public function getById($id)
    {
        return SalesmanType::findOrFail($id);
    }

    private function generateCode(): string
    {
        $last = SalesmanType::orderByDesc('id')->value('salesman_type_code');
        if ($last) {
            $number = (int) substr($last, 3);
            $next = $number + 1;
        } else {
            $next = 1;
        }
        return 'SMT' . str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Ensure the key exists
            $data['salesman_type_code'] = $data['salesman_type_code'] ?? $this->generateCode();
            // dd($data);
            $data['salesman_created_user'] = auth()->id();
            $data['salesman_updated_user'] = auth()->id();

            return SalesmanType::create($data);
        });
    }


    //     public function create(array $data)
    //     {
    //         return DB::transaction(function () use ($data) {
    //             $data['salesman_type_code'] = !empty($data['salesman_type_code'])
    //                 ? $data['salesman_type_code']
    //                 : $this->generateCode();
    // dd($data);
    //             $data['salesman_created_user'] = auth()->id();
    //             $data['salesman_updated_user'] = auth()->id(); // optional: track updated user

    //             return SalesmanType::create($data);
    //         });
    //     }


    public function update($id, array $data)
    {
        // dd($data);
        $data['salesman_updated_user'] = auth()->id();
        $salesmanType = SalesmanType::findorfail($id);
        $salesmanType->update($data);
        return $salesmanType;
    }


    public function delete($id): bool
    {
        try {
            $salesmanType = SalesmanType::where("id", $id)->delete();
            return true;
        } catch (\Exception $e) {
            \Log::error('SalesmanType delete failed: ' . $e->getMessage(), [
                'id' => $id
            ]);
            return false;
        }
    }

    // private function generateCode(): string
    // {
    //     $last = SalesmanType::orderByDesc('id')->value('salesman_type_code');
    //     if ($last) {
    //         $number = (int) substr($last, 3);
    //         $next = $number + 1;
    //     } else {
    //         $next = 1;
    //     }
    //     return 'SMT' . str_pad($next, 3, '0', STR_PAD_LEFT);
    // }
}
