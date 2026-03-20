<?php

namespace App\Services\V1\Settings\Web;

use App\Models\ProjectList;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProjectListService
{
public function getAll(
    array $filters = [],
    int $perPage = 50,
    bool $dropdown = false
) {
    $query = ProjectList::query()
        ->select([
            'id',
            'uuid',
            'osa_code',
            'name',
            'salesman_type_id',
            'status'
        ])
        ->with([
            'salesmanType:id,salesman_type_code,salesman_type_name'
        ])
        ->orderByDesc('id');
    foreach ($filters as $field => $value) {
        if ($value !== null && $value !== '') {
            if ($field === 'status') {
                $query->where('status', $value);
            } else {
                $query->where($field, $value);
            }
        }
    }
    if (isset($filters['status'])) {
        $query->where('status', $filters['status']);
    }
    if ($dropdown) {
        $dropdownQuery = clone $query;
        return $dropdownQuery
            ->without('salesmanType')
            ->select('id', 'osa_code', 'name', 'status', 'salesman_type_id')
            ->orderBy('name')
            ->get();
    }
    return $query->paginate($perPage);
}

public function getByUuid(string $uuid)
    {
        $project = ProjectList::where('uuid', $uuid)->with('salesmanType')->first();
        if (!$project) {
            throw new ModelNotFoundException("Project not found");
        }
        return $project;
    }
public function create(array $data): ProjectList
    {
        return DB::transaction(function () use ($data) {
            $data['uuid'] = Str::uuid();
            $data['osa_code'] = $this->generateOsaCode();
            $data['created_user'] = auth()->id();

            return ProjectList::create($data);
        });
    }

public function update(string $uuid, array $data): ProjectList
    {
        $data['updated_user'] = auth()->id();
        $project = ProjectList::where('uuid', $uuid)->firstOrFail();
        $project->update($data);
        return $project;
    }
public function delete(string $uuid): bool
    {
        $project = ProjectList::where('uuid', $uuid)->firstOrFail();
        $project->update([
            'deleted_user' => auth()->id(),
            'deleted_at' => now(),
        ]);
        return $project->delete();
    }
private function generateOsaCode(): string
    {
        $last = ProjectList::orderByDesc('id')->value('osa_code');
        if ($last) {
            $number = (int) substr($last, 3);
            $next = $number + 1;
        } else {
            $next = 1;
        }
        return 'SPL' . str_pad($next, 3, '0', STR_PAD_LEFT);
    }
}
