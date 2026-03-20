<?php

    namespace App\Http\Resources\V1\Master\Web;

    use Illuminate\Http\Resources\Json\JsonResource;

    class AgentCustomerDropdownResource extends JsonResource
    {
        public function toArray($request)
        {
            return [
                'id'       => $this->id,
                'osa_code' => $this->osa_code,
                'name'     => $this->name,
            ];
        }
    }
