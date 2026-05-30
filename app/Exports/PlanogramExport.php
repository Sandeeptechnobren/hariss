<?php

namespace App\Exports;

use App\Services\V1\Merchendisher\Web\PlanogramService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class PlanogramExport implements FromCollection, WithHeadings, WithMapping
{
    protected $service;

    public function __construct(PlanogramService $service)
    {
        $this->service = $service;
    }

    /**
     * Return the collection of rows for export.
     */
    public function collection()
    {
        return $this->service->getFlatRows();
    }

    /**
     * Map each row to the desired output order.
     */
    public function map($row): array
    {
        return [
            $row['planogram_id'],
            $row['planogram_name'],
            $row['planogram_code'],
            !empty($row['valid_from'])
                ? Carbon::parse($row['valid_from'])->format('d M Y')
                : null,
            !empty($row['valid_to'])
                ? Carbon::parse($row['valid_to'])->format('d M Y')
                : null,
            $row['merchandiser_name'],
            $row['customer_name'],
            $row['image'],
        ];
    }

    /**
     * Headings for CSV / Excel columns.
     */
    public function headings(): array
    {
        return [
            'Planogram ID',
            'Planogram Name',
            'Planogram Code',
            'Valid From',
            'Valid To',
            'Merchandiser Name',
            'Customer Name',
            'Image',
        ];
    }
}
