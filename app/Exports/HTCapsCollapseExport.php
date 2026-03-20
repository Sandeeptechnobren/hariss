<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\HtCapsHeader;
use App\Models\Hariss_Transaction\Web\HtCapsDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class HTCapsCollapseExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents,
    WithStyles
{
    protected array $groupIndexes = [];
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
        $rowIndex = 2;

        $headers = HtCapsHeader::with(['warehouse', 'driverinfo'])
            ->when($this->startDate && $this->endDate, function ($q) {
                $q->whereBetween('claim_date', [$this->startDate, $this->endDate]);
            })
            ->get();

        foreach ($headers as $header) {

            $headerRowIndex = $rowIndex;

            // HEADER ROW
            $rows[] = [
                'OSA Code'        => (string) $header->osa_code,
                'Warehouse Code'  => (string) ($header->warehouse->warehouse_code ?? ''),
                'Warehouse Name'  => (string) ($header->warehouse->warehouse_name ?? ''),
                'Driver Code'     => (string) ($header->driverinfo->osa_code ?? ''),
                'Driver Name'     => (string) ($header->driverinfo->driver_name ?? ''),
                'Driver Contact'  => (string) ($header->driverinfo->contactno ?? ''),
                'Truck No'        => (string) $header->truck_no,
                'Claim No'        => (string) $header->claim_no,
                'Claim Date'      => (string) $header->claim_date,
                'Claim Amount'    => (float)  $header->claim_amount,

                'Item Code'       => '',
                'Item Name'       => '',
                'UOM Name'        => '',
                'Quantity'        => '',
                'Receive Qty'     => '',
                'Receive Amount'  => '',
                'Receive Date'    => '',
                'Remarks'         => '',
                'Remarks2'        => '',
            ];

            $rowIndex++;

            // DETAIL ROWS
            $details = HtCapsDetail::with(['item', 'uoms'])
                ->where('header_id', $header->id)
                ->get();

            $detailRowIndexes = [];

            foreach ($details as $detail) {
                $rows[] = [
                    'OSA Code'        => '',
                    'Warehouse Code'  => '',
                    'Warehouse Name'  => '',
                    'Driver Code'     => '',
                    'Driver Name'     => '',
                    'Driver Contact'  => '',
                    'Truck No'        => '',
                    'Claim No'        => '',
                    'Claim Date'      => '',
                    'Claim Amount'    => '',

                    'Item Code'       => (string) ($detail->item->code ?? ''),
                    'Item Name'       => (string) ($detail->item->name ?? ''),
                    'UOM Name'        => (string) ($detail->uoms->name ?? ''),
                    'Quantity'        => (float) $detail->quantity,
                    'Receive Qty'     => (float) $detail->receive_qty,
                    'Receive Amount'  => (float) $detail->receive_amount,
                    'Receive Date'    => (string) $detail->receive_date,
                    'Remarks'         => (string) $detail->remarks,
                    'Remarks2'        => (string) $detail->remarks2,
                ];

                $detailRowIndexes[] = $rowIndex;
                $rowIndex++;
            }

            // GROUP DETAILS
            if ($detailRowIndexes) {
                $this->groupIndexes[] = [
                    'start' => $headerRowIndex + 1,
                    'end'   => max($detailRowIndexes),
                ];
            }

            // BLANK SEPARATOR ROW (SAFE)
            $rows[] = array_fill_keys(array_keys($rows[0]), '');
            $rowIndex++;
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'OSA Code',
            'Warehouse Code',
            'Warehouse Name',
            'Driver Code',
            'Driver Name',
            'Driver Contact',
            'Truck No',
            'Claim No',
            'Claim Date',
            'Claim Amount',
            'Item Code',
            'Item Name',
            'UOM Name',
            'Quantity',
            'Receive Qty',
            'Receive Amount',
            'Receive Date',
            'Remarks',
            'Remarks2',
        ];
    }

    // ✅ FIXED: NO HARD-CODED COLUMNS
    public function styles(Worksheet $sheet)
    {
        $lastColumn = $sheet->getHighestColumn();

        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);
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
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

                foreach ($this->groupIndexes as $group) {
                    for ($i = $group['start']; $i <= $group['end']; $i++) {
                        $sheet->getRowDimension($i)
                              ->setOutlineLevel(1)
                              ->setVisible(false);
                    }
                }

                $sheet->setShowSummaryBelow(false);
            }
        ];
    }
}
