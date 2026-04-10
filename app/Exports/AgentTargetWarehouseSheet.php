<?php

namespace App\Exports;

use App\Models\Warehouse;
use App\Models\Agent_Transaction\AgentTarget;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class AgentTargetWarehouseSheet implements FromArray, WithHeadings, WithTitle, WithStyles
{
    protected $warehouseId;
    protected $filters;

    public function __construct($warehouseId, $filters)
    {
        $this->warehouseId = $warehouseId;
        $this->filters = $filters;
    }

    public function array(): array
    {
        $query = AgentTarget::with(['warehouse', 'item'])
            ->select(
                'warehouse_id',
                'target_month',
                'target_year',
                'item_id',
                DB::raw('SUM(qty) as total_qty')
            )
            ->groupBy('warehouse_id', 'target_month', 'target_year', 'item_id');

        // ✅ APPLY warehouse filter ONLY if exists
        if (empty($this->warehouseId)) {
            $query->orderBy('warehouse_id', 'asc'); // 👈 change to 'desc' if needed
        }

        // ✅ Warehouse sheet → same as before
        if (!empty($this->warehouseId)) {
            $query->where('warehouse_id', $this->warehouseId);
        }

        // ✅ Filters SAME
        if (!empty($this->filters['target_month'])) {
            $query->where('target_month', $this->filters['target_month']);
        }

        if (!empty($this->filters['target_year'])) {
            $query->where('target_year', $this->filters['target_year']);
        }

        $data = $query->get();

        $rows = [];

        foreach ($data as $item) {
            $rows[] = [
                'Year' => $item->target_year,
                'Month' => Carbon::create()
                    ->month((int) $item->target_month)
                    ->format('F'),
                'Warehouse' => ($item->warehouse?->warehouse_code ?? '') .
                    ' - ' . ($item->warehouse?->warehouse_name ?? ''),
                'Item' => ($item->item?->erp_code ?? '') .
                    ' - ' . ($item->item?->name ?? ''),
                'Qty' => (float) $item->total_qty,
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Year',
            'Month',
            'Distributors',
            'Item',
            'Qty',
        ];
    }

    // ✅ Dynamic title (Summary + Warehouse)
    public function title(): string
    {
        if (empty($this->warehouseId)) {
            return 'Summary';
        }

        $warehouse = Warehouse::select('warehouse_code')
            ->where('id', $this->warehouseId)
            ->first();

        return $warehouse?->warehouse_code ?? ('WH_' . $this->warehouseId);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'F5F5F5'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '993442'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(35);
        $sheet->getColumnDimension('E')->setWidth(15);

        $sheet->getRowDimension(1)->setRowHeight(25);

        return [];
    }
}
