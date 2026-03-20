<?php

namespace App\Services\V1\Merchendisher\Mob;

use App\Models\SurveyHeader;
use App\Models\Survey;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str; 
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;


class SurveyHeaderService
{

public function all($perPage = 10, $search = null)
{
    $query = SurveyHeader::with(['survey']);

    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('id', 'like', "%{$search}%")
              ->orWhere('answerer_name', 'like', "%{$search}%")
              ->orWhere('date', 'like', "%{$search}%")
              ->orWhereHas('survey', function ($sq) use ($search) {
                  $sq->where('name', 'like', "%{$search}%"); 
              })
              ->orWhereHas('merchandiser', function ($mq) use ($search) {
                  $mq->where('name', 'like', "%{$search}%"); 
              });
        });
    }

    return $query->paginate($perPage);
}
public function getBySurveyUuid(string $surveyUuid)
    {
        $survey = Survey::where('uuid', $surveyUuid)->firstOrFail();
        return SurveyHeader::with(['survey', 'merchandiser', 'surveyDetails'])
            ->where('survey_id', $survey->id)
            ->get();
    }
      public function create(array $data): SurveyHeader
    {
        // Add system fields
        $data['created_user'] = Auth::id();
        $data['updated_user'] = Auth::id();
        $data['uuid'] = (string) Str::uuid();

        return SurveyHeader::create($data);
    }

    public function update($id, array $data)
    {
        $surveyHeader = $this->getById($id);
        $data['updated_user'] = Auth::id(); 
        $surveyHeader->update($data);
        return $surveyHeader;
    }

    public function delete($id)
    {
        $surveyHeader = $this->getById($id);
        $surveyHeader->deleted_user = Auth::id(); 
        $surveyHeader->save();
        $surveyHeader->delete();
        return true;
    }
  public function exportSurveyDataForAuthenticatedMerchandiser(): string
{
    $userId = Auth::id();

    // Step 1: Get all survey IDs from SurveyHeader for this merchandiser
    $surveyIds = SurveyHeader::where('merchandiser_id', $userId)
        ->pluck('survey_id')
        ->toArray();

    // Step 2: Get full survey records from surveys table
    $existingSurveys = Survey::whereIn('id', $surveyIds)->get()->keyBy('id');

    // Step 3: Build final plain text content
    $lines = [];

    foreach ($surveyIds as $id) {
        if (isset($existingSurveys[$id])) {
            $survey = $existingSurveys[$id];

            $lines[] = "Survey ID: {$survey->id}";
            $lines[] = "Survey Name: {$survey->survey_name}";
            $lines[] = "Start Date: " . ($survey->start_date ?? 'N/A');
            $lines[] = "End Date: " . ($survey->end_date ?? 'N/A');
            $lines[] = "Created User: " . ($survey->created_user ?? 'N/A');
            $lines[] = "Updated User: " . ($survey->updated_user ?? 'N/A');
            $lines[] = "Deleted User: " . ($survey->deleted_user ?? 'N/A');
            $lines[] = "Created At: " . ($survey->created_at ?? 'N/A');
            $lines[] = "Updated At: " . ($survey->updated_at ?? 'N/A');
            $lines[] = "Deleted At: " . ($survey->deleted_at ?? 'N/A');
            $lines[] = "Survey Code: " . ($survey->survey_code ?? 'N/A');
            $lines[] = "UUID: " . ($survey->uuid ?? 'N/A');
            $lines[] = "Status: " . ($survey->status === 1 ? 'Active' : 'Inactive');
        } else {
            $lines[] = "Survey ID: {$id} (Not found)";
        }

        $lines[] = str_repeat('-', 40);
    }

    // Step 4: Join lines into a single string
    $textContent = implode(PHP_EOL, $lines);

    // Step 5: Save to .txt file
    $fileName = 'survey_data_user_' . $userId . '_' . now()->format('Ymd_His') . '.txt';
    Storage::disk('public')->put($fileName, $textContent);

    // Step 6: Return public file URL
    return asset('storage/' . $fileName);
}
}