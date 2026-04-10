<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TargetCommisionaDummyCSV implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'Item Code',
            'Item Name',
            'Category',
            'Month',
            'Year',
            'Warehouse Code example(WH0001)'
        ];
    }

    public function array(): array
    {
        return [
            [
                '150001',
                'Riham Cola 320ml x 12',
                'Cat',
                '3',
                '2026',
                'Quantity(500)',
            ]
        ];
    }
}
