<?php

namespace App\Exports;

use App\Models\RouteVisit;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RouteVisitExport implements FromCollection, WithHeadings
{
    protected ?string $fromDate;
    protected ?string $toDate;
    protected ?string $headerUuid;

    public function __construct(
        ?string $fromDate = null,
        ?string $toDate = null,
        ?string $headerUuid = null
    ) {
        $this->fromDate   = $fromDate;
        $this->toDate     = $toDate;
        $this->headerUuid = $headerUuid;
    }

    public function collection()
    {
        return RouteVisit::query()
            ->select([
                'route_visit.uuid',
                'route_visit.osa_code',
                'route_visit.customer_type',
                'route_visit.region',
                'route_visit.area',
                'route_visit.warehouse',
                'route_visit.route',
                'route_visit.days',
                'route_visit.from_date',
                'route_visit.to_date',
                'route_visit.status',
                'route_visit.customer_id',
                'route_visit.company_id',
                'route_visit.merchandiser_id',
                'route_visit.created_at',
            ])
            ->when($this->fromDate && $this->toDate, function ($q) {
                $q->whereBetween('route_visit.created_at', [
                    $this->fromDate . ' 00:00:00',
                    $this->toDate   . ' 23:59:59',
                ]);
            })
            ->when($this->headerUuid, function ($q) {
                $q->whereHas('header', function ($h) {
                    $h->where('uuid', $this->headerUuid);
                });
            })
            ->whereNull('route_visit.deleted_at')
            ->orderBy('route_visit.id', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'UUID',
            'OSA Code',
            'Customer Type',
            'Region',
            'Area',
            'Warehouse',
            'Route',
            'Days',
            'From Date',
            'To Date',
            'Status',
            'Customer ID',
            'Company ID',
            'Merchandiser ID',
            'Created At',
        ];
    }
}
