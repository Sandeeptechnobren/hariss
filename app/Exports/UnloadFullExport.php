<?php

namespace App\Exports;

use App\Models\Agent_Transaction\UnloadHeader;
use App\Models\Agent_Transaction\UnloadDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class UnloadFullExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $uuid;

    public function __construct($uuid = null)
    {
        $this->uuid = $uuid;
    }
    public function collection()
    {
        $rows = [];
        $query = UnloadHeader::with(['warehouse', 'route', 'salesman', 'projecttype']);
        if ($this->uuid) {
            $query->where('uuid', $this->uuid); 
        }
        $headers = $query->get();
        foreach ($headers as $header) {
            $rows[] = [
                'OSA Code'       => (string)($header->osa_code ?? ''),
                'Unload No'      => (string)($header->unload_no ?? ''),
                'Unload Date'    => (string)($header->unload_date ?? ''),
                'Unload Time'    => (string)($header->unload_time ?? ''),
                'Sync Date'      => (string)($header->sync_date ?? ''),
                'Sync Time'      => (string)($header->sync_time ?? ''),
                'Salesman Type'  => (string)($header->salesman_type ?? ''),
                'Warehouse Name' => (string)($header->warehouse->warehouse_name ?? ''),
                'Route Name'     => (string)($header->route->route_name ?? ''),
                'Salesman Name'  => (string)($header->salesman->name ?? ''),
                'Project Type'   => (string)($header->projecttype->salesman_type_name ?? ''),
                'Latitude'       => (string)($header->latitude ?? ''),
                'Longitude'      => (string)($header->longtitude ?? ''),
                'Unload From'    => (string)($header->unload_from ?? ''),
                'Load Date'      => (string)($header->load_date ?? ''),
                'Status'         => (string)($header->status == 1 ? 'Active' : 'Inactive'),

                'Item Code'      => '',
                'Item Name'      => '',
                'UOM Name'       => '',
                'Quantity'       => '',
                'Detail Status'  => '',
            ];

            // Detail rows
            $details = UnloadDetail::with(['item', 'itemuom'])
                ->where('header_id', $header->id)
                ->get();

            foreach ($details as $detail) {
                $rows[] = [
                    'OSA Code'       => '',
                    'Unload No'      => '',
                    'Unload Date'    => '',
                    'Unload Time'    => '',
                    'Sync Date'      => '',
                    'Sync Time'      => '',
                    'Salesman Type'  => '',
                    'Warehouse Name' => '',
                    'Route Name'     => '',
                    'Salesman Name'  => '',
                    'Project Type'   => '',
                    'Latitude'       => '',
                    'Longitude'      => '',
                    'Unload From'    => '',
                    'Load Date'      => '',
                    'Status'         => '',

                    'Item Code'      => (string)($detail->item->code ?? ''),
                    'Item Name'      => (string)($detail->item->name ?? ''),
                    'UOM Name'       => (string)($detail->itemuom->name ?? ''),
                    'Quantity'       => (float)($detail->qty ?? 0),
                    'Detail Status'  => (string)($detail->status == 1 ? 'Active' : 'Inactive'),
                ];
            }

            // Empty separator row
            $rows[] = array_fill_keys($this->headings(), '');
        }

        return new Collection($rows);
    }

    /**
     * Define column headings
     */
    public function headings(): array
    {
        return [
            'OSA Code',
            'Unload No',
            'Unload Date',
            'Unload Time',
            'Sync Date',
            'Sync Time',
            'Salesman Type',
            'Warehouse Name',
            'Route Name',
            'Salesman Name',
            'Project Type',
            'Latitude',
            'Longitude',
            'Unload From',
            'Load Date',
            'Status',
            'Item Code',
            'Item Name',
            'UOM Name',
            'Quantity',
            'Detail Status',
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
                        'bold' => true,
                        'color' => ['rgb' => 'F5F5F5'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'], 
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}