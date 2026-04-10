<?php

namespace App\Exports;

use App\Models\Planogram;
use App\Models\Salesman;
use App\Models\CompanyCustomer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PlanogramExportxl implements FromCollection, WithHeadings, WithEvents
{
    protected ?string $searchTerm;

    public function __construct(?string $searchTerm = null)
    {
        $this->searchTerm = $searchTerm;
    }

    public function collection()
    {
        
        $query = Planogram::latest();

        if (!empty($this->searchTerm)) {
            $s = strtolower($this->searchTerm);

            $query->where(function ($q) use ($s) {
                $q->orWhereRaw("LOWER(CAST(id AS TEXT)) LIKE ?", ["%{$s}%"])
                  ->orWhereRaw("LOWER(name) LIKE ?", ["%{$s}%"])
                  ->orWhereRaw("LOWER(CAST(valid_from AS TEXT)) LIKE ?", ["%{$s}%"])
                  ->orWhereRaw("LOWER(CAST(valid_to AS TEXT)) LIKE ?", ["%{$s}%"]);
            });
        }

        return $query->get()->map(function ($item) {
            $merchIds = is_array($item->merchendisher_id)
                ? $item->merchendisher_id
                : explode(',', $item->merchendisher_id ?? '');

            $custIds = is_array($item->customer_id)
                ? $item->customer_id
                : explode(',', $item->customer_id ?? '');

            $merchNames = Salesman::whereIn('id', array_filter($merchIds))
                ->pluck('name')->implode(', ');

            $customerNames = CompanyCustomer::whereIn('id', array_filter($custIds))
                ->pluck('business_name')->implode(', ');

            $imageList = $item->images
                ? collect(explode(',', $item->images))->implode(' | ')
                : 'N/A';

            return [
                'Code'          => $item->code,
                'Name'          => $item->name,
                'Valid From'    => $item->valid_from,
                'Valid To'      => $item->valid_to,
                'Merchendisher' => $merchNames ?: 'N/A',
                'Customer'      => $customerNames ?: 'N/A',
                'Images'        => $imageList,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Name',
            'Valid From',
            'Valid To',
            'Merchendisher',
            'Customer',
            'Images',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet      = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();
                $lastRow    = $sheet->getHighestRow();

                // Header row styling
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

                // Auto-fit column widths
                foreach (range('A', $lastColumn) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Freeze header row
                $sheet->freezePane('A2');
            },
        ];
    }
}