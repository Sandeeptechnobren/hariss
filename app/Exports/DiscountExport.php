<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DiscountExport implements FromArray, WithHeadings
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return $this->data->map(function ($discount) {
            return [
                'OSA Code' => $discount->osa_code,
                'Item Name' => $discount->item->name ?? '',
                'Item Category Name' => $discount->item_category->category_name ?? '',
                'Customer Name' => $discount->customer->name ?? '',
                'Outlet Channel Name' => $discount->outlet_channel->outlet_channel ?? '',
                'Discount Type Name' => $discount->discount_type->discount_name ?? '',
                'Discount Value' => $discount->discount_value,
                'Min Quantity' => $discount->min_quantity,
                'Min Order Value' => $discount->min_order_value,
                'Start Date' => $discount->start_date,
                'End Date' => $discount->end_date,
            ];
        })->toArray();
    }

    public function headings(): array
    {
        return [
            'OSA Code',
            'Item',
            'Item Category',
            'Customer',
            'Outlet Channel',
            'Discount Type',
            'Discount Value',
            'Min Quantity',
            'Min Order Value',
            'Start Date',
            'End Date',
        ];
    }
}