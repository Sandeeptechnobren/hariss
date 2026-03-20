<?php

namespace App\Services\V1\Settings\Web;

use App\Models\SubMenu;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SubMenuService
{
    public function getAll(array $filters = [], int $perPage = 150)
    {
        $query = SubMenu::with(['menu', 'parent', 'children'])->orderByDesc('id');
        foreach ($filters as $field => $value) { 
            if (!empty($value)) {
                if (in_array($field, ['name', 'url'])) {
                    $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                } else {
                    $query->where($field, $value);
                }
            }
        }

        return $query->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?SubMenu
    {
        if (!Str::isUuid($uuid)) {
            throw new \InvalidArgumentException("Invalid UUID format: {$uuid}");
        }

        return SubMenu::where('uuid', $uuid)->first();
    }

    public function generateCode(): string
    {
        do {
            $last = SubMenu::withTrashed()->latest('id')->first();
            $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $osa_code = 'SBM' . str_pad($next, 3, '0', STR_PAD_LEFT);
        } while (SubMenu::withTrashed()->where('osa_code', $osa_code)->exists());

        return $osa_code;
    }

    public function create(array $data): SubMenu
    {
        DB::beginTransaction();

        try {
            $data = array_merge($data, [
                'uuid' => $data['uuid'] ?? Str::uuid()->toString(),
                'osa_code' => $this->generateCode(),
            ]);

            $submenu = SubMenu::create($data);
            DB::commit();

            return $submenu;
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("SubMenu creation failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
                'user' => Auth::id(),
            ]);

            // Throw the actual error so you can see it in your response if needed
            throw new \Exception("SubMenu creation failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function updateByUuid(string $uuid, array $data): SubMenu
    {
        $submenu = $this->findByUuid($uuid);
        if (!$submenu) {
            throw new \Exception("SubMenu not found or invalid UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $submenu->update($data);
            DB::commit();

            return $submenu;
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("SubMenu update failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'uuid' => $uuid,
                'payload' => $data,
            ]);

            throw new \Exception("SubMenu update failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function deleteByUuid(string $uuid): void
    {
        $submenu = $this->findByUuid($uuid);
        if (!$submenu) {
            throw new \Exception("SubMenu not found or invalid UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $submenu->delete();
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("SubMenu delete failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'uuid' => $uuid,
            ]);

            throw new \Exception("SubMenu delete failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function globalSearch($perPage = 10, $searchTerm = null)
    {
        try {
            $query = SubMenu::with([
                'menu:id,osa_code,name',
                'createdBy:id,firstname,lastname,username',
                'updatedBy:id,firstname,lastname,username',
            ]);

            if (!empty($searchTerm)) {
                $searchTerm = strtolower($searchTerm);
                $likeSearch = '%' . $searchTerm . '%';

                $query->where(function ($q) use ($likeSearch) {
                    $q->orWhereRaw("LOWER(osa_code) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(name) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(url) LIKE ?", [$likeSearch]);
                });
            }

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            Log::error("SubMenu search failed", [
                'error' => $e->getMessage(),
                'search' => $searchTerm,
            ]);

            throw new \Exception("Failed to SubMenu Requests: " . $e->getMessage(), 0, $e);
        }
    }
}
