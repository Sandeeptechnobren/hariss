<?php

namespace App\Exports;

use App\Models\IRDetail;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class IRFullDetailExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $uuid;

    public function __construct($uuid)
    {
        $this->uuid = $uuid;
    }

    private $statusMap = [
        1 => 'Waiting for confirmation from Technician',
        2 => 'Technician Accepted',
        3 => 'Technician Rejected',
        4 => 'Technician Reschedule',
        5 => 'Request for Close',
        6 => 'Closed',
    ];

    public function collection()
    {
        $records = IRDetail::with([
            'header',
            'fridge.assetsCategory',
            'fridge.brand',
            'fridge.customerUpdate',
            'crf.customer.route',
            'crf.fridgeStatuses',
            'crf.warehouse.area.region',
            'crf.route',
            'crf.salesman',
            'crf.modelNumber',
            'crf.outlet'
        ])
            ->whereHas('header', function ($q) {
                $q->where('uuid', $this->uuid);
            })
            ->get();

        return $records->map(function ($item) {

            $crf = $item->crf;
            $fridge = $item->fridge;

            $warehouse = $crf?->warehouse;
            $area = $warehouse?->area;
            $region = $area?->region;

            $installStatus = $crf?->fridgeStatuses
                ? $crf->fridgeStatuses->where('fridge_id', $item->fridge_id)->first()
                : null;

            $installDate = $installStatus?->install_date
                ? Carbon::parse($installStatus->install_date)->format('d M Y')
                : null;

            $route = $crf?->customer?->route;
            $routeValue = ($route?->route_code ?? '') . ' - ' . ($route?->route_name ?? '');

            $customerUpdate = $fridge?->customerUpdate;

            $ACFNo = $customerUpdate?->osa_code ?? '';

            $ACFDate = $customerUpdate?->created_at
                ? Carbon::parse($customerUpdate->created_at)->format('d M Y')
                : '';

            return [

                $installDate,

                $crf?->osa_code,

                $crf?->outlet?->outlet_channel,

                $crf?->modelNumber?->name,

                ($crf?->customer?->osa_code ?? '') . ' - ' . ($crf?->customer?->name ?? ''),

                $crf?->owner_name,

                $crf?->contact_number,

                $crf?->landmark,

                $crf?->customer?->district,

                $crf?->customer?->town,

                ($crf?->salesman?->osa_code ?? '') . ' - ' . ($crf?->salesman?->name ?? ''),

                $routeValue,

                ($warehouse?->warehouse_code ?? '') . ' - ' . ($warehouse?->warehouse_name ?? ''),

                ($area?->area_code ?? '') . ' - ' . ($area?->area_name ?? ''),

                ($region?->region_code ?? '') . ' - ' . ($region?->region_name ?? ''),

                $this->statusMap[$item->status] ?? $item->status,

                $fridge?->osa_code,

                $fridge?->assetsCategory?->name,

                $fridge?->serial_number,

                $fridge?->assets_type,

                $fridge?->brand?->name,

                $ACFNo,

                $ACFDate
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Installation Date',
            'CRF Code',
            'Outlet Type',
            'Size Requested',
            'Customer',
            'Owner Name',
            'Contact Number',
            'Landmark',
            'District',
            'City',
            'Salesman',
            'Route',
            'Distributor',
            'Area',
            'Region',
            'Status',
            'Fridge Code',
            'Chiller Number',
            'Serial Number',
            'Model Type',
            'Brand',
            'ACF Number',
            'ACF Creation Date',
        ];
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();

                // Header Styling
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => '993442'
                        ],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // Row height increase (padding effect)
                $sheet->getRowDimension(1)->setRowHeight(30);
            },
        ];
    }
}
