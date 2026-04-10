<?php

// namespace App\Exports;

// use App\Models\CampaignInformation;
// use Maatwebsite\Excel\Concerns\FromCollection;
// use Maatwebsite\Excel\Concerns\WithHeadings;
// use Maatwebsite\Excel\Concerns\ShouldAutoSize;
// use Carbon\Carbon;

// class CampaignInformationExport implements FromCollection, WithHeadings, ShouldAutoSize
// {
//     protected $startDate;
//     protected $endDate;

//     public function __construct($startDate = null, $endDate = null)
//     {
//         $this->startDate = $startDate;
//         $this->endDate = $endDate;
//     }

//     public function collection()
//     {
//         $query = CampaignInformation::with(['merchandiser', 'customer']);

//         if ($this->startDate && $this->endDate) {
//             $query->whereBetween('created_at', [
//                 Carbon::parse($this->startDate)->startOfDay(),
//                 Carbon::parse($this->endDate)->endOfDay()
//             ]);
//         }

//         return $query->get()->map(function ($item) {
//             return [
//                 'code' => $item->code,
//                 'merchandiser' => $item->merchandiser->name ?? '',
//                 'customer' => $item->customer->business_name ?? '',
//                 'feedback' => $item->feedback,
//                 'images' => json_encode($item->images),
//             ];
//         });
//     }

//     public function headings(): array
//     {
//         return [
//             'Code',
//             'Merchandiser Name',
//             'Customer Business Name',
//             'Feedback',
//             'Images',
//             // 'Created At'
//         ];
//     }
// }
namespace App\Exports;

use App\Models\CampaignInformation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class CampaignInformationExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected ?string $startDate;
    protected ?string $endDate;
    protected ?string $searchTerm;
    protected mixed $merchandiserId;  // ← was ?string, integers were being cast to null
    protected mixed $customerId;      // ← same fix
    protected ?string $date;

    public function __construct(
        ?string $startDate      = null,
        ?string $endDate        = null,
        ?string $searchTerm     = null,
        mixed   $merchandiserId = null,  // ← fixed
        mixed   $customerId     = null,  // ← fixed
        ?string $date           = null
    ) {
        $this->startDate      = $startDate;
        $this->endDate        = $endDate;
        $this->searchTerm     = $searchTerm;
        $this->merchandiserId = $merchandiserId;
        $this->customerId     = $customerId;
        $this->date           = $date;
    }

public function collection()
    {
        $query = CampaignInformation::with(['merchandiser', 'customer']);
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }
        if (!empty($this->merchandiserId)) {
            $query->where('merchandiser_id', $this->merchandiserId);
        }
        if (!empty($this->customerId)) {
            $query->where('customer_id', $this->customerId);
        }
        if (!empty($this->date)) {
            $query->whereDate('created_at', $this->date);
        }
        if (!empty($this->searchTerm)) {
            $s = strtolower($this->searchTerm);
            $query->where(function ($q) use ($s) {
                $q->orWhereRaw("LOWER(CAST(id AS TEXT)) LIKE ?", ["%{$s}%"])
                  ->orWhereRaw("LOWER(CAST(uuid AS TEXT)) LIKE ?", ["%{$s}%"])
                  ->orWhereRaw("LOWER(code) LIKE ?", ["%{$s}%"])
                  ->orWhereRaw("LOWER(CAST(merchandiser_id AS TEXT)) LIKE ?", ["%{$s}%"])
                  ->orWhereRaw("LOWER(CAST(customer_id AS TEXT)) LIKE ?", ["%{$s}%"])
                  ->orWhereHas('merchandiser', fn($sub) =>
                      $sub->whereRaw("LOWER(name) LIKE ?", ["%{$s}%"])
                  )
                  ->orWhereHas('customer', fn($sub) =>
                      $sub->whereRaw("LOWER(business_name) LIKE ?", ["%{$s}%"])
                  );
            });
        }
        return $query->latest()->get()->map(function ($item) {
            return [
                'Code'          => $item->code,
                'Merchandiser'  => $item->merchandiser->name ?? 'N/A',
                'Customer'      => $item->customer->business_name ?? 'N/A',
                'Feedback'      => $item->feedback,
                // 'Images'        => is_array($item->images)
                //                     ? implode(' | ', $item->images)
                //                     : (json_encode($item->images) ?? 'N/A'),
                // 'Created At'    => $item->created_at?->format('Y-m-d H:i:s'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Campaign Code',
            'Merchandiser',
            'Customer Name',
            'Feedback',
            // 'Images',
            // 'Created At',
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

                // Freeze header row
                $sheet->freezePane('A2');

                // Row height for header
                $sheet->getRowDimension(1)->setRowHeight(20);
            },
        ];
    }
}