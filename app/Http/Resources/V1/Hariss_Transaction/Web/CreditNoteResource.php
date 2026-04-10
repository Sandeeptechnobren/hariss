<?php
namespace App\Http\Resources\V1\Hariss_Transaction\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class CreditNoteResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'uuid'=>$this->uuid,
            'credit_note_no' => $this->credit_note_no,
            'purchase_invoice_id' => $this->purchase_invoice_id,
            'supplier_id' => $this->supplier_id,
            'customer_id' => $this->customer_id,
            'salesman_id' => $this->salesman_id,
            'distributor_id' => $this->distributor_id,
            'total_amount' => $this->total_amount,
            'reason' => $this->reason,
            'status' => $this->status,

            'details' => CreditNoteDetailResource::collection($this->details),

            'created_at' => $this->created_at,
        ];
    }
}