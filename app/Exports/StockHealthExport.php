<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StockHealthExport implements FromCollection, WithHeadings
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data)->map(function ($item) {
            return [
                'Item ID' => $item->item_id,
                'Item Name' => $item->item_name,
                'Item Code' => $item->item_code,
                'ERP Code' => $item->erp_code,
                'Available Qty' => $item->available_stock_qty,
                'Total Sales' => $item->total_sales,
                'Purchase Qty' => $item->purchase_qty,
                'Avg/Day' => $item->avg_per_day,
                'Required Qty' => $item->required_qty,
                'Health Flag' => $item->health_flag
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Item ID',
            'Item Name',
            'Item Code',
            'ERP Code',
            'Available Qty',
            'Total Sales',
            'Purchase Qty',
            'Avg/Day',
            'Required Qty',
            'Health Flag'
        ];
    }
}