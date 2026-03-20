<?php

namespace App\Http\Resources\V1\Agent_Transaction\Mob;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class InvoiceHeaderResource extends JsonResource
{
public function toArray(Request $request): array
    {
      return [
            'header_id'       => $this->id,
            'invoice_code'    => $this->invoice_code,
            'currency_id'     => $this->currency_id ?? null,
            'currency_name'   => $this->currency_name ?? null,
            'company_id'      => $this->company_id,
            // 'company_name' => $this->company->name ?? null,
            'order_number'    => $this->order_number,
            'order_code'      => $this->order->order_code ?? null,
            'delivery_number' => $this->delivery_number,
            'delivery_code'   => $this->delivery->delivery_code ?? null,
            'warehouse_id'    => $this->warehouse_id,
            'warehouse_code'  => $this->warehouse->warehouse_code ?? null,
            'warehouse_name'  => $this->warehouse->warehouse_name ?? null,
            'route_id'        => $this->route_id,
            'route_code'      => $this->route->route_code ?? null,
            'route_name'      => $this->route->route_name ?? null,
            'customer_id'     => $this->customer_id,
            'customer_code'   => $this->customer->osa_code ?? null,
            'customer_name'   => $this->customer->name ?? null,
            'salesman_id'     => $this->salesman_id,
            'salesman_code'   => $this->salesman->osa_code ?? null,
            'salesman_name'   => $this->salesman->name ?? null,
            'invoice_date'    => Carbon::parse($this->invoice_date)->format('Y-m-d'),
            'invoice_time'    => Carbon::parse($this->invoice_time)->format('H:i:s'),
            'invoice_type'    => $this->invoice_type,
            'gross_total'     => $this->gross_total,
            'vat'             => $this->vat,
            'pre_vat'         => $this->pre_vat,
            'net_total'       => $this->net_total,
            'ura_antifake_code' => $this->ura_antifake_code,
            'ura_invoice_no'    => $this->ura_invoice_no,
            'ura_qr_code'      => $this->ura_qr_code,
            'promotion_total' => $this->promotion_total,
            'discount'        => $this->discount,
            'total_amount'    => $this->total_amount,
            'status'          => $this->status,
            'uuid'            => $this->uuid,
            'details'         => InvoiceDetailResource::collection($this->whenLoaded('details')),
        ];
    }
}
