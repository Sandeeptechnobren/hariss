<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ServiceTerritoryExport implements FromArray, WithStyles, WithCustomStartCell, WithColumnWidths
{
    protected $hierarchy;
    protected $territory;

    public function __construct(array $hierarchy, $territory)
    {
        $this->hierarchy = $hierarchy;
        $this->territory = $territory;
    }

    public function startCell(): string
    {
        // Table starts at row 5 (headers at row 5)
        return 'A5';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 35,
            'C' => 50,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Title
        $sheet->mergeCells('A1:C1');
        $sheet->setCellValue('A1', 'Service Territory Report');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(18);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Divider
        $sheet->mergeCells('A2:C2');
        $sheet->setCellValue('A2', '-------------------------------------------------------');

        // Code Line
        $sheet->mergeCells('A3:C3');
        $sheet->setCellValue('A3', 'Code: ' . ($this->territory->osa_code ?? ''));
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header row bold
        $sheet->getStyle('A5:C5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => '4A90E2']
            ]
        ]);

        return [];
    }

    public function array(): array
    {
        $rows = [];

        // Header Row
        $rows[] = ['Region', 'Area', 'Warehouse(s)'];

        foreach ($this->hierarchy as $region) {

            // CASE: Region has NO areas
            if (empty($region['area'])) {
                $rows[] = [
                    ($region['region_code'] ?? '') . ' - ' . ($region['region_name'] ?? ''),
                    '',
                    ''
                ];
                continue;
            }

            foreach ($region['area'] as $area) {

                // CASE: Area has NO warehouses
                if (empty($area['warehouses'])) {

                    $rows[] = [
                        ($region['region_code'] ?? '') . ' - ' . ($region['region_name'] ?? ''),
                        ($area['area_code'] ?? '') . ' - ' . ($area['area_name'] ?? ''),
                        ''
                    ];

                    continue;
                }

                // CASE: Area has warehouses → print each row
                foreach ($area['warehouses'] as $wh) {

                    $rows[] = [
                        ($region['region_code'] ?? '') . ' - ' . ($region['region_name'] ?? ''),
                        ($area['area_code'] ?? '') . ' - ' . ($area['area_name'] ?? ''),
                        ($wh['warehouse_code'] ?? '') . ' - ' . ($wh['warehouse_name'] ?? ''),
                    ];
                }
            }
        }

        return $rows;
    }
}
