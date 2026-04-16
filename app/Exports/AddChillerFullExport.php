<?php

namespace App\Exports;

use App\Models\AddChiller;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AddChillerFullExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents
{
    protected $uuid, $warehouseIds, $status, $model;

    public function __construct($uuid = null, $warehouseIds = [], $status = [], $model = [])
    {
        $this->uuid = $uuid;
        $this->warehouseIds = (array) $warehouseIds;
        $this->status = (array) $status;
        $this->model = (array) $model;
    }

    public function collection()
    {
        $query = AddChiller::with([
            'country',
            'vendor',
            'assetsCategory',
            'modelNumber',
            'manufacture',
            'brand',
            'fridgeStatus',
            'warehouse',
            'customer'
        ]);

        if (!empty($this->warehouseIds)) {
            $query->whereIn('warehouse_id', $this->warehouseIds);
        }

        if (!empty($this->status)) {
            $query->whereIn('status', array_map('intval', $this->status));
        }

        if (!empty($this->model)) {
            $query->whereIn('model_number', array_map('intval', $this->model));
        }

        if ($this->uuid) {
            $query->where('uuid', $this->uuid);
        }

        return $query->get()->map(function ($c) {
            return [
                'Chiller Code' => $c->osa_code ?? '',
                'Serial Number' => $c->serial_number ?? '',
                'Acquisition' => $c->acquisition
                    ? Carbon::parse($c->acquisition)->format('d M Y')
                    : '',
                'Chiller Type' => $c->assets_type ?? '',
                'Distributor' => trim(
                    (optional($c->warehouse)->warehouse_code ?? '') . ' - ' .
                        (optional($c->warehouse)->warehouse_name ?? '')
                ),
                'Customer' => trim(
                    (optional($c->customer)->osa_code ?? '') . ' - ' .
                        (optional($c->customer)->name ?? '')
                ),
                'Vendor' => optional($c->vendor)->name ?? '',
                'Chiller Number' => optional($c->assetsCategory)->name ?? '',
                'Model' => optional($c->modelNumber)->name ?? '',
                'Manufacturer' => optional($c->manufacture)->name ?? '',
                'Brand' => optional($c->brand)->name ?? '',
                'Remarks' => $c->remarks ?? '',
                'Capacity' => $c->capacity ?? '',
                'Manufacturing Year' => $c->manufacturing_year ?? '',
                'Status' => optional($c->fridgeStatus)->name ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Chiller Code',
            'Serial Number',
            'Acquisition',
            'Chiller Type',
            'Distributor',
            'Customer',
            'Vendor',
            'Chiller Number',
            'Model',
            'Manufacturer',
            'Brand',
            'Remarks',
            'Capacity',
            'Manufacturing Year',
            'Status',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();
                $lastRow = $sheet->getHighestRow();

                // Header Style
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'],
                    ],
                ]);
                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_NONE);

                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}
