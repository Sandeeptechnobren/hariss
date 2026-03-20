<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use App\Models\Survey;

class SurveyImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Survey([
            'survey_name' => $row['survey_name'],
            'start_date'  => $this->formatDate($row['start_date']),
            'end_date'    => $this->formatDate($row['end_date']),
            'survey_code' => $row['survey_code'] ?? $this->generateSurveyCode(),
        ]);
    }

    private function formatDate($date)
    {
        if (is_numeric($date)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date)->format('Y-m-d');
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function generateSurveyCode()
    {
        return 'SURV' . rand(1000, 9999);
    }
}
