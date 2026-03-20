<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ComplaintFeedbackExport implements FromCollection, WithHeadings
{
    protected $exportData;

    public function __construct($exportData)
    {
        $this->exportData = $exportData;
    }

    public function collection()
    {
        return collect($this->exportData);
    }

    public function headings(): array
    {
        return [
            'Complaint Title',
            'Merchendiser Name',
            'Item Name',
            'Type',
            'Complaint',
            'Created At',
        ];
    }
}
