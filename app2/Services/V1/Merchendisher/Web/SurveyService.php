<?php

namespace App\Services\V1\Merchendisher\Web;

use App\Models\Survey;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class SurveyService
{
    /**
     * Get all surveys
     */

public function list(int $perPage = 10)
{
    return Survey::where('created_user', Auth::id()) 
                 ->orderBy('created_at', 'asc')
                 ->paginate($perPage);
}

    /**
     * Create a new survey
     */
    public function create(array $data): Survey
    {
         if (empty($data['survey_code'])) {
        $data['survey_code'] = $this->generateSurveyCode();
    }

        if (empty($data['uuid'])) {
            $data['uuid'] = Str::uuid()->toString();
        }

        if (isset($data['status'])) {
            $data['status'] = $this->mapStatus($data['status']);
        } else {
            $data['status'] = '1';
        }

        return Survey::create($data);
    }


    /**
     * Get survey by ID
     */
    public function getById(int $id): Survey
    {
        return Survey::findOrFail($id);
    }

    /**
     * Update survey (survey_code cannot be updated)
     */
    public function update(int $id, array $data): Survey
    {
        $survey = $this->getById($id);

        unset($data['survey_code'], $data['uuid']);

        if (isset($data['status'])) {
            $data['status'] = $this->mapStatus($data['status']);
        }

        $survey->update($data);

        return $survey;
    }

    /**
     * Soft delete a survey
     */
    public function delete(int $id): void
    {
        $survey = $this->getById($id);
        $survey->delete();
    }

    private function mapStatus(string $status): string
    {
        return strtolower($status) === 'active' ? '1' : '0';
    }
    public function globalSearch(?string $searchTerm = null, int $perPage = 10)
    {
        $query = Survey::query()
            ->with([
                'createdUser:id,firstname,lastname,username',
                'updatedUser:id,firstname,lastname,username',
                'deletedUser:id,firstname,lastname,username'
            ]);

        if ($searchTerm) {
            $likeSearch = '%' . strtolower($searchTerm) . '%';

           $query->where(function ($q) use ($likeSearch, $searchTerm) {
            $q->whereRaw('LOWER(survey_code) LIKE ?', [$likeSearch])
            ->orWhereRaw('LOWER(survey_name) LIKE ?', [$likeSearch])
            ->orWhere(function($sub) use ($searchTerm, $likeSearch) {
                if (is_numeric($searchTerm)) {
                    $sub->where('status', $searchTerm);
                } else {
                    $sub->whereRaw('LOWER(status::text) LIKE ?', [$likeSearch]);
                }
            })
            ->orWhereRaw('CAST(start_date AS TEXT) LIKE ?', [$likeSearch])
            ->orWhereRaw('CAST(end_date AS TEXT) LIKE ?', [$likeSearch])
                ->orWhereHas('createdUser', function ($sub) use ($likeSearch) {
                    $sub->where(function ($s) use ($likeSearch) {
                        $s->whereRaw('LOWER(firstname) LIKE ?', [$likeSearch])
                            ->orWhereRaw('LOWER(lastname) LIKE ?', [$likeSearch]);
                    });
                })
                ->orWhereHas('updatedUser', function ($sub) use ($likeSearch) {
                    $sub->where(function ($s) use ($likeSearch) {
                        $s->whereRaw('LOWER(firstname) LIKE ?', [$likeSearch])
                            ->orWhereRaw('LOWER(lastname) LIKE ?', [$likeSearch]);
                    });
                })
                ->orWhereHas('deletedUser', function ($sub) use ($likeSearch) {
                    $sub->where(function ($s) use ($likeSearch) {
                        $s->whereRaw('LOWER(firstname) LIKE ?', [$likeSearch])
                            ->orWhereRaw('LOWER(lastname) LIKE ?', [$likeSearch]);
                    });
                });
            });
        }

        return $query->orderBy('created_at', 'desc')
                    ->paginate($perPage);
    }

    
 public function importSurveys($file)
{
    $rows = Excel::toArray([], $file)[0];

    array_shift($rows); 

    array_map(function ($row) {
        $surveyName = $row[0] ?? null;
        $startDate  = $row[1] ?? null;
        $endDate    = $row[2] ?? null;
        $surveyCode = $row[3] ?? null;

        if (empty($surveyCode)) {
            $surveyCode = $this->generateSurveyCode();
        }

        $surveyData = [
            'survey_name' => $surveyName,
            'start_date'  => $startDate,
            'end_date'    => $endDate,
            'survey_code' => $surveyCode,
        ];

        Survey::updateOrCreate(
            ['survey_code' => $surveyData['survey_code']],
            $surveyData
        );
    }, $rows);
}

private function generateSurveyCode(): string
{
    $maxAttempts = 3;
    $attempts = 0;

    do {
        $code = 'SURV-' . strtoupper(substr(uniqid(), 0, 4));
        $exists = Survey::where('survey_code', $code)->exists();
        $attempts++;
    } while ($exists && $attempts < $maxAttempts);

    if ($exists) {
        $code = 'SURV-' . strtoupper(Str::random(5));
    }

    return $code;
}

     public function getFiltered($validFrom = null, $validTo = null)
    {
        $query = Survey::query();

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