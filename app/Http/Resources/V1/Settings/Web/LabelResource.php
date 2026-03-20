<?php
// app/Http/Resources/LabelResource.php
namespace App\Http\Resources\V1\Settings\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class LabelResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'osa_code' => $this->osa_code,
            'name' => $this->name,
            'status' => $this->status,
        ];
    }
}
