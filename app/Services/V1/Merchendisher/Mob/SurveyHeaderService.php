<?php

namespace App\Services\V1\Merchendisher\Mob;

use App\Models\SurveyHeader;
use App\Models\SurveyDetail;
use App\Models\Survey;
use App\Models\Salesman;
use App\Models\SalesmanWarehouseHistory;
use App\Models\AgentCustomer;
use App\Models\AcFridgeStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        return SurveyHeader::with(['survey', 'merchandiser', 'surveyDetails','surveyDetails.question'])
            ->where('survey_id', $survey->id)
            ->get();
    }
public function createSurvey(array $data)
    {
        return DB::transaction(function () use ($data) {
            $header = SurveyHeader::create([
                'uuid'             => Str::uuid(),
                'merchandiser_id'  => $data['merchandiser_id'],
                'date'             => $data['date'],
                'answerer_name'    => $data['answerer_name'] ?? null,
                'address'          => $data['address'] ?? null,
                'phone'            => $data['phone'] ?? null,
                'survey_id'        => $data['survey_id'],
            ]);
            $detailsData = [];
            foreach ($data['details'] as $item) {
                $detailsData[] = [
                    'uuid'        => Str::uuid(),
                    'header_id'   => $header->id,
                    'question_id' => $item['question_id'],
                    'answer'      => $item['answer'] ?? null,
                ];
            }
            SurveyDetail::insert($detailsData);
            return $header->load('details');
        });
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
    $surveyIds = SurveyHeader::where('merchandiser_id', $userId)
        ->pluck('survey_id')
        ->toArray();
    $existingSurveys = Survey::whereIn('id', $surveyIds)->get()->keyBy('id');
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
public function getBySalesman($salesmanId)
{
    $today = now()->toDateString();
    $salesman = Salesman::find($salesmanId);
    if (!$salesman) {
        return collect();
    }
    $baseQuery = Survey::query()
        ->whereIn('survey_type', [1, 2, 3])
        ->whereDate('end_date', '>=', $today);
    $fridgeIds = collect();
    if ($salesman->type == 6) {
        $warehouseId = SalesmanWarehouseHistory::where('salesman_id', $salesmanId)
            ->whereDate('requested_date', $today)
            ->value('warehouse_id');
        if ($warehouseId) {
            $customerIds = AgentCustomer::where('warehouse', $warehouseId)
                ->where('fridge', 1)
                ->pluck('id');
            if ($customerIds->isNotEmpty()) {
                $fridgeIds = AcFridgeStatus::whereIn('customer_id', $customerIds)
                    ->whereNull('remove_date')
                    ->pluck('fridge_id');
            }
        }
        if ($salesman->sub_type == 6) {
            $merchSurveys = (clone $baseQuery)
                ->where('survey_type', 1)
                ->whereRaw(
                    "',' || merchandisher_id || ',' LIKE ?",
                    ['%,' . $salesmanId . ',%']
                )
                ->get();
            $assetSurveys = collect();
            if ($fridgeIds->isNotEmpty()) {
                $assetSurveys = (clone $baseQuery)
                    ->where('survey_type', 3)
                    ->whereIn('asset_id', $fridgeIds)
                    ->get();
            }
            $defaultSurveys = (clone $baseQuery)
                ->where('survey_type', 2)
                ->get();
            return $merchSurveys
                ->merge($assetSurveys)
                ->merge($defaultSurveys)
                ->unique('id')
                ->values();
        }
        else {
            $assetSurveys = collect();
            if ($fridgeIds->isNotEmpty()) {
                $assetSurveys = (clone $baseQuery)
                    ->where('survey_type', 3)
                    ->whereIn('asset_id', $fridgeIds)
                    ->get();
            }
            $defaultSurveys = (clone $baseQuery)
                ->where('survey_type', 2)
                ->get();
            return $assetSurveys
                ->merge($defaultSurveys)
                ->unique('id')
                ->values();
        }
    }
    return $baseQuery->latest()->get();
}
}