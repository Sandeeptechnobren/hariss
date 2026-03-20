<?php

namespace App\Services\V1\Settings\Web;

use App\Models\Menu;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class MenuService
{

public function all(int $perPage = 50, array $filters = [])
    {
        $isDropdown = isset($filters['dropdown']) &&
            filter_var($filters['dropdown'], FILTER_VALIDATE_BOOLEAN);
        if ($isDropdown) {
            $query = Menu::query()
                ->select('id', 'name', 'osa_code', 'status')
                ->where('status', 1)
                ->orderBy('name');
            foreach ($filters as $field => $value) {
                if (!empty($value) && !in_array($field, ['dropdown', 'limit'])) {
                    if ($field === 'name') {
                        $query->whereRaw(
                            "LOWER(name) LIKE ?",
                            ['%' . strtolower($value) . '%']
                        );
                    }
                }
            }
            return $query->get();
        }
        $query = Menu::query()->orderByDesc('id');
        // foreach ($filters as $field => $value) {
        //     if (!empty($value) && $field !== 'dropdown') {
        //         if ($field === 'name') {
        //             $query->whereRaw(
        //                 "LOWER(name) LIKE ?",
        //                 ['%' . strtolower($value) . '%']
        //             );
        //         } else {
        //             $query->where($field, $value);
        //         }
        //     }
        // }
        return $query->paginate($perPage);
    }

public function findByUuid(string $uuid): ?Menu
    {
        return Menu::where('uuid', $uuid)->first();
    }

public function generateCode(): string
    {
        do {
            $last = Menu::withTrashed()->latest('id')->first();
            $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $code = 'M' . str_pad($next, 3, '0', STR_PAD_LEFT);
        } while (Menu::withTrashed()->where('osa_code', $code)->exists());

        return $code;
    }

    public function create(array $data): Menu
    {
        DB::beginTransaction();
        try {
            $data['uuid'] = Str::uuid()->toString();
            $data['osa_code'] = $data['osa_code'] ?? $this->generateCode();

            $menu = Menu::create($data);

            DB::commit();
            return $menu;
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Menu creation failed', ['error' => $e->getMessage(), 'data' => $data]);
            throw new \Exception('Failed to create menu: ' . $e->getMessage());
        }
    }

    public function updateByUuid(string $uuid, array $data): Menu
    {
        $menu = $this->findByUuid($uuid);
        if (!$menu) {
            throw new \Exception("Menu not found or invalid UUID: {$uuid}");
        }

        DB::beginTransaction();
        try {
            $menu->update($data);
            DB::commit();
            return $menu;
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Menu update failed', ['error' => $e->getMessage(), 'uuid' => $uuid, 'data' => $data]);
            throw new \Exception('Failed to update menu: ' . $e->getMessage());
        }
    }

    public function deleteByUuid(string $uuid): bool
    {
        DB::beginTransaction();
        try {
            $menu = $this->findByUuid($uuid);
            if (!$menu) {
                throw new \Exception("Menu not found or invalid UUID: {$uuid}");
            }

            $menu->delete();

            DB::commit();
            return true;
        } catch (Throwable $e) {
            DB::rollBack();
            $friendlyMessage = $e instanceof \Error ? "Server error occurred." : "Something went wrong, please try again.";

            Log::error('Menu delete failed', [
                'error' => $e->getMessage(),
                'uuid'  => $uuid,
            ]);

            throw new \Exception($friendlyMessage, 0, $e);
        }
    }

    public function globalSearch(int $perPage = 10, ?string $searchTerm = null)
    {
        try {
            $query = Menu::with([
                'created_user:id,firstname,lastname,username',
                'updated_user:id,firstname,lastname,username',
            ]);

            if (!empty($searchTerm)) {
                $searchTerm = strtolower($searchTerm);
                $likeSearch = '%' . $searchTerm . '%';

                $query->where(function ($q) use ($likeSearch) {
                    $q->orWhereRaw("LOWER(name) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(osa_code) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(url) LIKE ?", [$likeSearch]);
                });
            }

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            Log::error("Menu global search failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'searchTerm' => $searchTerm,
            ]);
            throw new \Exception("Failed to search menus: " . $e->getMessage());
        }
    }
}
