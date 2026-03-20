<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RouteVisitDummyExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'customer type',
            'start date',
            'end date',
            'route',
            'customer',
            'sunday',
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
            'saturday',
        ];
    }

    public function array(): array
    {
        return [
            [
                'Field Customer',
                '01-01-2026',
                '31-01-2026',
                'RT0569',
                'AC00533012',
                'Yes',
                'No',
                'Yes',
                'Yes',
                'Yes',
                'No',
                'No',
            ]
        ];
    }
}
