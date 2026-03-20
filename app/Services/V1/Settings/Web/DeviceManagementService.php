<?php

namespace App\Services\V1\Settings\Web;

use App\Models\DeviceManagement;
use Illuminate\Support\Facades\DB;
use Exception;

class DeviceManagementService
{

    public function createBank(array $data): DeviceManagement
    {
        return DB::transaction(function () use ($data) {
            return DeviceManagement::create($data);
        });
    }

     public function listBanks(array $filters = [], int $perPage = null)
    {
        $query = DeviceManagement::query();

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

    public function getByUuid(string $uuid): ?DeviceManagement
{
    return DeviceManagement::where('uuid', $uuid)->first();
}


public function updateBankByUuid(string $uuid, array $data): ?DeviceManagement
{
    $bank = DeviceManagement::where('uuid', $uuid)->first();

    if (!$bank) {
        return null;
    }

    $bank->update($data);
    return $bank->fresh();
}
}