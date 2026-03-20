<?php

namespace App\Exports;

use App\Models\Agent_Transaction\InvoiceHeader;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Carbon;

class InvoiceHeaderWarehouseExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $warehouseId;

    public function __construct($warehouseId)
    {
        $this->warehouseId = $warehouseId;
    }

    public function collection()
    {
        return InvoiceHeader::where('warehouse_id', $this->warehouseId)
            ->get()
            ->map(function ($invoice) {
                return [
                    'invoice_code'    => $invoice->invoice_code,
                    'currency_name'   => $invoice->currency_name,
                    'company_code'    => $invoice->company->company_code,
                    'company_name'    => $invoice->company->company_name,

                    'order_number'    => $invoice->order_number,
                    'order_code'      => $invoice->order->order_code ?? null,
                    'delivery_number' => $invoice->delivery_number,
                    'delivery_code'   => $invoice->delivery->delivery_code ?? null,

                    'warehouse_code'  => $invoice->warehouse->warehouse_code ?? null,
                    'warehouse_name'  => $invoice->warehouse->warehouse_name ?? null,
                    'warehouse_town_village' => $invoice->warehouse->town_village ?? null,
                    'warehouse_street'   => $invoice->warehouse->street ?? null,
                    'warehouse_landmark' => $invoice->warehouse->landmark ?? null,
                    'warehouse_address' => $invoice->warehouse->address ?? null,
                    'warehouse_city'     => $invoice->warehouse->city ?? null,
                    'warehouse_tin_no'   => $invoice->warehouse->tin_no ?? null,
                    'warehouse_contact'  => $invoice->warehouse->warehouse_manager_contact ?? null,
                    'warehouse_email'    => $invoice->warehouse->warehouse_email ?? null,

                    'route_code'      => $invoice->route->route_code ?? null,
                    'route_name'      => $invoice->route->route_name ?? null,

                    'customer_code'   => $invoice->customer->osa_code ?? null,
                    'customer_name'   => $invoice->customer->name ?? null,
                    'customer_street' => $invoice->customer->street ?? null,
                    'customer_town'   => $invoice->customer->town ?? null,
                    'customer_landmark' => $invoice->customer->landmark ?? null,
                    'customer_district' => $invoice->customer->district ?? null,
                    'customer_vat' => $invoice->customer->vat_no ?? null,

                    'salesman_code'   => $invoice->salesman->osa_code ?? null,
                    'salesman_name'   => $invoice->salesman->name ?? null,

                    'invoice_date'    => Carbon::parse($invoice->invoice_date)->format('Y-m-d'),
                    'invoice_time'    => Carbon::parse($invoice->invoice_time)->format('H:i:s'),

                    'invoice_type'    => $invoice->invoice_type,
                    'gross_total'     => $invoice->gross_total,
                    'vat'             => $invoice->vat,
                    'pre_vat'         => $invoice->pre_vat,
                    'net_total'       => $invoice->net_total,
                    'promotion_total' => $invoice->promotion_total,
                    'discount'        => $invoice->discount,
                    'total_amount'    => $invoice->total_amount,
                    'status'          => $invoice->status == 1 ? 'Active' : 'Inactive',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'invoice_code','currency_name','Company Code','Company Name',
            'order_number','order_code','delivery_number','delivery_code','warehouse_code','warehouse_name','warehouse_town_village',
            'warehouse_street','warehouse_landmark','warehouse_address','warehouse_city',
            'warehouse_tin_no','warehouse_contact','warehouse_email','route_code','route_name','customer_code','customer_name','customer_street','customer_town',
            'customer_landmark','customer_district','customer_vat','salesman_code','salesman_name',
            'invoice_date','invoice_time','invoice_type',
            'gross_total','vat','pre_vat','net_total','promotion_total',
            'discount','total_amount','status'
        ];
    }
}
