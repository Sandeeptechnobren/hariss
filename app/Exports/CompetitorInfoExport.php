<?php

namespace App\Exports;

use App\Models\CompetitorInfo;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class CompetitorInfoExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
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
        $query = CompetitorInfo::select(
                'competitor_infos.company_name',
                'competitor_infos.brand',
                'salesman.name as merchandiser_name',
                'competitor_infos.item_name',
                'competitor_infos.price',
                'competitor_infos.promotion',
                'competitor_infos.notes',
                'competitor_infos.image',
                'competitor_infos.code',
                'competitor_infos.uuid',
                'competitor_infos.id',
                'competitor_infos.created_at',
            )
            ->join('salesman', 'competitor_infos.merchendiser_id', '=', 'salesman.id');

        // Date range filter
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('competitor_infos.created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        } elseif ($this->startDate) {
            $query->whereDate('competitor_infos.created_at', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->whereDate('competitor_infos.created_at', '<=', $this->endDate);
        }

        // Global search — mirrors getAll() search fields
        if (!empty($this->searchTerm)) {
            $like = '%' . strtolower($this->searchTerm) . '%';

            $query->where(function ($q) use ($like) {
                $q->orWhereRaw('LOWER(CAST(competitor_infos.id AS TEXT)) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(CAST(competitor_infos.uuid AS TEXT)) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(competitor_infos.company_name) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(competitor_infos.brand) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(competitor_infos.item_name) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(CAST(competitor_infos.price AS TEXT)) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(competitor_infos.promotion) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(competitor_infos.code) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(salesman.name) LIKE ?', [$like]);
            });
        }

        return $query->latest('competitor_infos.created_at')->get()->map(function ($item) {
            return [
                'Competitor Code'     => $item->code     ?? 'N/A',
                'Company Name'     => $item->company_name     ?? 'N/A',
                'Brand'            => $item->brand            ?? 'N/A',
                'Merchandiser Name'=> $item->merchandiser_name ?? 'N/A',
                'Item Name'        => $item->item_name        ?? 'N/A',
                'Price'            => $item->price            ?? 'N/A',
                'Promotion'        => $item->promotion        ?? 'N/A',
                'Notes'            => $item->notes            ?? 'N/A',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Competitor Code',
            'Company Name',
            'Brand',
            'Merchandiser Name',
            'Item Name',
            'Price',
            'Promotion',
            'Notes',
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