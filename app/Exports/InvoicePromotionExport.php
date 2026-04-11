<?php

namespace App\Exports;

use App\Models\Agent_Transaction\InvoiceHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class InvoicePromotionExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents,
    WithStyles
{
    protected array $groupIndexes = [];
    protected $from;
    protected $to;
    protected $promotionId;

    public function __construct($from = null, $to = null, $promotionId = null)
    {
        $this->from = $from;
        $this->to = $to;
        $this->promotionId = $promotionId;
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

    $query = InvoiceHeader::with([
            'delivery:id,delivery_code',
            'warehouse:id,warehouse_code,warehouse_name',
            'route:id,route_code,route_name',
            'customer:id,osa_code,name',
            'salesman:id,osa_code,name',
        ])
        ->when($this->promotionId, fn($q) => $q->where('promotion_id', $this->promotionId))
        ->when($this->from && $this->to, fn($q) => $q->whereBetween('invoice_date', [$this->from, $this->to]))
        ->orderBy('invoice_date', 'desc');

    $query->chunk(200, function ($headers) use (&$rows) {
        foreach ($headers as $header) {

            $rows[] = [
                $this->excelSafe($header->invoice_code),
                $header->invoice_date ? \Carbon\Carbon::parse($header->invoice_date)->format('d M Y') : '',
                $header->invoice_time ? \Carbon\Carbon::parse($header->invoice_time)->format('h:i A') : '',
                $this->excelSafe($header->delivery->delivery_code ?? ''),
                $this->excelSafe(trim(($header->warehouse->warehouse_code ?? '') . ' - ' . ($header->warehouse->warehouse_name ?? ''))),
                $this->excelSafe(trim(($header->route->route_code ?? '') . ' - ' . ($header->route->route_name ?? ''))),
                $this->excelSafe(trim(($header->customer->osa_code ?? '') . ' - ' . ($header->customer->name ?? ''))),
                $this->excelSafe(trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? ''))),
                number_format($header->vat, 2, '.', ''),
                number_format($header->net_total, 2, '.', ''),
                number_format($header->total_amount, 2, '.', ''),
                $header->details->count() ?? 0, // still showing item count if needed
            ];
        }
    });

    return new Collection($rows);
}

    public function headings(): array
    {
        return [
            'Invoice Code',
            'Invoice Date',
            'Invoice Time',
            'Delivery Code',
            'Distributors',
            'Route',
            'Customer',
            'Salesman',
            'VAT',
            'Net Total',
            'Total Amount',
            'Total Item',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->freezePane('A2');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();

                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
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

                foreach ($this->groupIndexes as $group) {
                    $sheet->getRowDimension($group['header_row'])->setOutlineLevel(0)->setVisible(true);
                    for ($i = $group['start']; $i <= $group['end']; $i++) {
                        $sheet->getRowDimension($i)->setOutlineLevel(1)->setVisible(false);
                        $sheet->getStyle("B{$i}")->getAlignment()->setIndent(1);
                    }
                    $sheet->getStyle("B{$group['start']}:H{$group['start']}")->getFont()->setBold(true);
                }

                $sheet->setShowSummaryBelow(false);
            },
        ];
    }
}