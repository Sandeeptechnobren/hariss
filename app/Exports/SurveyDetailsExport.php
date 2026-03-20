<?php

namespace App\Exports;

use App\Models\SurveyDetail;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SurveyDetailsExport implements FromCollection, WithHeadings
{
    protected $headerId;

    // Accept header_id via constructor
    public function __construct(int $headerId = null)
    {
        $this->headerId = $headerId;
    }

    public function collection()
    {
        $details = SurveyDetail::with('question')
            ->when($this->headerId, fn($query) => $query->where('header_id', $this->headerId))
            ->get();

        $exportData = [];

        foreach ($details as $detail) {
            $exportData[] = [
                'ID'          => $detail->id,
                'Header ID'   => $detail->header_id,
                'Question ID' => $detail->question_id,
                'Question'    => $detail->question->question ?? 'N/A',
                'Answer'      => $detail->answer ?? 'N/A',
            ];
        }

        return collect($exportData);
    }

    public function headings(): array
    {
        return ['ID', 'Header ID', 'Question ID', 'Question', 'Answer'];
    }
}