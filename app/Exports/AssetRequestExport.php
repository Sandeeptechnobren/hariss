<?php

namespace App\Exports;

use App\Models\ChillerRequest;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Helpers\DataAccessHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AssetRequestExport implements FromQuery, WithMapping, WithHeadings, WithEvents
{
    protected $fromDate, $toDate, $status, $warehouseIds, $salesmanIds, $modelIds;

    public function __construct(
        $status,
        $warehouseIds,
        $salesmanIds,
        $modelIds,
        $fromDate,
        $toDate,
    ) {
        $this->fromDate     = $fromDate;
        $this->toDate       = $toDate;
        $this->status       = $status;
        $this->warehouseIds = $warehouseIds;
        $this->salesmanIds  = $salesmanIds;
        $this->modelIds     = $modelIds;
    }

    public function query()
    {
        $query = ChillerRequest::with([
            'iro:id,status',
            'customer:id,name,osa_code,street,district,contact_no,contact_no2,route_id,fridge_id',
            'customer.route:id,route_code,route_name',
            'customer.fridge:id,osa_code,serial_number,model_number,branding',
            'warehouse:id,warehouse_name,warehouse_code,area_id,region_id',
            'warehouse.area:id,area_code,area_name',
            'salesman:id,osa_code,name',
        ]);

        /** ✅ Data Access Helper */
        $query = DataAccessHelper::apply($query, Auth::user());
        $query->when(
            $this->fromDate,
            fn($q) => $q->whereDate('created_at', '>=', $this->fromDate)
        );

        $query->when(
            $this->toDate,
            fn($q) => $q->whereDate('created_at', '<=', $this->toDate)
        );
        /** FILTERS */
        if ($this->status !== null) {
            $query->where('status', (int)$this->status);
        }

        if (!empty($this->warehouseIds)) {
            $query->whereIn('warehouse_id', $this->warehouseIds);
        }

        if (!empty($this->regionIds)) {
            $query->whereHas('warehouse', function ($q) {
                $q->whereIn('region_id', $this->regionIds);
            });
        }
        if (!empty($this->salesmanIds)) {
            $query->whereIn('salesman_id', $this->salesmanIds);
        }

        if (!empty($this->userIds)) {
            $query = DataAccessHelper::filterByUsers($query, $this->userIds);
        }

        /** MODEL FILTER */
        if (!empty($this->modelIds)) {
            $sizes = DB::table('am_model_number')
                ->whereIn('id', $this->modelIds)
                ->pluck('size')
                ->toArray();

            if (!empty($sizes)) {
                $query->whereHas('customer.fridge', function ($q) use ($sizes) {
                    $q->whereIn('model_number', $sizes);
                });
            }
        }

        return $query->orderByDesc('id');
    }

    public function map($ch): array
    {
        return [
            optional($ch->iro)->status,
            $ch->id,

            optional($ch->customer)->name,
            optional($ch->customer)->osa_code,
            optional($ch->customer)->street,
            optional($ch->customer)->district,
            optional($ch->customer)->contact_no,
            optional($ch->customer)->contact_no2,

            optional($ch->customer->fridge)->osa_code ?? '',
            optional($ch->customer->fridge)->serial_number ?? '',
            optional($ch->customer->fridge)->model_number ?? '',
            optional($ch->customer->fridge)->branding ?? '',

            optional($ch->warehouse)->warehouse_name,
            optional($ch->warehouse)->warehouse_code,

            optional($ch->warehouse->area)->area_code ?? '',
            optional($ch->warehouse->area)->area_name ?? '',

            optional($ch->customer->route)->route_code ?? '',
            optional($ch->customer->route)->route_name ?? '',

            optional($ch->salesman)->osa_code,
            optional($ch->salesman)->name,

            $ch->created_at,
        ];
    }

    public function headings(): array
    {
        return [
            'IRO Status',
            'CRF ID',
            'Customer Name',
            'Customer Code',
            'City',
            'District',
            'Phone 1',
            'Phone 2',
            'Fridge Code',
            'Serial Number',
            'Model Number',
            'Type',
            'Warehouse Name',
            'Warehouse Code',
            'Region Code',
            'Region Name',
            'Route Code',
            'Route Name',
            'Salesman Code',
            'Salesman Name',
            'Created At'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function ($event) {

                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();

                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF'],
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
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}
