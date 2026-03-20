<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\TempReturnH;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class TempReturnHExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
    }

    public function collection()
    {
        $rows = [];

        $query = TempReturnH::with([
            'customer'
        ]);

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [$this->startDate, $this->endDate]);
        }

        $headers = $query->get();

        foreach ($headers as $header) {
            $rows[] = [
                'Return Code'      => $header->return_code,
                'Customer Code'    => $header->customer->osa_code ?? null,
                'Customer Name'    => $header->customer->business_name ?? null,
                'Customer Town'    => $header->customer->town ?? null,

                'VAT'              => $header->vat,
                'Net Amount'       => $header->net,
                'Total Amount'     => $header->amount,

                'Truck Name'       => $header->truckname,
                'Truck No'         => $header->truckno,
                'Contact No'       => $header->contactno,

                'SAP ID'           => $header->sap_id,
                'Message'          => $header->message,
                'Reason'           => $header->return_reason,
                'Reason Type'      => $header->return_type,
            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Return Code',
            'Customer Code',
            'Customer Name',
            'Customer Town',

            'VAT',
            'Net Amount',
            'Total Amount',

            'Truck Name',
            'Truck No',
            'Contact No',

            'SAP ID',
            'Message',
            'Reason',
            'Reason Type',
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
