<?php

namespace App\Http\Resources\V1\Settings\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseTypeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'expense_type_code'  => $this->expense_type_code,
            'expense_type_name'  => $this->expense_type_name,
            'expense_type_status'=> $this->expense_type_status,
            'createdBy'       => $this->createdBy,
            'updatedBy'       => $this->updatedBy,
        ];
    }
}
