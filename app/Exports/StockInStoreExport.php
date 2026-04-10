<?php

namespace App\Exports;

use App\Models\StockInStore;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class StockInStoreExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected ?string $startDate;
    protected ?string $endDate;
    protected ?string $searchTerm;

    public function __construct(
        ?string $startDate  = null,
        ?string $endDate    = null,
        ?string $searchTerm = null
    ) {
        $this->startDate  = $startDate;
        $this->endDate    = $endDate;
        $this->searchTerm = $searchTerm;
    }

    public function collection()
    {
        $query = StockInStore::query();

        // Date range filter
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        } elseif ($this->startDate) {
            $query->whereDate('created_at', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->whereDate('created_at', '<=', $this->endDate);
        }

        // Global search — mirrors getAll() search fields
        if (!empty($this->searchTerm)) {
            $like = '%' . strtolower($this->searchTerm) . '%';

            $query->where(function ($q) use ($like) {
                $q->orWhereRaw('LOWER(CAST(id AS TEXT)) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(code) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(CAST(uuid AS TEXT)) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(activity_name) LIKE ?', [$like])
                  ->orWhereRaw('CAST(date_from AS TEXT) LIKE ?', [$like])
                  ->orWhereRaw('CAST(date_to AS TEXT) LIKE ?', [$like]);
            });
        }

        return $query->latest()->get()->map(function ($item) {
            return [
                'Code'      => $item->code      ?? 'N/A',
                'Name'      => $item->activity_name ?? 'N/A',
                'From Date' => $item->date_from ? Carbon::parse($item->date_from)->format('d M Y') : 'N/A',
                'To Date'   => $item->date_to   ? Carbon::parse($item->date_to)->format('d M Y')   : 'N/A',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Name',
            'From Date',
            'To Date',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet      = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();
                $lastRow    = $sheet->getHighestRow();

                // Header styling
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'name'  => 'Arial',
                        'size'  => 11,
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
                            'color'       => ['rgb' => 'FFFFFF'],
                        ],
                    ],
                ]);

                // Data rows styling
                if ($lastRow > 1) {
                    $sheet->getStyle("A2:{$lastColumn}{$lastRow}")->applyFromArray([
                        'font' => [
                            'name' => 'Arial',
                            'size' => 10,
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_LEFT,
                            'vertical'   => Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color'       => ['rgb' => 'DDDDDD'],
                            ],
                        ],
                    ]);
                }

                $sheet->freezePane('A2');
                $sheet->getRowDimension(1)->setRowHeight(20);
            },
        ];
    }
}