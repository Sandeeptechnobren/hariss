<?php

namespace App\Exports;

use App\Models\WarehouseStock;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class WarehouseStockExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $from_date;
    protected $to_date;

    public function __construct($from_date = null, $to_date = null)
    {
        $this->from_date = $from_date;
        $this->to_date   = $to_date;
    }

    public function collection()
    {
        $query = WarehouseStock::with(['warehouse', 'item']);

        if ($this->from_date && $this->to_date) {
            $query->whereBetween('created_at', [$this->from_date, $this->to_date]);
        }

        $stocks = $query->get();
        $rows = [];

        foreach ($stocks as $s) {
            $rows[] = [
                'ID'            => (string) $s->id,
                // 'UUID'          => (string) $s->uuid,
                'OSA Code'      => (string) $s->osa_code,

                // 'Warehouse ID'   => (string) $s->warehouse_id,
                'Warehouse Name' => (string) ($s->warehouse->warehouse_name ?? ''),

                // 'Item ID'        => (string) $s->item_id,
                'Item Name'      => (string) ($s->item->name ?? ''),
                'Item Code'      => (string) ($s->item->erp_code ?? ''),

                'Quantity'       => (int) $s->qty,
                'Status'         => $s->status == 1 ? 'Active' : 'Inactive',
                // 'Created User'   => (string) $s->created_user,
                // 'Updated User'   => (string) $s->updated_user,
                // 'Deleted User'   => (string) $s->deleted_user,

                // 'Created At'     => $s->created_at ? $s->created_at->format('Y-m-d H:i:s') : null,
                // 'Updated At'     => $s->updated_at ? $s->updated_at->format('Y-m-d H:i:s') : null,
                // 'Deleted At'     => $s->deleted_at ? $s->deleted_at->format('Y-m-d H:i:s') : null,
            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'ID',
            // 'UUID',
            'OSA Code',

            // 'Warehouse ID',
            'Warehouse Name',

            // 'Item ID',
            'Item Name',
            'Item Code',

            'Quantity',
            'Status',

            // 'Created User',
            // 'Updated User',
            // 'Deleted User',

            // 'Created At',
            // 'Updated At',
            // 'Deleted At',
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
                        'color' => ['rgb' => 'FFFFFF'],
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
                            'color'       => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}
