<?php

// namespace App\Exports;

// use App\Models\Survey;
// use Maatwebsite\Excel\Concerns\FromCollection;
// use Maatwebsite\Excel\Concerns\WithHeadings;
// use Maatwebsite\Excel\Concerns\ShouldAutoSize;
// use Maatwebsite\Excel\Concerns\WithEvents;
// use Maatwebsite\Excel\Events\AfterSheet;
// use PhpOffice\PhpSpreadsheet\Style\Fill;
// use PhpOffice\PhpSpreadsheet\Style\Alignment;
// use PhpOffice\PhpSpreadsheet\Style\Border;
// use Carbon\Carbon;

// class SurveyExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
// {
//     protected ?string $validFrom;
//     protected ?string $validTo;
//     protected ?string $searchTerm;

//     public function __construct(
//         ?string $validFrom   = null,
//         ?string $validTo     = null,
//         ?string $searchTerm  = null
//     ) {
//         $this->validFrom   = $validFrom;
//         $this->validTo     = $validTo;
//         $this->searchTerm  = $searchTerm;
//     }
//     public function collection()
//     {
//         $query = Survey::query()
//             ->with([
//                 'merchandishers',
//                 'customers',
//                 'assets',
//                 'createdUser:id,name,username',
//                 'updatedUser:id,name,username',
//                 'deletedUser:id,name,username',
//             ]);
//         if ($this->validFrom && $this->validTo) {
//             $query->whereBetween('created_at', [
//                 Carbon::parse($this->validFrom)->startOfDay(),
//                 Carbon::parse($this->validTo)->endOfDay(),
//             ]);
//         } elseif ($this->validFrom) {
//             $query->whereDate('created_at', '>=', $this->validFrom);
//         } elseif ($this->validTo) {
//             $query->whereDate('created_at', '<=', $this->validTo);
//         }
//         if (!empty($this->searchTerm)) {
//             $likeSearch = '%' . strtolower($this->searchTerm) . '%';
//             $searchTerm = $this->searchTerm;
//             $query->where(function ($q) use ($likeSearch, $searchTerm) {
//                 $q->whereRaw('LOWER(survey_code) LIKE ?', [$likeSearch])
//                   ->orWhereRaw('LOWER(survey_name) LIKE ?', [$likeSearch])
//                   ->orWhere(function ($sub) use ($searchTerm, $likeSearch) {
//                       if (is_numeric($searchTerm)) {
//                           $sub->where('status', $searchTerm);
//                       } else {
//                           $sub->whereRaw('LOWER(status::text) LIKE ?', [$likeSearch]);
//                       }
//                   })
//                   ->orWhereRaw('CAST(start_date AS TEXT) LIKE ?', [$likeSearch])
//                   ->orWhereRaw('CAST(end_date AS TEXT) LIKE ?', [$likeSearch])
//                   ->orWhereHas('createdUser', fn($sub) =>
//                       $sub->whereRaw('LOWER(name) LIKE ?', [$likeSearch])
//                           ->orWhereRaw('LOWER(username) LIKE ?', [$likeSearch])
//                   )
//                   ->orWhereHas('updatedUser', fn($sub) =>
//                       $sub->whereRaw('LOWER(name) LIKE ?', [$likeSearch])
//                           ->orWhereRaw('LOWER(username) LIKE ?', [$likeSearch])
//                   )
//                   ->orWhereHas('deletedUser', fn($sub) =>
//                       $sub->whereRaw('LOWER(name) LIKE ?', [$likeSearch])
//                           ->orWhereRaw('LOWER(username) LIKE ?', [$likeSearch])
//                   );
//             });
//         }
//         $surveyTypeMap = [
//             1 => 'Consumer',
//             2 => 'Sensory',
//             3 => 'Asset',
//         ];
//         return $query->orderBy('id', 'desc')->get()->map(function ($item) use ($surveyTypeMap) {
//             return [
//                 'Survey Code'  => $item->survey_code ?? 'N/A',
//                 'Survey Name'  => $item->survey_name ?? 'N/A',
//                 'Start Date'   => $item->start_date ?? 'N/A',
//                 'End Date'     => $item->end_date ?? 'N/A',
//                 'Survey Type'  => $surveyTypeMap[$item->survey_type] ?? 'N/A',
//                 'Merchandiser' => $item->merchandishers->pluck('name')->implode(', ') ?: 'N/A',
//                 'Customer'     => $item->customers->pluck('business_name')->implode(', ') ?: 'N/A',
//                 'Asset'        => $item->assets->pluck('serial_number')->implode(', ') ?: 'N/A',
//                 'Status'       => $item->status == 1 ? 'Active' : 'Inactive',
//                 'Created By'   => $item->createdUser->name ?? 'N/A',
//                 'Updated By'   => $item->updatedUser->name ?? 'N/A',
//             ];
//         });
//     }

//     public function headings(): array
//     {
//         return [
//             'Survey Code',
//             'Survey Name',
//             'Start Date',
//             'End Date',
//             'Survey Type',
//             'Merchandiser',
//             'Customer',
//             'Asset',
//             'Status',
//             'Created By',
//             'Updated By',
//         ];
//     }

//     public function registerEvents(): array
//     {
//         return [
//             AfterSheet::class => function (AfterSheet $event) {
//                 $sheet      = $event->sheet->getDelegate();
//                 $lastColumn = $sheet->getHighestColumn();
//                 $lastRow    = $sheet->getHighestRow();

//                 // Header row styling
//                 $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
//                     'font' => [
//                         'bold'  => true,
//                         'color' => ['rgb' => 'FFFFFF'],
//                         'name'  => 'Arial',
//                         'size'  => 11,
//                     ],
//                     'alignment' => [
//                         'horizontal' => Alignment::HORIZONTAL_CENTER,
//                         'vertical'   => Alignment::VERTICAL_CENTER,
//                     ],
//                     'fill' => [
//                         'fillType'   => Fill::FILL_SOLID,
//                         'startColor' => ['rgb' => '993442'],
//                     ],
//                     'borders' => [
//                         'allBorders' => [
//                             'borderStyle' => Border::BORDER_THIN,
//                             'color'       => ['rgb' => 'FFFFFF'],
//                         ],
//                     ],
//                 ]);

//                 // Data rows styling
//                 if ($lastRow > 1) {
//                     $sheet->getStyle("A2:{$lastColumn}{$lastRow}")->applyFromArray([
//                         'font' => [
//                             'name' => 'Arial',
//                             'size' => 10,
//                         ],
//                         'alignment' => [
//                             'horizontal' => Alignment::HORIZONTAL_LEFT,
//                             'vertical'   => Alignment::VERTICAL_CENTER,
//                         ],
//                         'borders' => [
//                             'allBorders' => [
//                                 'borderStyle' => Border::BORDER_THIN,
//                                 'color'       => ['rgb' => 'DDDDDD'],
//                             ],
//                         ],
//                     ]);
//                 }

//                 // Freeze header row
//                 $sheet->freezePane('A2');

//                 // Header row height
//                 $sheet->getRowDimension(1)->setRowHeight(20);
//             },
//         ];
//     }
// }
namespace App\Exports;

use App\Models\Survey;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class SurveyExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected ?string $validFrom;
    protected ?string $validTo;
    protected ?string $searchTerm;

    public function __construct(
        ?string $validFrom  = null,
        ?string $validTo    = null,
        ?string $searchTerm = null
    ) {
        $this->validFrom  = $validFrom;
        $this->validTo    = $validTo;
        $this->searchTerm = $searchTerm;
    }

    public function collection()
    {
        $query = Survey::query()
            ->with([
                // Only real relationships — NOT accessors
                'createdUser:id,name,username',
                'updatedUser:id,name,username',
                'deletedUser:id,name,username',
            ]);

        // Date range filter
        if ($this->validFrom && $this->validTo) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->validFrom)->startOfDay(),
                Carbon::parse($this->validTo)->endOfDay(),
            ]);
        } elseif ($this->validFrom) {
            $query->whereDate('created_at', '>=', $this->validFrom);
        } elseif ($this->validTo) {
            $query->whereDate('created_at', '<=', $this->validTo);
        }

        // Global search
        if (!empty($this->searchTerm)) {
            $likeSearch = '%' . strtolower($this->searchTerm) . '%';
            $searchTerm = $this->searchTerm;

            $query->where(function ($q) use ($likeSearch, $searchTerm) {
                $q->whereRaw('LOWER(survey_code) LIKE ?', [$likeSearch])
                  ->orWhereRaw('LOWER(survey_name) LIKE ?', [$likeSearch])
                  ->orWhere(function ($sub) use ($searchTerm, $likeSearch) {
                      if (is_numeric($searchTerm)) {
                          $sub->where('status', $searchTerm);
                      } else {
                          $sub->whereRaw('LOWER(status::text) LIKE ?', [$likeSearch]);
                      }
                  })
                  ->orWhereRaw('CAST(start_date AS TEXT) LIKE ?', [$likeSearch])
                  ->orWhereRaw('CAST(end_date AS TEXT) LIKE ?', [$likeSearch])
                  ->orWhereHas('createdUser', fn($sub) =>
                      $sub->whereRaw('LOWER(name) LIKE ?', [$likeSearch])
                          ->orWhereRaw('LOWER(username) LIKE ?', [$likeSearch])
                  )
                  ->orWhereHas('updatedUser', fn($sub) =>
                      $sub->whereRaw('LOWER(name) LIKE ?', [$likeSearch])
                          ->orWhereRaw('LOWER(username) LIKE ?', [$likeSearch])
                  )
                  ->orWhereHas('deletedUser', fn($sub) =>
                      $sub->whereRaw('LOWER(name) LIKE ?', [$likeSearch])
                          ->orWhereRaw('LOWER(username) LIKE ?', [$likeSearch])
                  );
            });
        }

        $surveyTypeMap = [
            1 => 'Consumer',
            2 => 'Sensory',
            3 => 'Asset',
        ];

        return $query->orderBy('id', 'desc')->get()->map(function ($item) use ($surveyTypeMap) {
            // These are accessors — called directly, NOT via with()
            $merchandisers = $item->merchandishers->pluck('name')->implode(', ') ?: 'N/A';
            $customers     = $item->customers->pluck('business_name')->implode(', ') ?: 'N/A';
            $assets        = $item->assets->pluck('serial_number')->implode(', ') ?: 'N/A';

            return [
                'Survey Code'  => $item->survey_code  ?? 'N/A',
                'Survey Name'  => $item->survey_name  ?? 'N/A',
                'Start Date'   => $item->start_date   ?? 'N/A',
                'End Date'     => $item->end_date      ?? 'N/A',
                'Survey Type'  => $surveyTypeMap[$item->survey_type] ?? 'N/A',
                'Merchandiser' => $merchandisers,
                'Customer'     => $customers,
                'Asset'        => $assets,
                'Status'       => $item->status == 1 ? 'Active' : 'Inactive',
                'Created By'   => $item->createdUser->name ?? 'N/A',
                'Updated By'   => $item->updatedUser->name ?? 'N/A',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Survey Code',
            'Survey Name',
            'Start Date',
            'End Date',
            'Survey Type',
            'Merchandiser',
            'Customer',
            'Asset',
            'Status',
            'Created By',
            'Updated By',
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