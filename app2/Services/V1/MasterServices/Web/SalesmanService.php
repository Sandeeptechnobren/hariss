<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\Salesman;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Error;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use App\Exports\SalesmanExport;
use Maatwebsite\Excel\Facades\Excel;

class SalesmanService
{
    public function all(int $perPage = 50, array $filters = [], bool $dropdown = false)
    {
        $query = Salesman::with([
            'route:id,route_code,route_name',
            'salesmanType:id,salesman_type_code,salesman_type_name',
            'warehouse:id,warehouse_code,warehouse_name'
        ])->latest();

        if ($dropdown) {
            $query->select(['id', 'name', 'osa_code']);
        } else {
            $query->select([
                'id', 'uuid', 'osa_code', 'name', 'designation', 'contact_no',
                'type', 'route_id', 'warehouse_id', 'status', 'reason',
                'is_block', 'forceful_login'
            ]);
        }

        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                if (in_array($field, ['osa_code', 'name', 'designation', 'username'])) {
                    $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                } else {
                    $query->where($field, $value);
                }
            }
        }

        if ($dropdown) {
            return $query->get();
        } else {
            return $query->paginate($perPage);
        }
    }

    public function findByUuid(string $uuid): Salesman
    {
        $salesman = Salesman::with(['route:id,route_code,route_name', 'warehouse:id,warehouse_code,warehouse_name'])
            ->where('uuid', $uuid)
            ->first();

        if (!$salesman) {
            throw new ModelNotFoundException("Salesman not found with UUID: {$uuid}");
        }

        return $salesman;
    }

    public function generateCode(): string
    {
        do {
            $last = Salesman::withTrashed()->latest('id')->first();
            $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $osa_code = 'SA' . str_pad($next, 3, '0', STR_PAD_LEFT);
        } while (Salesman::where('osa_code', $osa_code)->exists());

        return $osa_code;
    }

    public function create(array $data): Salesman
    {
        DB::beginTransaction();
        try {
            $data = array_merge($data, [
                'uuid' => $data['uuid'] ?? Str::uuid()->toString(),
                'password' => Hash::make($data['password']),
                'created_user' => Auth::user()->id,
            ]);

            if (empty($data['osa_code'])) {
                $data['osa_code'] = $this->generateCode();
            }

            $salesman = Salesman::create($data);
            DB::commit();
            return $salesman;
        } catch (Throwable $e) {
            DB::rollBack();
            $friendlyMessage = $e instanceof Error ? "Server error occurred." : "Something went wrong, please try again.";
            Log::error('Salesman creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
                'user' => Auth::id(),
            ]);
            throw new \Exception($friendlyMessage, 0, $e);
        }
    }

    public function updateByUuid(string $uuid, array $data): Salesman
    {
        DB::beginTransaction();
        try {
            $salesman = $this->findByUuid($uuid);
            $salesman->update($data);
            DB::commit();
            return $salesman;
        } catch (Throwable $e) {
            DB::rollBack();
            $friendlyMessage = $e instanceof Error ? "Server error occurred." : "Something went wrong, please try again.";

            Log::error('Salesman update failed', [
                'error' => $e->getMessage(),
                'uuid'  => $uuid,
                'data'  => $data,
            ]);

            throw new \Exception($friendlyMessage, 0, $e);
        }
    }

    public function deleteByUuid(string $uuid): bool
    {
        DB::beginTransaction();
        try {
            $salesman = $this->findByUuid($uuid);
            $salesman->delete();

            DB::commit();
            return true;
        } catch (Throwable $e) {
            DB::rollBack();
            $friendlyMessage = $e instanceof Error ? "Server error occurred." : "Something went wrong, please try again.";

            Log::error('Salesman delete failed', [
                'error' => $e->getMessage(),
                'uuid'  => $uuid,
            ]);

            throw new \Exception($friendlyMessage, 0, $e);
        }
    }

    public function export(string $format = 'xlsx', ?string $fromDate = null, ?string $toDate = null)
    {
        $allowedFormats = ['xlsx', 'csv'];

        if (!in_array($format, $allowedFormats)) {
            throw new \InvalidArgumentException("Unsupported export format: $format");
        }

        $fileName = 'salesmen_export_' . now()->format('Ymd_His') . '.' . $format;

        return Excel::download(new SalesmanExport($fromDate, $toDate), $fileName);
    }

    public function updateSalesmenStatus(array $salesmanIds, $status)
    {
        $updated = Salesman::whereIn('id', $salesmanIds)->update(['status' => $status]);
        return $updated > 0;
    }
}
