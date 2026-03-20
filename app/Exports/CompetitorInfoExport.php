<?php

namespace App\Exports;

use App\Models\CompetitorInfo;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CompetitorInfoExport implements FromCollection, WithHeadings
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Company Name',
            'Brand',
            'Merchandiser Name',
            'Item Name',
            'Price',
            'Promotion',
            'Notes',
            'Image',
        ];
    }
}
