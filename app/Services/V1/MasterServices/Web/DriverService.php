<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\Driver;
use Illuminate\Support\Facades\DB;
use Exception;

class DriverService
{

    public function createBank(array $data): Driver
    {
        return DB::transaction(function () use ($data) {
            return Driver::create($data);
        });
    }

     public function listBanks(array $filters = [], int $perPage = null)
    {
        $query = Driver::query();

        if (!empty($filters['device_name'])) {
            $query->where('device_name', 'like', '%' . $filters['device_name'] . '%');
        }

        if (!empty($filters['osa_code'])) {
            $query->where('osa_code', 'like', '%' . $filters['osa_code'] . '%');
        }

        if (!empty($filters['IMEI_1'])) {
            $query->where('IMEI_1', 'like', '%' . $filters['IMEI_1'] . '%');
        }

         if (!empty($filters['IMEI_2'])) {
            $query->where('IMEI_2', 'like', '%' . $filters['IMEI_2'] . '%');
        }
        
        if ($perPage) {
            return $query->orderBy('id', 'desc')->paginate($perPage);
        }

        return $query->orderBy('id', 'desc')->get();
    }

    public function getByUuid(string $uuid): ?Driver
{
    return Driver::where('uuid', $uuid)->first();
}


public function updateBankByUuid(string $uuid, array $data): ?Driver
{
    $bank = Driver::where('uuid', $uuid)->first();

    if (!$bank) {
        return null;
    }

    $bank->update($data);
    return $bank->fresh();
}
}
