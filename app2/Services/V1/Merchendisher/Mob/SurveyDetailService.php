<?php

namespace App\Services\V1\Merchendisher\Mob;

use App\Models\SurveyDetail;
use App\Models\SurveyHeader;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class SurveyDetailService
{
    public function create(array $data): SurveyDetail
    {
        $data['uuid'] = (string) Str::uuid();
        $data['created_user'] = auth()->id();

        return SurveyDetail::create($data);
    }

public function getSurveyDetails(array $data): array
{
    $perPage = $data['per_page'] ?? 10;

    // Get the survey header
    $header = SurveyHeader::query()
        ->where('id', $data['header_id'])
        ->whereNull('deleted_at')
        ->first();

    if (!$header) {
        return [
            'data' => [],
            'pagination' => [
                'current_page' => 0,
                'last_page' => 0,
                'per_page' => $perPage,
                'total' => 0,
            ],
        ];
    }

    // Paginate survey details
    $details = SurveyDetail::with('question:id,question')
        ->where('header_id', $header->id)
        ->paginate($perPage, ['id', 'header_id', 'question_id', 'answer']);

    return [
        'data' => $details->items(),
        'pagination' => [
            'current_page' => $details->currentPage(),
            'last_page' => $details->lastPage(),
            'per_page' => $details->perPage(),
            'total' => $details->total(),
        ],
    ];
}


    public function globalSearch(int $perPage = 10, ?string $searchTerm = null)
    {
        try {
            $query = SurveyDetail::select([
                    'id',
                    'header_id',
                    'question_id',
                    'answer',
                    'created_user',
                    'updated_user',
                    'deleted_user',
                ])
                ->with([
                    'question:id,question',
                    'header:id',
                    'createdUser:id,firstname,lastname,username',
                    'updatedUser:id,firstname,lastname,username',
                    'deletedUser:id,firstname,lastname,username',
                ]);

            if (!empty($searchTerm)) {
                $searchTerm = strtolower($searchTerm);
                $likeSearch = '%' . $searchTerm . '%';

                $query->where(function ($q) use ($likeSearch) {
                    // Direct fields
                    $q->orWhereRaw("CAST(id AS CHAR) LIKE ?", [$likeSearch])
                      ->orWhereRaw("CAST(header_id AS CHAR) LIKE ?", [$likeSearch])
                      ->orWhereRaw("CAST(question_id AS CHAR) LIKE ?", [$likeSearch])
                      ->orWhereRaw("LOWER(answer) LIKE ?", [$likeSearch]);

                    // Question
                    $q->orWhereHas('question', function ($sub) use ($likeSearch) {
                        $sub->whereRaw("LOWER(question) LIKE ?", [$likeSearch]);
                    });

                    // Created user
                    $q->orWhereHas('createdUser', function ($sub) use ($likeSearch) {
                        $sub->whereRaw("
                            LOWER(firstname) LIKE ? OR 
                            LOWER(lastname) LIKE ? OR 
                            LOWER(username) LIKE ?", 
                            [$likeSearch, $likeSearch, $likeSearch]
                        );
                    });

                    // Updated user
                    $q->orWhereHas('updatedUser', function ($sub) use ($likeSearch) {
                        $sub->whereRaw("
                            LOWER(firstname) LIKE ? OR 
                            LOWER(lastname) LIKE ? OR 
                            LOWER(username) LIKE ?", 
                            [$likeSearch, $likeSearch, $likeSearch]
                        );
                    });

                    // Deleted user
                    $q->orWhereHas('deletedUser', function ($sub) use ($likeSearch) {
                        $sub->whereRaw("
                            LOWER(firstname) LIKE ? OR 
                            LOWER(lastname) LIKE ? OR 
                            LOWER(username) LIKE ?", 
                            [$likeSearch, $likeSearch, $likeSearch]
                        );
                    });
                });
            }

            return $query->paginate($perPage);

        } catch (\Exception $e) {
            throw new \Exception("Failed to search survey details: " . $e->getMessage());
        }
    }
}
