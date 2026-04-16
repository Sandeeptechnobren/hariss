<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

use App\Models\Claim_Management\Web\PetitClaim;
use Maatwebsite\Excel\Concerns\{
    FromQuery,
    WithMapping,
    WithHeadings,
    ShouldAutoSize,
    WithChunkReading,
    WithEvents
};
use Maatwebsite\Excel\Events\AfterSheet;

class PetitClaimExport implements
    FromQuery,
    WithMapping,
    WithHeadings,
    ShouldAutoSize,
    WithChunkReading,
    WithEvents
{
    protected $fromDate;
    protected $toDate;
    protected $warehouseIds;

    public function __construct($fromDate = null, $toDate = null, $warehouseIds = [])
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->warehouseIds = $warehouseIds;
    }

    public function query()
    {
        $query = PetitClaim::with('warehouse');

        if ($this->fromDate && $this->toDate) {
            $query->whereBetween('created_at', [
                $this->fromDate . ' 00:00:00',
                $this->toDate . ' 23:59:59'
            ]);
        }

        if (!empty($this->warehouseIds)) {
            $query->whereIn('warehouse_id', $this->warehouseIds);
        }

        return $query;
    }

    public function map($item): array
    {
        return [
            $item->osa_code,
            $item->claim_type,

            // ✅ MERGED COLUMN
            optional($item->warehouse)->warehouse_code . ' - ' .
                optional($item->warehouse)->warehouse_name,

            $item->petit_name,
            $item->fuel_amount,
            $item->rent_amount,
            $item->agent_amount,
            $item->month_range,
            $item->year,
            $this->formatTechnicianStatus($item->status),
            $item->reject_reason,
        ];
    }

    public function headings(): array
    {
        return [
            "OSA Code",
            "Claim Type",
            "Distributor", // ✅ merged heading
            "Petit Name",
            "Fuel Amount",
            "Rent Amount",
            "Agent Amount",
            "Month Range",
            "Year",
            "Status",
            "Reject Reason",
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    private function formatTechnicianStatus($status): string
    {
        $map = [
            1 => 'Waiting for Commercial Manager',
            2 => 'Rejected By Commercial Manager',
            3 => 'Waiting For Customer Care',
            4 => 'Rejected By Customer Care',
            5 => 'Completed',
        ];

        return $map[$status] ?? 'Unknown';
    }
    // 🎨 HEADER COLOR STYLE
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
            }

        ];
    }
}
