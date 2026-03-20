<?php

namespace App\Services\V1\Merchendisher\Mob;

use App\Models\ComplaintFeedback;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Exception;

class ComplaintFeedbackService
{
public function createComplaint(array $data): ComplaintFeedback
{
    try {
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
    } catch (Exception $e) {
        Log::error('Complaint creation failed', [
            'error' => $e->getMessage(),
            'data'  => $data
        ]);
        throw $e;
    }
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