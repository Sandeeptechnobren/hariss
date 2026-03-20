<?php

namespace App\Services\V1\Settings\Web;

use App\Models\ServiceType;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
use SplTempFileObject;
use Exception;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ServiceTypeService
{
    public function all(int $perPage = 10, array $filters = [])
    {
        $query = ServiceType::query()
            ->orderByDesc('id');

        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                if (in_array($field, ['code', 'name', 'status'])) {
                    $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                } else {
                    $query->where($field, $value);
                }
            }
        }

        return $query->paginate($perPage);
    }

    public function generateCode(): string
    {
        do {
            $last = ServiceType::withTrashed()->latest('id')->first();
            $next = $last ? ((int) preg_replace('/\D/', '', $last->code)) + 1 : 1;
            $code = 'ST' . str_pad($next, 3, '0', STR_PAD_LEFT);
        } while (ServiceType::withTrashed()->where('code', $code)->exists());

        return $code;
    }

    public function create(array $data): ServiceType
    {
        try {
            $data['created_user'] = Auth::id();
            $data['updated_user'] = Auth::id();
            $data['code'] = $this->generateCode();

            if (empty($data['uuid'])) {
                $data['uuid'] = Str::uuid()->toString();
            }

            return ServiceType::create($data);
        } catch (Exception $e) {
            throw new Exception("ServiceType creation failed: " . $e->getMessage());
        }
    }


    public function findByUuid(string $uuid): ?ServiceType
    {
        return ServiceType::withTrashed()->where('uuid', $uuid)->firstOrFail();
    }

    public function updateByUuid(string $uuid, array $data): ServiceType
    {
        try {
            $serviceType = $this->findByUuid($uuid);

            $data['updated_user'] = Auth::id();

            if (!empty($data['code']) && $data['code'] !== $serviceType->code) {
                if (ServiceType::withTrashed()->where('code', $data['code'])->exists()) {
                    throw new Exception("The code '{$data['code']}' already exists.");
                }
            }

            $serviceType->update($data);

            return $serviceType;
        } catch (Exception $e) {
            throw new Exception("ServiceType update failed: " . $e->getMessage());
        }
    }

    public function deleteByUuid(string $uuid): bool
    {
        $serviceType = ServiceType::withTrashed()->where('uuid', $uuid)->first();

        if (!$serviceType) {
            throw new \Exception("ServiceType with UUID {$uuid} not found.");
        }

        try {
            $serviceType->delete();
            return true;
        } catch (\Exception $e) {
            throw new \Exception("ServiceType delete failed: " . $e->getMessage());
        }
    }

    public function exportToCsv(): BinaryFileResponse
    {
        $directory = storage_path('app/exports');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $fileName = 'service_types_' . now()->format('Ymd_His') . '.csv';
        $filePath = $directory . DIRECTORY_SEPARATOR . $fileName;

        $csv = Writer::createFromPath($filePath, 'w+');

        $csv->insertOne([chr(0xEF) . chr(0xBB) . chr(0xBF)]);

        $csv->insertOne(['ID', 'UUID', 'Code', 'Name', 'Status', 'Created At', 'Updated At']);

        $types = ServiceType::all();
        foreach ($types as $type) {
            $csv->insertOne([
                $type->id,
                $type->uuid,
                $type->code,
                $type->name,
                $type->status,
                $type->created_at,
                $type->updated_at,
            ]);
        }
        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
