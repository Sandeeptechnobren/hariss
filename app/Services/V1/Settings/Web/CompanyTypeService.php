<?php

namespace App\Services\V1\Settings\Web;

use App\Models\CompanyType;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Exception;

class CompanyTypeService
{
    // public function all(int $perPage = 50, array $filters = [])
    // {
    //     $query = CompanyType::query()
    //         ->orderByDesc('id');
    //     foreach ($filters as $field => $value) {
    //         if (!empty($value)) {
    //             if (in_array($field, ['code', 'name'])) {
    //                 $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
    //             } else {
    //                 $query->where($field, $value);
    //             }
    //         }
    //     }

    //     return $query->paginate($perPage);
    // }

    public function all(int $perPage = 50, array $filters = [])
    {
        /**
         * 🔹 DROPDOWN MODE
         */
        if (!empty($filters['dropdown']) && $filters['dropdown'] === true) {
            return CompanyType::query()
                ->select([
                    'id',
                    'name',
                    'code',
                    'status'
                ])
                ->where('status', 1)
                ->orderBy('name')
                ->get();
        }

        /**
         * 🔹 NORMAL LIST MODE
         */
        $query = CompanyType::query()
            ->orderByDesc('id');

        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                if (in_array($field, ['code', 'name'])) {
                    $query->whereRaw(
                        "LOWER({$field}) LIKE ?",
                        ['%' . strtolower($value) . '%']
                    );
                } elseif ($field !== 'dropdown') {
                    $query->where($field, $value);
                }
            }
        }

        return $query->paginate($perPage);
    }


    public function generateCode(): string
    {
        do {
            $last = CompanyType::withTrashed()->latest('id')->first();
            $next = $last ? ((int) preg_replace('/\D/', '', $last->code)) + 1 : 1;
            $code = 'CT' . str_pad($next, 3, '0', STR_PAD_LEFT);
        } while (CompanyType::withTrashed()->where('code', $code)->exists());

        return $code;
    }

    public function create(array $data): CompanyType
    {
        try {
            $data['created_user'] = Auth::id();
            $data['updated_user'] = Auth::id();
            $data['code'] = $this->generateCode();

            if (empty($data['uuid'])) {
                $data['uuid'] = Str::uuid()->toString();
            }

            return CompanyType::create($data);
        } catch (Exception $e) {
            throw new Exception("CompanyType creation failed: " . $e->getMessage());
        }
    }
    // public function create(array $data): CompanyType
    // {
    //     try {
    //         $data['created_user'] = Auth::id();
    //         $data['updated_user'] = Auth::id();

    //         if (empty($data['code'])) {
    //             do {
    //                 $last = CompanyType::withTrashed()->latest('id')->first();
    //                 $next = $last ? ((int) preg_replace('/\D/', '', $last->code)) + 1 : 1;
    //                 $code = 'CT' . str_pad($next, 3, '0', STR_PAD_LEFT);
    //             } while (CompanyType::withTrashed()->where('code', $code)->exists());

    //             $data['code'] = $code;
    //         } else {
    //             if (CompanyType::withTrashed()->where('code', $data['code'])->exists()) {
    //                 throw new Exception("The code '{$data['code']}' already exists.");
    //             }
    //         }

    //         if (empty($data['uuid'])) {
    //             $data['uuid'] = Str::uuid()->toString();
    //         }

    //         return CompanyType::create($data);
    //     } catch (Exception $e) {
    //         throw new Exception("CompanyType creation failed: " . $e->getMessage());
    //     }
    // }

    public function find(string $uuid): ?CompanyType
    {
        return CompanyType::where('uuid', $uuid)->firstOrFail();
    }

    public function updateByUuid(string $uuid, array $data): CompanyType
    {
        try {
            $companyType = CompanyType::withTrashed()->where('uuid', $uuid)->firstOrFail();

            $data['updated_user'] = Auth::id();

            if (!empty($data['code']) && $data['code'] !== $companyType->code) {
                if (CompanyType::withTrashed()->where('code', $data['code'])->exists()) {
                    throw new Exception("The code '{$data['code']}' already exists.");
                }
            }

            $companyType->update($data);

            return $companyType;
        } catch (Exception $e) {
            throw new Exception("CompanyType update failed: " . $e->getMessage());
        }
    }

    public function deleteByUuid(string $uuid): bool
    {
        $companyType = CompanyType::withTrashed()->where('uuid', $uuid)->first();

        if (!$companyType) {
            throw new \Exception("CompanyType with UUID {$uuid} not found.");
        }

        try {
            $companyType->delete();
            return true;
        } catch (\Exception $e) {
            throw new \Exception("CompanyType delete failed: " . $e->getMessage());
        }
    }
}
