<?php

namespace App\Services\V1\Merchendisher\Mob;

use App\Models\CampaignInformation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Exports\CampaignInformationExport;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\SearchHelper;

class CampaignInformationService
{
  public function store(array $data): CampaignInformation
{
    $images = [];

    if (isset($data['image_1']) && $data['image_1'] instanceof UploadedFile) {
        $path = $data['image_1']->store('campaign_images', 'public');
        $images['image_1'] = '/storage/' . $path;
    }

    if (isset($data['image_2']) && $data['image_2'] instanceof UploadedFile) {
        $path = $data['image_2']->store('campaign_images', 'public');
        $images['image_2'] = '/storage/' . $path;
    }

    $data['images'] = $images;

    return CampaignInformation::create([
        'code'  => $data['code'] ?? null,
        'date-time' => $data['date_time'] ?? null,
        'merchandiser_id' => $data['merchandiser_id'],
        'customer_id' => $data['customer_id'],
        'feedback' => $data['feedback'] ?? '',
        'images' => $images,
        'name' => $data['name'] ?? '',
    ]);
}

public function getAll(Request $request)
{
    $query = CampaignInformation::with(['merchandiser', 'customer']);

    if ($request->filled('merchandiser_id')) {
        $query->where('merchandiser_id', $request->merchandiser_id);
    }

    if ($request->filled('customer_id')) {
        $query->where('customer_id', $request->customer_id);
    }

    if ($request->filled('date')) {
        $query->whereDate('created_at', $request->date);
    }
    $search = $request->input('search');
    $query = SearchHelper::applySearch($query, $search, [
        'id',
        'uuid',
        'code',
        'merchandiser_id',
        'customer_id',
        'merchandiser.name',
        'customer.business_name',
        'created_user.name',
        'updated_user.name',
    ]);

    return $query->latest()->paginate(50);
}

//  public function export($startDate, $endDate, $format = 'csv')
// {
//     $export = new CampaignInformationExport($startDate, $endDate);
//     $fileName = 'campaign_information_' . now()->format('Ymd_His') . '.' . $format;

//     return Excel::download(
//         $export,
//         $fileName,
//         $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
//     );
// }

public function export($startDate, $endDate, $format = 'csv', $filters = [])
{
    $export = new CampaignInformationExport(
        startDate:      $startDate,
        endDate:        $endDate,
        searchTerm:     $filters['search']    ?? null,
        merchandiserId: $filters['merchandiser_id'] ?? null,
        customerId:     $filters['customer_id']     ?? null,
        date:           $filters['date']     ?? null,
    );

    $fileName = 'campaign_information_' . now()->format('Ymd_His') . '.' . $format;
    $path     = 'campaign_exports/' . $fileName;

    if ($format === 'csv') {
        Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
    } else {
        Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
    }

    $downloadUrl = rtrim(config('app.url'), '/') . '/storage/app/public/' . $path;

    return response()->json([
        'status'       => 'success',
        'message'      => 'Export file generated successfully',
        'download_url' => $downloadUrl,
    ]);
}
}