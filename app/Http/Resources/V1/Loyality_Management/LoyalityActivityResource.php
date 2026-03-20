<?php

namespace App\Http\Resources\V1\Loyality_Management;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Agent_Transaction\InvoiceHeader;
use App\Models\Loyality_Management\Adjustment;

class LoyalityActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
            return [
                'id'              => $this->id,
                'uuid'            => $this->uuid,
                'osa_code'        => $this->osa_code,
                'customer_id'     => $this->customer_id,
                'customer_code'   => $this->customer->osa_code ?? null,
                'customer_name'   => $this->customer->name ?? null,
                'activity_date'   => $this->activity_date,
                'activity_type'   => $this->activity_type,
                'invoice_ids'     => json_decode($this->invoice_id, true),

                'documents'       => $this->resolveDocuments(),
                
                'adjustment_point' => $this->adjustment_point,
                'incooming_point'  => $this->incooming_point,
                'outgoing_point'   => $this->outgoing_point,
                'closing_point'    => $this->closing_point,
        ];
    }

private function resolveDocuments()
{
    $ids = json_decode($this->invoice_id, true);
    $ids = is_array($ids) ? $ids : [$ids]; 

    if ($this->activity_type === 'earn') {
        return InvoiceHeader::whereIn('id', $ids)->get()->map(function ($inv) {
            return [
                'id'   => $inv->id,
                'code' => $inv->invoice_code,
                'date' => $inv->invoice_date,
                'type' => 'invoice',
            ];
        });
    }

    if ($this->activity_type === 'adjustment') {
        return Adjustment::whereIn('id', $ids)->get()->map(function ($adj) {
            return [
                'id'          => $adj->id,
                'code'        => $adj->osa_code ?? null,
                'date'        => $adj->created_at,
                'description' => $adj->description,
                'type'        => 'adjustment',
            ];
        });
    }

    return [];
}

}