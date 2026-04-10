<?php

// namespace App\Exports;

// use Maatwebsite\Excel\Concerns\FromCollection;
// use Maatwebsite\Excel\Concerns\WithHeadings;

// class ComplaintFeedbackExport implements FromCollection, WithHeadings
// {
//     protected $exportData;

//     public function __construct($exportData)
//     {
//         $this->exportData = $exportData;
//     }

//     public function collection()
//     {
//         return collect($this->exportData);
//     }

//     public function headings(): array
//     {
//         return [
//             'Complaint Title',
//             'Merchendiser Name',
//             'Item Name',
//             'Type',
//             'Complaint',
//         ];
//     }
// }

namespace App\Exports;

use App\Models\ComplaintFeedback;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class ComplaintFeedbackExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
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
        $query = ComplaintFeedback::with(['merchendiser', 'item', 'customer']);

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

        // Global search — only real relationships on this model
        if (!empty($this->searchTerm)) {
            $like = '%' . strtolower($this->searchTerm) . '%';

            $query->where(function ($q) use ($like) {
                $q->orWhereRaw('LOWER(CAST(id AS TEXT)) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(complaint_title) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(complaint) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(CAST(uuid AS TEXT)) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(complaint_code) LIKE ?', [$like])
                  ->orWhereHas('merchendiser', fn($sub) =>
                      $sub->whereRaw('LOWER(name) LIKE ?', [$like])
                  )
                  ->orWhereHas('item', fn($sub) =>
                      $sub->whereRaw('LOWER(name) LIKE ?', [$like])
                  )
                  ->orWhereHas('customer', fn($sub) =>
                      $sub->whereRaw('LOWER(business_name) LIKE ?', [$like])
                  );
            });
        }

        return $query->latest()->get()->map(function ($item) {
            return [
                'Complaint Title'   => $item->complaint_title      ?? 'N/A',
                'Complaint Code'    => $item->complaint_code        ?? 'N/A',
                'Merchendiser Name' => $item->merchendiser->name    ?? 'N/A',
                'Item Name'         => $item->item->name            ?? 'N/A',
                'Customer'          => $item->customer->business_name ?? 'N/A',
                'Type'              => $item->type                  ?? 'N/A',
                'Complaint'         => $item->complaint             ?? 'N/A',
                'Created At'        => $item->created_at?->format('Y-m-d H:i:s') ?? 'N/A',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Complaint Title',
            'Complaint Code',
            'Merchendiser Name',
            'Item Name',
            'Customer',
            'Type',
            'Complaint',
            'Created At',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet      = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();
                $lastRow    = $sheet->getHighestRow();

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
