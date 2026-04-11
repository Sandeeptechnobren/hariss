<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\HtCapsHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class HTCapsHeaderExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $from_date;
    protected $to_date;
    protected $warehouseIds;

    public function __construct($from_date = null, $to_date = null, $warehouseIds = [])
    {
        $this->from_date = $from_date ?: now()->toDateString();
        $this->to_date   = $to_date   ?: now()->toDateString();
        $this->warehouseIds = $warehouseIds;
    }
    public function collection()
    {
        $rows = [];

        $query = HtCapsHeader::with([
            'warehouse',
            'driverinfo',
        ]);
        if (!empty($this->warehouseIds)) {
            $query->whereIn('warehouse_id', $this->warehouseIds);
        }

        if ($this->from_date && $this->to_date) {
            $query->whereBetween('claim_date', [$this->from_date, $this->to_date]);
        }
        $headers = $query->get();


        foreach ($headers as $header) {
            $rows[] = [
                'OSA Code' => $header->osa_code,
                // ✅ Date format: d M Y
                'Claim Date'   => $header->claim_date
                    ? Carbon::parse($header->claim_date)->format('d M Y')
                    : null,

                // ✅ Merge Warehouse Code + Name
                'Distributor' => ($header->warehouse->warehouse_code ?? '') .
                    ' - ' .
                    ($header->warehouse->warehouse_name ?? ''),

                'Driver' => ($header->driverinfo->osa_code ?? '') .
                    ' - ' .
                    ($header->driverinfo->driver_name ?? ''),

                'Driver Contact No' => $header->driverinfo->contactno ?? null,

                'Truck No'     => $header->truck_no,
                'Claim No'     => $header->claim_no,


                'Claim Amount' => $header->claim_amount,
            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'OSA Code',
            'Claim Date',
            'Distributor',

            'Driver',
            'Driver Contact No',

            'Truck No',
            'Claim No',
            'Claim Amount',
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
