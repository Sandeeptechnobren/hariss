<?php

namespace App\Services\V1\Merchendisher\Web;

use App\Models\PlanogramImage;
use App\Models\CompanyCustomer;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Helpers\SearchHelper;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;
use App\Http\Resources\V1\Merchendisher\Web\PlanogramImageResource;

class PlanogramImageService
{
    public function getAll(int $perPage = 10)
    {
          $search = request()->input('search');
        $query = PlanogramImage::with(['merchandiser','customer','shelf'])->latest();
        $query = SearchHelper::applySearch($query, $search, [
            'id',
            'uuid',
            'merchandiser_id',
            'shelf.shelf_name',
            'shelf_id',
            'merchandiser.name',
            'customer.business_name',
            'created_user.firstname',
            'updated_user.firstname',
        ]);

        return $query->paginate(request()->get('per_page', 10));
    }

    public function store($data, $file, $userId)
    {
        $customer = CompanyCustomer::findOrFail($data['customer_id']);
        $merchandiserIds = explode(',', $customer->merchandiser_ids);
        $path = $file->store('planogram_images', 'public');
        $url  = Storage::url($path);

        return PlanogramImage::create([
            'uuid'            => Str::uuid()->toString(),
            'customer_id'     => $data['customer_id'],
            'merchandiser_id' => $data['merchandiser_id'],
            'shelf_id'        => $data['shelf_id'],
            'image'           => $url,
        ]);
    }

    public function show($id)
    {
        return PlanogramImage::with(['customer'])->findOrFail($id);
    }

    public function update($id, $data, $file, $userId)
    {
        $planogramImage = PlanogramImage::findOrFail($id);
        $customer = CompanyCustomer::findOrFail($data['customer_id']);
        $merchandiserIds = explode(',', $customer->merchandiser_ids);

        if ($file) {
            $path = $file->store('planogram_images', 'public');
            $planogramImage->image = Storage::url($path);
        } else {
        $planogramImage->image = $data['image'] ?? $planogramImage->image;
    }

        $planogramImage->update([
            'customer_id'     => $data['customer_id'],
            'merchandiser_id' => $data['merchandiser_id'],
            'shelf_id'        => $data['shelf_id'],
            'image'           => $planogramImage->image,
        ]);

        return $planogramImage;
    }

    public function delete($id, $userId)
    {
        $planogramImage = PlanogramImage::findOrFail($id);
        $planogramImage->save();
        $planogramImage->delete();

        return true;
    }

     public function bulkUpload($file, $userId)
    {
          $results = [
        'success' => [],
        'failed'  => []
    ];

    $data = Excel::toCollection(null, $file)->first()->skip(1);

    $data->map(function ($row, $index) use (&$results) {
        try {
            $customerId     = $row[0];
            $merchandiserId = $row[1];
            $shelfId        = $row[2];
            $localImagePath = $row[3];

            if (!file_exists($localImagePath)) {
                throw new \Exception("Image file not found at path: {$localImagePath}");
            }

            $extension = pathinfo($localImagePath, PATHINFO_EXTENSION);
            $filename = Str::uuid() . '.' . $extension;
            $uploadedPath = Storage::disk('public')->putFileAs('planogram_images', new \Illuminate\Http\File($localImagePath), $filename);
            $imageUrl = Storage::url($uploadedPath); 

            $customer = CompanyCustomer::findOrFail($customerId);

            $planogramImage = PlanogramImage::create([
                'uuid'            => Str::uuid()->toString(),
                'customer_id'     => $customerId,
                'merchandiser_id' => $merchandiserId,
                'shelf_id'        => $shelfId,
                'image'           => $imageUrl,
            ]);

            $results['success'][] = [
                'row'     => $index + 2,
                'message' => 'Inserted successfully',
                'data'    => new PlanogramImageResource($planogramImage),
            ];

        } catch (\Exception $e) {
            $results['failed'][] = [
                'row'   => $index + 2,
                'error' => $e->getMessage(),
            ];
        }
    });

    return $results;
    }

     public function getFiltered($validFrom = null, $validTo = null)
    {
        $query = PlanogramImage::query();

        if ($validFrom && $validTo) {
            $query->whereBetween('created_at', [$validFrom, $validTo]);
        } elseif ($validFrom) {
            $query->whereDate('created_at', '>=', $validFrom);
        } elseif ($validTo) {
            $query->whereDate('created_at', '<=', $validTo);
        }

        return $query->orderBy('created_at', 'asc')->get();
    }
}
