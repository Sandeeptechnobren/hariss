<?php

namespace App\Exports\EfrisExport;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class UraInvoiceExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        $export = [];

        foreach ($this->data as $item) {
            $export[] = [

                'Issued Date' => !empty($item['issuedDate'])
                    ? Carbon::createFromFormat('d/m/Y H:i:s', $item['issuedDate'])
                    ->format('d M Y')
                    : '',
                'Agent Name'    => (string) ($item['businessName'] ?? ''),
                'Customer Name'       => (string) ($item['buyerLegalName'] ?? ''),
                'invoice ID'         => (string) ($item['id'] ?? ''),
                'FDN No'       => (string) ($item['invoiceNo'] ?? ''),
                'Gross Total'           => (float)  ($item['grossAmount'] ?? 0),
                // 'Antifake Code'    => (string) ($item['ura_antifake_code'] ?? ''),
                // 'UUID'             => (string) ($item['uuid'] ?? ''),
            ];
        }

        return new Collection($export);
    }

    public function headings(): array
    {
        return [
            'Issued Date',
            'Agent Name',
            'Customer Name',
            'invoice ID',
            'FDN No',
            'Gross Total'
            // 'Antifake Code',
            // 'UUID'
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
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size'  => 12,
                        'name'  => 'Arial',
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'wrapText'   => true,
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // Increase header height
                $sheet->getRowDimension(1)->setRowHeight(28);
            },
        ];
    }
}
