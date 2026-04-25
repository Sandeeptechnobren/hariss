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
    protected $fromDate, $toDate, $requestStatus, $warehouseIds;

    public function __construct($fromDate, $toDate, $requestStatus, $warehouseIds)
    {
        $this->fromDate      = $fromDate;
        $this->toDate        = $toDate;
        $this->requestStatus = $requestStatus;
        $this->warehouseIds  = $warehouseIds;
    }

    public function query()
    {
        $user   = auth()->user();
        $query = ChillerRequest::with([
            'iro:id,status',
            'customer:id,name,osa_code,owner_name,street,district,contact_no,contact_no2,route_id,fridge_id',
            'customer.route:id,route_code,route_name',
            'outlet:id,outlet_channel',
            'customer.fridge' => function ($q) {
                $q->select('id', 'customer_id', 'osa_code', 'serial_number', 'model_number', 'branding')
                    ->withoutGlobalScopes()
                    ->with([
                        'assetsCategory:id,name',
                        'modelNumber:id,name',
                        'brand:id,name'
                    ]);;
            },
            'warehouse:id,warehouse_name,warehouse_code,area_id',
            'warehouse.area:id,area_code,area_name,region_id',
            'warehouse.area.region:id,region_name',
            'salesman:id,osa_code,name',
        ]);

        /** ✅ DataAccessHelper */
        $query = DataAccessHelper::filterAgentTransaction($query, $user);

        /** ✅ DATE */
        $query->when(
            $this->fromDate,
            fn($q) =>
            $q->whereDate('created_at', '>=', $this->fromDate)
        );

        $query->when(
            $this->toDate,
            fn($q) =>
            $q->whereDate('created_at', '<=', $this->toDate)
        );

        /** ✅ STATUS */
        if (!empty($this->requestStatus)) {
            $query->whereIn('status', $this->requestStatus);
        }

        /** ✅ ONLY FINAL FILTER */
        if (!empty($this->warehouseIds)) {
            $query->whereIn('warehouse_id', $this->warehouseIds);
        }

        return $query->orderByDesc('id');
    }
    public function map($ch): array
    {
        $area   = $ch->warehouse?->area;
        $region = $area?->region;

        $asm = $area?->getAsmUser();
        $rsm = $region ? $region->getRmUser() : null;

        $asmValue = $asm
            ? trim(($asm->user_code ?? '') . ' - ' . ($asm->name ?? ''))
            : '';

        $rsmValue = $rsm
            ? trim(($rsm->user_code ?? '') . ' - ' . ($rsm->name ?? ''))
            : '';

        $fridge = $ch->customer?->fridge;
        return [
            $ch->created_at
                ? \Carbon\Carbon::parse($ch->created_at)->format('d M Y')
                : '',

            $ch->osa_code,

            optional($ch->outlet)->outlet_channel ?? '',

            // Customer (Merged)
            trim(
                (optional($ch->customer)->osa_code ?? '') . ' - ' .
                    (optional($ch->customer)->name ?? '')
            ),

            optional($ch->customer)->owner_name,
            optional($ch->customer)->street,
            optional($ch->customer)->district,
            optional($ch->customer)->contact_no,
            // optional($ch->customer)->contact_no2,

            // Warehouse (Merged)
            trim(
                (optional($ch->warehouse)->warehouse_code ?? '') . ' - ' .
                    (optional($ch->warehouse)->warehouse_name ?? '')
            ),

            // Route (Merged)
            trim(
                (optional($ch->customer->route)->route_code ?? '') . ' - ' .
                    (optional($ch->customer->route)->route_name ?? '')
            ),

            // Salesman (Merged)
            trim(
                (optional($ch->salesman)->osa_code ?? '') . ' - ' .
                    (optional($ch->salesman)->name ?? '')
            ),

            $asmValue,
            $rsmValue,

            optional($fridge)->osa_code ?? '',
            optional($fridge)->serial_number ?? '',
            optional($fridge?->modelNumber)->name ?? '',
            optional($fridge?->brand)->name ?? '',
        ];
    }

    public function headings(): array
    {
        return [
            'Date',
            'CRF',
            'Outlet Type',
            'Customer',
            'Owner Name',
            'City',
            'District',
            'Contact No',
            // 'Phone 2',
            'Warehouse',
            'Route',
            'Salesman',
            'ASM',
            'RSM',
            'Fridge Code',
            'Serial Number',
            'Model Number',
            'Type',
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
