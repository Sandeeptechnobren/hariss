<?php

namespace App\Exports;

use App\Models\Agent_Transaction\ExchangeHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ExchangeHeaderFullExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function collection()
    {
        $rows = [];

        $headers = ExchangeHeader::with([
            'country', 'order', 'warehouse', 'route',
            'customer', 'salesman',
            'invoices.item', 'returns.item'
        ])->get();

        foreach ($headers as $header) {
            $invoices = $header->invoices->isNotEmpty() ? $header->invoices : collect([null]);
            $returns  = $header->returns->isNotEmpty() ? $header->returns : collect([null]);

            foreach ($invoices as $invoice) {
                foreach ($returns as $return) {
                    $rows[] = [
                        // === Header details ===
                        'Exchange Code'   => (string) $header->exchange_code,
                        'Currency'        => (string) $header->currency,
                        'Country'         => (string) ($header->country->country_name ?? $header->country->country_code ?? '-'),
                        'Order Code'      => (string) ($header->order->order_code ?? '-'),
                        'Warehouse Name'  => (string) ($header->warehouse->warehouse_name ?? $header->warehouse->warehouse_code ?? '-'),
                        'Route Name'      => (string) ($header->route->route_name ?? $header->route->route_code ?? '-'),
                        'Customer Name'   => (string) ($header->customer->name ?? $header->customer->osa_code ?? '-'),
                        'Salesman Name'   => (string) ($header->salesman->name ?? $header->salesman->osa_code ?? '-'),
                        'Gross Total'     => (float) $header->gross_total,
                        'VAT'             => (float) $header->vat,
                        'Net Amount'      => (float) $header->net_amount,
                        'Total'           => (float) $header->total,
                        'Discount'        => (float) $header->discount,
                        'Status'          => $header->status == 1 ? 'Active' : 'Inactive',

                        // === Invoice ===
                        'Invoice Item Name' => (string) (optional($invoice)->item->name ?? '-'),
                        'Invoice Qty'       => (float) (optional($invoice)->item_quantity ?? 0),
                        'Invoice Total'     => (float) (optional($invoice)->total ?? 0),
                        'Invoice Item Price'=> (float) (optional($invoice)->item_price ?? 0),
                        'Invoice VAT'       => (float) (optional($invoice)->VAT ?? 0),
                        'Invoice Discount'  => (float) (optional($invoice)->discount ?? 0),
                        'Invoice Gross Total'=> (float) (optional($invoice)->gross_total ?? 0),
                        'Invoice Net Total' => (float) (optional($invoice)->net_total ?? 0),
                        'Invoice Is Promotional'=> (string) (optional($invoice)->is_promotional ? 'Yes' : 'No'),

                        // === Return ===
                        'Return Item Name'  => (string) (optional($return)->item->name ?? '-'),
                        'Return Qty'        => (float) (optional($return)->item_quantity ?? 0),
                        'Return Total'      => (float) (optional($return)->total ?? 0),
                        'Return Item Price' => (float) (optional($return)->item_price ?? 0),
                        'Return VAT'        => (float) (optional($return)->VAT ?? 0),
                        'Return Discount'   => (float) (optional($return)->discount ?? 0),
                        'Return Gross Total'=> (float) (optional($return)->gross_total ?? 0),
                        'Return Net Total'  => (float) (optional($return)->net_total ?? 0),
                        'Return Is Promotional'=> (string) (optional($return)->is_promotional ? 'Yes' : 'No'),
                    ];
                }
            }
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Exchange Code',
            'Currency',
            'Country',
            'Order Code',
            'Warehouse Name',
            'Route Name',
            'Customer Name',
            'Salesman Name',
            'Gross Total',
            'VAT',
            'Net Amount',
            'Total',
            'Discount',
            'Status',
            'Invoice Item Name',
            'Invoice Qty',
            'Invoice Total',
            'Invoice Item Price',
            'Invoice VAT',
            'Invoice Discount',
            'Invoice Gross Total',
            'Invoice Net Total',
            'Invoice Is Promotional',
            'Return Item Name',
            'Return Qty',
            'Return Total',
            'Return Item Price',
            'Return VAT',
            'Return Discount',
            'Return Gross Total',
            'Return Net Total',
            'Return Is Promotional',
        ];
    }
}
