<?php

namespace App\Exports;

use App\Models\Agent_Transaction\UnloadHeader;
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

class UnloadHeaderFullExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents
{
    public function collection()
    {
        $rows = [];

        $query = UnloadHeader::with([
            'warehouse',
            'route',
            'salesman',
            'projecttype'
        ]);

        $query = DataAccessHelper::filterAgentTransaction($query, Auth::user());
        $unloads = $query->get();


        foreach ($unloads as $unload) {

            $rows[] = [
                'OSA Code'    => (string) ($unload->osa_code ?? ''),
                'Unload No'   => (string) ($unload->unload_no ?? ''),
                'Unload Date' => (string) ($unload->unload_date ?? ''),
                'Unload Time' => (string) ($unload->unload_time ?? ''),
                'Warehouse' => trim(
                    ($unload->warehouse->warehouse_code ?? '') . ' - ' .
                    ($unload->warehouse->warehouse_name ?? '')
                ),

                'Route' => trim(
                    ($unload->route->route_code ?? '') . ' - ' .
                    ($unload->route->route_name ?? '')
                ),

                'Salesman' => trim(
                    ($unload->salesman->osa_code ?? '') . ' - ' .
                    ($unload->salesman->name ?? '')
                ),
                'Load Date' => (string) ($unload->load_date ?? ''),
                'Status'    => $unload->status == 1 ? 'Active' : 'Inactive',
            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'OSA Code',
            'Unload No',
            'Unload Date',
            'Unload Time',
            'Warehouse',
            'Route',
            'Salesman',
            'Load Date',
            'Status',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();

                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => 'F5F5F5'],
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
                            'color'       => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}
