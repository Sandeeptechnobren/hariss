<?php

namespace App\Services\V1\Merchendisher\Web;

use App\Models\CompetitorInfo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\V1\Merchendisher\Web\CompetitorInfoRequest;
use App\Models\Salesman;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Helpers\SearchHelper;

class CompetitorInfoService
{
   public function getByUuid(string $uuid): ?CompetitorInfo
    {
        return CompetitorInfo::where('uuid', $uuid)->first();
    }

        public function getAll()
    {
      $search = request()->input('search');
      $query = CompetitorInfo::with(['merchandiser'])->latest();
      $query = SearchHelper::applySearch($query, $search, [
        'id',
        'uuid',
        'company_name',
        'brand',
        'item_name',
        'price',
        'promotion',
        'merchandiser.name',
        'code',
        'created_user.firstname',
        'updated_user.firstname',
        
    ]);
    return $query->paginate(request()->get('per_page', 50));
    }

public function store(CompetitorInfoRequest $request): array
{
    $data = $request->validated();

    $images = [];

    $storeImage = function(?UploadedFile $file) {
        if (!$file) return null;

        $filename = $file->hashName(); 
        $file->move(public_path('competitor_images'), $filename);
        return 'competitor_images/' . $filename;
    };

    if ($request->hasFile('image1') && $request->file('image1') instanceof UploadedFile) {
        $images['image1'] = '/' . $storeImage($request->file('image1'));
    }

    if ($request->hasFile('image2') && $request->file('image2') instanceof UploadedFile) {
        $images['image2'] = '/' . $storeImage($request->file('image2'));
    }

    $data['image'] = $images;

    $competitorInfo = CompetitorInfo::create($data);

    return [
        'success' => true,
        'message' => 'Competitor info created successfully.',
        'data' => $competitorInfo,
    ];
}

public function getFilteredData($startDate = null, $endDate = null)
{
    $query = CompetitorInfo::select(
            'competitor_infos.company_name',
            'competitor_infos.brand',
            'salesman.name as merchandiser_name',
            'competitor_infos.item_name',
            'competitor_infos.price',
            'competitor_infos.promotion',
            'competitor_infos.notes',
            'competitor_infos.image',
        )
        ->join('salesman', 'competitor_infos.merchendiser_id', '=', 'salesman.id');

    if ($startDate && $endDate) {
        $query->whereBetween('competitor_infos.created_at', [$startDate, $endDate]);
    }

    return $query->get()
        ->map(function ($item) {
            $item->image = json_encode($item->image);
            return $item;
        });
}
}