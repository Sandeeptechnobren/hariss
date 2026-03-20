<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PlanogramPostsExport implements FromCollection, WithHeadings, WithMapping
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

    public function map($item): array
    {
        return [
            $item->planogram->name ?? '',
            $item->merchendisher->name ?? '',
            $item->date,
            $item->customer->business_name ?? '',
            $item->shelf->shelf_name ?? '',
            $item->before_image,
            $item->after_image,
        ];
    }

    public function headings(): array
    {
        return [
            'Planogram',
            'Merchendisher',
            'Date',
            'Customer',
            'Shelf',
            'Before Image',
            'After Image',
        ];
    }
}
