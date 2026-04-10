<?php

namespace App\Exports;

use App\Models\Agent_Transaction\CapsCollectionHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;
use App\Helpers\DataAccessHelper;
use Illuminate\Support\Facades\Auth;

class CapsCollectionFullExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents
{
    protected $fromDate;
    protected $toDate;
    protected $warehouseIds;
    protected $routeIds;
    protected $salesmanIds;

    public function __construct($fromDate = null, $toDate = null, $warehouseIds = [], $routeIds = [], $salesmanIds = [])
    {
        $today = now()->toDateString();
        $this->fromDate = $fromDate ?: $today;
        $this->toDate   = $toDate   ?: $today;
        $this->warehouseIds = $warehouseIds;
        $this->routeIds = $routeIds;
        $this->salesmanIds = $salesmanIds;
    }
    // private function mapStatus($status)
    // {
    //     return [
    //         1 => 'Active',
    //         2 => 'Closed',
    //         3 => 'Cancelled',
    //     ][$status] ?? 'Unknown';
    // }

    public function collection()
    {
        $rows = [];

        $query = CapsCollectionHeader::with([
            'warehouse',
            'route',
            'customerdata',
            'salesman',
        ])
            ->withSum('details as itemtotal', 'collected_quantity')
            ->when(
                $this->fromDate && $this->toDate,
                fn($q) =>
                $q->whereBetween('created_at', [
                    $this->fromDate . ' 00:00:00',
                    $this->toDate . ' 23:59:59'
                ])
            )

            ->when(
                !empty($this->salesmanIds),
                fn($q) =>
                $q->whereIn('salesman_id', $this->salesmanIds)
            )

            ->when(
                empty($this->salesmanIds) && !empty($this->routeIds),
                fn($q) =>
                $q->whereIn('route_id', $this->routeIds)
            )

            ->when(
                empty($this->salesmanIds) && empty($this->routeIds) && !empty($this->warehouseIds),
                fn($q) =>
                $q->whereIn('warehouse_id', $this->warehouseIds)
            );

        $query = DataAccessHelper::filterAgentTransaction($query, Auth::user());
        $headers = $query->get();

        foreach ($headers as $header) {

            $rows[] = [
                'Code' => (string) $header->code,
                'Date' => $header->created_at
                    ? Carbon::parse($header->created_at)->format('d M Y')
                    : '',

                'Distributer' => trim(
                    ($header->warehouse->warehouse_code ?? '') . ' - ' .
                        ($header->warehouse->warehouse_name ?? '')
                ),

                'Customer' => trim(
                    ($header->customerdata->osa_code ?? '') . ' - ' .
                        ($header->customerdata->name ?? '')
                ),

                'Contact No' => $header->contact_no,

                'Total Qty' => $header->itemtotal ?? 0,
            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Code',
            'Date',
            'Distributer',
            'Customer',
            'Contact No',
            'Total Qty',
            // 'Status',
        ];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $lastColumn = $sheet->getHighestColumn();
            $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'F5F5F5'],], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER,], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '993442'],], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000'],],],]);
            $sheet->getRowDimension(1)->setRowHeight(25);
        },];
    }
}
