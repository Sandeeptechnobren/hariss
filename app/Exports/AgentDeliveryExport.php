<?php

namespace App\Exports;

use App\Models\Agent_Transaction\AgentDeliveryHeaders;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AgentDeliveryExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        $headers = AgentDeliveryHeaders::with('details')->get();

        $data = [];

        foreach ($headers as $header) {
            foreach ($header->details as $detail) {
                $data[] = [
                    'Delivery Code' => $header->delivery_code,
                    'Warehouse ID' => $header->warehouse_id,
                    'Customer ID' => $header->customer_id,
                    'Route ID' => $header->route_id,
                    'Salesman ID' => $header->salesman_id,
                    'Gross Total' => $header->gross_total,
                    'VAT' => $header->vat,
                    'Discount' => $header->discount,
                    'Net Amount' => $header->net_amount,
                    'Item ID' => $detail->item_id,
                    'Quantity' => $detail->quantity,
                    'Item Price' => $detail->item_price,
                    'Detail Total' => $detail->total,
                ];
            }
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'Delivery Code',
            'Warehouse ID',
            'Customer ID',
            'Route ID',
            'Salesman ID',
            'Gross Total',
            'VAT',
            'Discount',
            'Net Amount',
            'Item ID',
            'Quantity',
            'Item Price',
            'Detail Total',
        ];
    }
}
