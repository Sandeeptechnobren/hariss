<?php

namespace App\Exports;

use App\Models\RouteVisit;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RouteVisitSingleExport implements FromCollection, WithHeadings
{
    protected $uuid;

    public function __construct($uuid = null)
    {
        $this->uuid = $uuid;
    }

    public function collection()
    {
        $query = RouteVisit::with('header')
            ->when($this->uuid, function ($q) {
                $q->whereHas('header', fn($h) => $h->where('uuid', $this->uuid));
            });

        return $query->get()->map(function ($visit) {
            return [
                $visit->header->osa_code ?? '',
                $visit->customer_id,
                $visit->customer_type,
                $visit->region,
                $visit->area,
                $visit->warehouse,
                $visit->route,
                $visit->days,
                $visit->from_date,
                $visit->to_date,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Header Code',
            'Customer ID',
            'Customer Type',
            'Region',
            'Area',
            'Warehouse',
            'Route',
            'Days',
            'From Date',
            'To Date',
        ];
    }
}
