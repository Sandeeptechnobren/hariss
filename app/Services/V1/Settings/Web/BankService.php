<?php

namespace App\Services\V1\Settings\Web;

use App\Models\Bank;
use Illuminate\Support\Facades\DB;
use Exception;

class BankService
{

    public function createBank(array $data): Bank
    {
        return DB::transaction(function () use ($data) {
            return Bank::create($data);
        });
    }

     public function listBanks(array $filters = [], int $perPage = null)
    {
        $query = Bank::query();

        if (!empty($filters['osa_code'])) {
            $query->where('osa_code', 'like', '%' . $filters['osa_code'] . '%');
        }

        if (!empty($filters['bank_name'])) {
            $query->where('bank_name', 'like', '%' . $filters['bank_name'] . '%');
        }

        if (!empty($filters['city'])) {
            $query->where('city', 'like', '%' . $filters['city'] . '%');
        }

         if (!empty($filters['account_number'])) {
            $query->where('account_number', 'like', '%' . $filters['account_number'] . '%');
        }

         if (!empty($filters['branch'])) {
            $query->where('branch', 'like', '%' . $filters['branch'] . '%');
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if ($perPage) {
            return $query->orderBy('id', 'desc')->paginate($perPage);
        }

        return $query->orderBy('id', 'desc')->get();
    }

    public function getByUuid(string $uuid): ?Bank
{
    return Bank::where('uuid', $uuid)->first();
}


public function updateBankByUuid(string $uuid, array $data): ?Bank
{
    $bank = Bank::where('uuid', $uuid)->first();

    if (!$bank) {
        return null;
    }

    $bank->update($data);
    return $bank->fresh();
}
}