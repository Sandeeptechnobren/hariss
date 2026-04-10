<?php

namespace App\Exports;

use App\Models\Warehouse;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromQuery;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;


class WarehousesExport implements FromQuery, WithHeadings, WithMapping, WithEvents
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function map($warehouse): array
    {
        return [
            $warehouse->warehouse_code,
            $warehouse->warehouse_type == 1 ? 'Company Outlet' : 'Distributor',
            $warehouse->warehouse_name,
            $warehouse->owner_name,
            $warehouse->owner_number,
            $warehouse->owner_email,
            $warehouse->agreed_stock_capital,
            $warehouse->location,
            $warehouse->city,
            $warehouse->warehouse_manager,
            $warehouse->warehouse_manager_contact,
            $warehouse->tin_no,
            optional($warehouse->getCompany)->company_name,
            $warehouse->warehouse_email,
            optional($warehouse->region)->region_name,
            optional($warehouse->area)->area_name,
            $warehouse->latitude,
            $warehouse->longitude,
            optional($warehouse->getCompanyCustomer)->business_name,
            $warehouse->town_village,
            $warehouse->street,
            $warehouse->landmark,
            $warehouse->is_efris ? 'Yes' : 'No',
            $warehouse->is_branch,
            $warehouse->status ? 'Active' : 'Inactive',
        ];
    }

    public function headings(): array
    {
        return [
            'Distributor Code',
            'Distributor Type',
            'Distributor Name',
            'Owner Name',
            'Owner Number',
            'Owner Email',
            'Agreed Stock Capital',
            'Location',
            'City',
            'Distributor Manager',
            'Manager Contact',
            'TIN No',
            'Company Name',
            'Distributor Email',
            'Region Name',
            'Area Name',
            'Latitude',
            'Longitude',
            'Customer Name',
            'Town/Village',
            'Street',
            'Landmark',
            'Is EFRIS',
            'Is Branch',
            'Status',
        ];
    }

    public function registerEvents(): array
{
    return [
        AfterSheet::class => function (AfterSheet $event) {

            $sheet = $event->sheet->getDelegate();
            $lastColumn = $sheet->getHighestColumn();

            $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'], // white text
                    'size' => 12,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'FFDC3545', // 🔥 proper red (same as your image)
                    ],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ]);

            // Row height
            $sheet->getRowDimension(1)->setRowHeight(25);
        },
    ];
}
}