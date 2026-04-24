<?php

namespace App\Exports;

use App\Models\Agent_Transaction\ExchangeHeader;
use App\Models\Agent_Transaction\ExchangeInReturn;
use App\Models\Agent_Transaction\ExchangeInInvoice;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Helpers\DataAccessHelper;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ExchangeCollapseExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected array $groupIndexes = [];

    protected $fromDate;
    protected $toDate;
    protected $companyId;
    protected $regionIds;
    protected $areaIds;
    protected $warehouseIds;

    public function __construct(
        $fromDate = null,
        $toDate = null,
        $companyId = null,
        $regionIds = [],
        $areaIds = [],
        $warehouseIds = []
    ) {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->companyId = $companyId;
        $this->regionIds = $regionIds;
        $this->areaIds = $areaIds;
        $this->warehouseIds = $warehouseIds;
    }

    private function excelSafe($value)
    {
        if (is_string($value) && preg_match('/^[=+\-@]/', $value)) {
            return "'" . $value;
        }
        return $value;
    }

    public function collection()
{
    $rows = [];
    $rowIndex = 2;

    $query = ExchangeHeader::select([
            'id',
            'exchange_code',
            'warehouse_id',
            'customer_id',
            'comment',
            'status',
            'created_at'
        ])
        ->with([
            'warehouse:id,warehouse_name,warehouse_code,company,region_id,area_id',
            'customer:id,osa_code,name',
        ])

        // ✅ DATE FILTER
        ->when($this->fromDate && $this->toDate, function ($q) {
            $q->whereBetween('created_at', [
                Carbon::parse($this->fromDate)->startOfDay(),
                Carbon::parse($this->toDate)->endOfDay(),
            ]);
        })

        // ✅ COMPANY (warehouse relation se)
        ->when(!empty($this->companyId), function ($q) {
            $q->whereHas('warehouse', function ($sub) {
                $sub->where('company', $this->companyId);
            });
        })

        // ✅ REGION (warehouse relation se)
        ->when(!empty($this->regionIds), function ($q) {
            $q->whereHas('warehouse', function ($sub) {
                $sub->whereIn('region_id', $this->regionIds);
            });
        })

        // ✅ AREA (warehouse relation se)
        ->when(!empty($this->areaIds), function ($q) {
            $q->whereHas('warehouse', function ($sub) {
                $sub->whereIn('area_id', $this->areaIds);
            });
        })

        // ✅ WAREHOUSE DIRECT
        ->when(!empty($this->warehouseIds), function ($q) {
            $q->whereIn('warehouse_id', $this->warehouseIds);
        })

        ->orderBy('id', 'desc');

    // ✅ USER DATA ACCESS
    $query = DataAccessHelper::filterAgentTransaction($query, Auth::user());

    $query->chunk(200, function ($headers) use (&$rows, &$rowIndex) {

        foreach ($headers as $header) {

            $sectionRows = [];
            $headerRow = $rowIndex;

            // HEADER
            $rows[] = [
                $this->excelSafe($header->exchange_code),
                $this->excelSafe(optional($header->created_at)->format('d M Y')),
                $this->excelSafe(($header->warehouse->warehouse_code ?? '') . ' - ' . ($header->warehouse->warehouse_name ?? '')),
                $this->excelSafe(($header->customer->osa_code ?? '') . ' - ' . ($header->customer->name ?? '')),
                $this->excelSafe($header->comment ?? ''),
                '', '', '', '', '', '', '', '', ''
            ];
            $rowIndex++;

            // COLLECT
            $collects = ExchangeInReturn::with(['item', 'uoms'])
                ->where('header_id', $header->id)
                ->get();

            if ($collects->isNotEmpty()) {

                $sectionRows[] = $rowIndex;

                $rows[] = ['Collect','Item','UOM','Price','Quantity','Total','Reason','Return Type','','','','','',''];
                $rowIndex++;

                foreach ($collects as $d) {
                    $rows[] = [
                        '',
                        $this->excelSafe(($d->item->code ?? '') . ' - ' . ($d->item->name ?? '')),
                        $d->uoms->name ?? '',
                        (float)$d->item_price,
                        (float)$d->item_quantity,
                        (float)$d->total,
                        $this->getReturnCategory($d->return_type ?? ''),
                        $this->getReturnTypeLabel($d->region ?? ''),
                        '', '', '', '', '', ''
                    ];
                    $rowIndex++;
                }
            }

            // RETURN
            $returns = ExchangeInInvoice::with(['item', 'uoms'])
                ->where('header_id', $header->id)
                ->get();

            if ($returns->isNotEmpty()) {

                $sectionRows[] = $rowIndex;

                $rows[] = ['Return','Item','UOM','Price','Quantity','Total','','','','','','','',''];
                $rowIndex++;

                foreach ($returns as $d) {
                    $rows[] = [
                        '',
                        $this->excelSafe(($d->item->code ?? '') . ' - ' . ($d->item->name ?? '')),
                        $d->uoms->name ?? '',
                        (float)$d->item_price,
                        (float)$d->item_quantity,
                        (float)$d->total,
                        '', '', '', '', '', '', '', ''
                    ];
                    $rowIndex++;
                }
            }

            // GROUP
            if ($rowIndex > $headerRow + 1) {
                $this->groupIndexes[] = [
                    'header_row' => $headerRow,
                    'start' => $headerRow + 1,
                    'end' => $rowIndex - 1,
                    'section_rows' => $sectionRows,
                ];
            }

            // GAP
            $rows[] = array_fill(0, 16, '');
            $rowIndex++;
        }
    });

    return new Collection($rows);
}
    private function getReturnCategory($value)
    {
        if (in_array($value, ["1", "2"])) return "Good";
        if (in_array($value, ["3", "4"])) return "Bad";
        return "-";
    }

    private function getReturnTypeLabel($value)
    {
        if ($value === "1") return "Near By Expiry";
        if ($value === "2") return "Package Issue";
        if ($value === "3") return "Damage";
        if ($value === "4") return "Expiry";
        return "-";
    }

    public function headings(): array
    {
        return [
            'Exchange No',
            'Date',
            'Distributors',
            'Customer',
            'Comment',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle("A1:E1")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'F5F5F5']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'],
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);

                foreach ($this->groupIndexes as $g) {
                    for ($i = $g['start']; $i <= $g['end']; $i++) {
                        $sheet->getRowDimension($i)
                            ->setOutlineLevel(1)
                            ->setVisible(false);
                    }

                    foreach ($g['section_rows'] as $row) {
                        $sheet->getStyle("A{$row}:J{$row}")
                            ->getFont()->setBold(true);
                    }
                }

                $sheet->setShowSummaryBelow(false);
            }
        ];
    }
}