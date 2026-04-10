<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\HtReturnHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class HTReturnExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{

    protected $from_date;
    protected $to_date;
    protected $warehouseIds;

    public function __construct($from_date = null, $to_date = null, $warehouseIds = [])
    {
        $this->from_date = $from_date ?: now()->toDateString();
        $this->to_date   = $to_date ?: now()->toDateString();
        $this->warehouseIds = $warehouseIds;
    }

    public function collection()
    {
        $rows = [];

        $query = HtReturnHeader::with([
            'warehouse',
            'company',
            'customer',
            'driver',
        ]);
        if ($this->from_date && $this->to_date) {
            $query->whereDate('created_at', '>=', $this->from_date)
                ->whereDate('created_at', '<=', $this->to_date);
        }

        if (!empty($this->warehouseIds)) {
            $query->whereIn('warehouse_id', $this->warehouseIds);
        }
        // dd($query->count());
        $headers = $query->get();

        foreach ($headers as $header) {
            $rows[] = [
                'OSA Code'                  => $header->return_code,
                'Customer'                  => trim(($header->customer->osa_code ?? '') . ' - ' . ($header->customer->business_name ?? '')),
                'Company'                   => trim(($header->company->company_code ?? '') . ' - ' . ($header->company->company_name ?? '')),
                'Warehouse'                 => trim(($header->warehouse->warehouse_code ?? '') . ' - ' . ($header->warehouse->warehouse_name ?? '')),
                'Driver'                    => trim(($header->driver->osa_code ?? '') . ' - ' . ($header->driver->driver_name ?? '')),
                'Turnman'                   => $header->turnman,
                'Truck No'                  => $header->truck_no,
                'Vat'                       => $header->vat,
                'Net'                       => $header->net,
                'Total'                     => $header->total,
                'Contact No'                => $header->contact_no,
            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'OSA Code',
            'Customer',
            'Company',

            'Distributors',
            'Driver',
            'Turnman',

            'Truck No',
            'Vat',

            'Net',
            'Total',
            'Contact No',
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
                        'color' => ['rgb' => 'F5F5F5'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}
