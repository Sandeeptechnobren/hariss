<?php

namespace App\Exports;

use App\Models\Agent_Transaction\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class CollectionFullExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $uuid;

    public function __construct($uuid = null)
    {
        $this->uuid = $uuid;
    }

    public function collection()
    {
        $rows = [];

        $query = Collection::with(['invoice', 'customer', 'salesman','route','warehouse']);
        if ($this->uuid) {
            $query->where('uuid', $this->uuid); 
        }
        $headers = $query->get();
        foreach ($headers as $header) {
            $rows[] = [
                'Collection Code'     => (string) $header->code,
                'Invoice Code'        => (string) ($header->invoice->invoice_code),
                'Warehouse Name'      => (string) ($header->warehouse->warehouse_name ?? ''),
                'Route Name'          => (string) ($header->route->route_name ?? ''),
                'Salesman Name'       => (string) ($header->salesman->name ?? ''),
                'Customer Name'       => (string) ($header->customer->name ?? ''),
                'Amount'              => (string) ($header->amount ?? ''),
                'Outstanding'         => (string) ($header->outstanding ?? ''),
                'Status'              => $header->status == 1 ? 'Active' : 'Inactive',
            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'CapsCollection Code',
            'Invoice Code',
            'Warehouse Name',
            'Route Name',
            'Salesman Name',
            'Customer Name',
            'Amount',
            'Outstanding',
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