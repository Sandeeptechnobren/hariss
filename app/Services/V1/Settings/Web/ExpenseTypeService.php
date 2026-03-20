<?php

namespace App\Services\V1\Settings\Web;

use App\Models\ExpenseType;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
class ExpenseTypeService
{
    use ApiResponse;

public function getAll(array $filters = [], int $perPage = 10): LengthAwarePaginator
{
    $query = ExpenseType::with([
                'createdBy' => function ($q) {
                    $q->select('id', 'name','username');
                },
                'updatedBy' => function ($q) {
                    $q->select('id', 'name','username');
                }
            ])->orderByDesc('id');

    foreach ($filters as $field => $value) {
        if (!empty($value)) {
            if (in_array($field, ['expense_type_code', 'expense_type_name', 'status'])) {
                $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
            } else {
                $query->where($field, $value);
            }
        }
    }

    return $query->orderByDesc('id')->paginate($perPage);
}



    public function getById($id)
    {
        return ExpenseType::findOrFail($id);
    }

public function create(array $data)
{
    DB::beginTransaction();
    try {
        $data['created_user'] = auth()->id();
        $data['updated_user'] = auth()->id();
        if (empty($data['expense_type_code'])) {
            $lastCode = ExpenseType::withTrashed()
                ->orderByDesc('id')
                ->value('expense_type_code');

            if (!$lastCode) {
                $data['expense_type_code'] = 'EXP001';
            } else {
                preg_match('/(\d+)$/', $lastCode, $matches);
                $nextNumber = isset($matches[1]) ? (int)$matches[1] + 1 : 1;
                $data['expense_type_code'] = 'EXP' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }
        }
        $newExpense = ExpenseType::create($data);
        DB::commit();
        return $newExpense;

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error creating expense type: ' . $e->getMessage(), [
            'data' => $data,
            'trace' => $e->getTraceAsString()
        ]);
        throw $e;
    }
}

public function update($id, array $data)
    {
        $data['updated_user'] = auth()->id();
        $updatedType=ExpenseType::findorfail($id);
        $updatedType->update($data);
        return $updatedType;
    }

public function delete($id) { 
    try { 
        $expenseType = ExpenseType::findOrFail($id); 
        $expenseType->delete(); 
        return true; 
    } 
    catch (\Exception $e) { 
        \Log::error('ExpenseType delete failed: ' . $e->getMessage(), [ 'expense_type_id' => $id ]); 
        return false; } 
    }



}
