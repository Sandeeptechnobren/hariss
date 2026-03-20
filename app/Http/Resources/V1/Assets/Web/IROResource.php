<?php

namespace App\Http\Resources\V1\Assets\Web;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V1\Assets\Web\ChillerResource; // ensure correct namespace

class IROResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'header_id'   => $this->header_id,
            'iro_id'      => $this->iro_id,
            'crf_id'      => $this->crf_id,

            // Use GetCRFData Resource for chiller_request data
            'crf_data'    => $this->chillerRequest
                ? new ChillerResource($this->chillerRequest)
                : null,

            'customer_id' => $this->customer_id,
            'model_no'    => $this->model_no,
            'created_user' => $this->created_user,
            'created_at'  => $this->created_at,
        ];
    }
}
