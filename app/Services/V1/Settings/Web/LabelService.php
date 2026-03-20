<?php

namespace App\Services\V1\Settings\Web;

use App\Models\Label;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class LabelService
{
    /**
     * Fetch paginated labels with optional filters
     */
    public function listLabels(int $perPage = 50, array $filters = [])
    {
        try {
            $query = Label::query()
            ->where('status', 1)
            ->orderByDesc('id');

            foreach ($filters as $field => $value) {
                if (!empty($value)) {
                    if (in_array($field, ['name', 'osa_code'])) {
                        $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                    } else {
                        $query->where($field, $value);
                    }
                }
            }

            return $query->paginate($perPage);
        } catch (Throwable $e) {
            Log::error("Failed to fetch labels", [
                'filters' => $filters,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception("Failed to fetch labels. Please try again.");
        }
    }

    /**
     * Get a single label by ID
     */
    public function getById(int $id): Label
    {
        try {
            return Label::findOrFail($id);
        } catch (Throwable $e) {
            Log::error("Failed to fetch label", ['label_id' => $id, 'error' => $e->getMessage()]);
            throw new \Exception("Label not found.");
        }
    }

    /**
     * Create a new label
     */

    public function generateCode(): string
    {
        do {
            $last = Label::withTrashed()->latest('id')->first();
            $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $code = 'LB' . str_pad($next, 3, '0', STR_PAD_LEFT);
        } while (Label::withTrashed()->where('osa_code', $code)->exists());

        return $code;
    }

    public function create(array $data): Label
    {
        DB::beginTransaction();
        try {
            $data['uuid'] = Str::uuid()->toString();
            $data['osa_code'] = $data['osa_code'] ?? $this->generateCode();

            $label = Label::create($data);
            DB::commit();
            return $label;
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error("Failed to create label", [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception("Failed to create label. Please try again.");
        }
    }

    /**
     * Update an existing label
     */
    public function update(Label $label, array $data): Label
    {
        DB::beginTransaction();
        try {
            // if (isset($data['labels']) && is_array($data['labels'])) {
            //     $data['labels'] = implode(',', $data['labels']);
            // }

            $label->update($data);
            DB::commit();
            return $label;
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error("Failed to update label", [
                'label_id' => $label->id,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception("Failed to update label. Please try again.");
        }
    }

    /**
     * Delete a label
     */
    public function delete(Label $label): bool
    {
        DB::beginTransaction();
        try {
            $deleted = $label->delete();
            DB::commit();
            return $deleted;
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error("Failed to delete label", [
                'label_id' => $label->id,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception("Failed to delete label. Please try again.");
        }
    }
}
