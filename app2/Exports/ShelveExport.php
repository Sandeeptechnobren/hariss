<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ShelveExport implements FromCollection, WithHeadings
{
    protected $data;
    private $fileName;

    public function __construct($data, $fileName = 'shelves_export.xlsx')
    {
        $this->data = $data;
        $this->fileName = $fileName;
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return [
            'Shelf Name',
            'Height',
            'Width',
            'Depth',
            'Valid From',
            'Valid To',
            'Customer Name',
            'Merchandiser Name',
            'Code',
        ];
    }

    public function fileName(): string
    {
        return $this->fileName;
    }
}