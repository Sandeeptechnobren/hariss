<?php
namespace App\Http\Resources\V1\Hariss_Transaction\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class CreditNoteDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'item_id' => $this->item_id,
            'qty' => $this->qty,
            'price' => $this->price,
            'total' => $this->total,
            'batch_no'=>$this->batch_no,
            'net'     =>$this->net,
            'vat'     =>$this->vat,
        ];
    }
}