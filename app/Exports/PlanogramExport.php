<?php
namespace App\Exports;

use App\Services\V1\Merchendisher\Web\PlanogramService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

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
            $row['valid_from'],
            $row['valid_to'],
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