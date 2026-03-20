<?php

namespace App\Services\V1\Merchendisher\Web;

use App\Models\ComplaintFeedback;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Helpers\SearchHelper;
use Illuminate\Support\Carbon;

class ComplaintFeedbackService
{
    public function getByUuid(string $uuid): ?ComplaintFeedback
    {
          return ComplaintFeedback::with(['merchendiser', 'item']) 
                ->where('uuid', $uuid)
                ->where('created_user', Auth::id())
                ->first();
    }

// public function getAll()
// {
//     return ComplaintFeedback::with(['merchendiser', 'item']) 
//         ->where('uuid', $uuid)
//         ->where('created_user', Auth::id())
//         ->first();
// }

public function getAll()
{
    $search = request()->input('search');
    $query = ComplaintFeedback::with(['merchendiser','item'])->latest();
    $query = SearchHelper::applySearch($query, $search, [
        'id',
        'complaint_title',
        'complaint',
        'uuid',
        'complaint_code',
        'merchendiser.name',
        'item.name',
        'created_user.firstname',
        'updated_user.firstname',
        
    ]);
    return $query->paginate(request()->get('per_page', 50));
}

public function createComplaint(array $data): ComplaintFeedback
{
    if (empty($data['uuid'])) {
        $data['uuid'] = Str::uuid()->toString();
    }
   if (empty($data['complaint_code'])) {
        $data['complaint_code'] = $this->generateComplaintCode();
    }
    if (!empty($data['image']) && is_array($data['image'])) {
        $uploadedImagePaths = [];

        foreach ($data['image'] as $imageFile) {
            if ($imageFile && $imageFile->isValid()) {
                $path = $imageFile->store('complaint_feedback', 'public');
                $uploadedImagePaths[] = '/storage/' . $path;
            }
        }
        $data['image'] = $uploadedImagePaths;
    }
    return ComplaintFeedback::create($data);
}

 public function exportFeedbacks($startDate, $endDate)
    {
        $feedbacks = ComplaintFeedback::with(['merchendiser', 'item'])
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ]);
            })
            ->get();

        return $feedbacks->map(function ($feedback) {
            return [
                'complaint_title' => $feedback->complaint_title,
                'merchendiser_name' => $feedback->merchendiser->name ?? null,
                'item_name' => $feedback->item->name ?? null,
                'type' => $feedback->type,
                'complaint' => $feedback->complaint,

            ];
        });
    }
protected function generateComplaintCode(): string
{
    do {
        $randomNumber = random_int(1, 999);
        $code = 'CMP' . str_pad($randomNumber, 3, '0', STR_PAD_LEFT);
    } while (ComplaintFeedback::where('complaint_code', $code)->exists());
    return $code;
}

}