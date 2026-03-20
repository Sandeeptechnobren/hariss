<?php

namespace App\Services\V1\Merchendisher\Mob;

use App\Models\PlanogramPost;
use App\Models\Planogram;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use App\Exports\PlanogramPostsExport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;

class PlanogramPostService
{
    public function store(array $data)
    {
        $data['uuid'] = Str::uuid()->toString();
        if (isset($data['before_image'])) {
            $data['before_image'] = $data['before_image']->store('planogram_posts', 'public');
            $data['before_image'] = Storage::url($data['before_image']);
        }

        if (isset($data['after_image'])) {
            $data['after_image'] = $data['after_image']->store('planogram_posts', 'public');
            $data['after_image'] = Storage::url($data['after_image']);
        }

        return PlanogramPost::create($data);
    }
    //  public function list(array $filters, int $perPage = 10)
    // {
    //     $query = PlanogramPost::query();

    //     if (!empty($filters['merchendisher_id'])) {
    //         $query->where('merchendisher_id', $filters['merchendisher_id']);
    //     }

    //     if (!empty($filters['planogram_id'])) {
    //         $query->where('planogram_id', $filters['planogram_id']);
    //     }

    //     if (!empty($filters['shelf_id'])) {
    //         $query->where('shelf_id', $filters['shelf_id']);
    //     }

    //     if (!empty($filters['customer_id'])) {
    //         $query->where('customer_id', $filters['customer_id']);
    //     }

    //     return $query->orderBy('id', 'desc')->paginate($perPage);
    // }
public function getByPlanogramUuid(string $planogramUuid, int $perPage = 10)
{
    return PlanogramPost::whereHas('planogram', function ($q) use ($planogramUuid) {
        $q->where('uuid', $planogramUuid);
    })
    ->orderBy('id', 'desc')
    ->paginate($perPage);
}

    public function generatePlanogramIdsFileForUser(int $merchandiserId): ?string
{
    $planogramIds = PlanogramPost::where('merchendisher_id', $merchandiserId)
        ->pluck('planogram_id')
        ->unique()
        ->values();

    if ($planogramIds->isEmpty()) {
        return null;
    }

    $existingPlanograms = Planogram::whereIn('id', $planogramIds)->get()->keyBy('id');

    $lines = [];

    foreach ($planogramIds as $id) {
        if (isset($existingPlanograms[$id])) {
            $planogram = $existingPlanograms[$id];

            $lines[] = "Planogram ID: {$planogram->id}";
            $lines[] = "Name: {$planogram->name}";
            $lines[] = "Valid From: " . ($planogram->valid_from ?? 'N/A');
            $lines[] = "Valid To: " . ($planogram->valid_to ?? 'N/A');
            $lines[] = "Status: " . ($planogram->status === 1 ? 'Active' : 'Inactive');
            $lines[] = "Created User: " . ($planogram->created_user ?? 'N/A');
            $lines[] = "Updated User: " . ($planogram->updated_user ?? 'N/A');
            $lines[] = "Deleted User: " . ($planogram->deleted_user ?? 'N/A');
            $lines[] = "Created At: " . ($planogram->created_at ?? 'N/A');
            $lines[] = "Updated At: " . ($planogram->updated_at ?? 'N/A');
            $lines[] = "Deleted At: " . ($planogram->deleted_at ?? 'N/A');
        } else {
            $lines[] = "Planogram ID: {$id} (Not found)";
        }

        $lines[] = str_repeat('-', 40);
    }

    $textContent = implode(PHP_EOL, $lines);
    $fileName = 'planogram_data_user_' . $merchandiserId . '_' . now()->format('Ymd_His') . '.txt';
    Storage::disk('public')->put($fileName, $textContent);

    return asset('storage/' . $fileName);
}
public function exportPlanogramPosts($startDate = null, $endDate = null, $format = 'csv')
{
    $query = PlanogramPost::with(['planogram', 'merchendisher', 'customer', 'shelf']);

    if ($startDate && $endDate) {
        $query->whereBetween('date', [$startDate, $endDate]);
    } elseif ($startDate) {
        $query->where('date', '>=', $startDate);
    } elseif ($endDate) {
        $query->where('date', '<=', $endDate);
    }

    $data = $query->get();

    $export = new PlanogramPostsExport($data);

    $fileName = 'planogram_posts_export_' . now()->format('Y_m_d_H_i_s');

    if (in_array(strtolower($format), ['xlsx', 'excel'])) {
        $fileName .= '.xlsx';
        return Excel::download($export, $fileName, ExcelFormat::XLSX);
    }
    $fileName .= '.csv';
    return Excel::download($export, $fileName, ExcelFormat::CSV);
}
}