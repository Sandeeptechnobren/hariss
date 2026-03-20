<?php

namespace App\Http\Resources\V1\Assets\Web;

use App\Models\Vendor;
use Illuminate\Http\Resources\Json\JsonResource;

class ChillerResource extends JsonResource
{
    public function toArray($request)
    {
        // Get vendor IDs as array
        $vendorIds = $this->vender_details
            ? explode(',', $this->vender_details)
            : [];

        // Fetch vendor details
        $vendors = Vendor::whereIn('id', $vendorIds)->get(['id', 'code', 'name']);

        return [
            'id'            => $this->id,
            'uuid'          => $this->uuid,
            'fridge_code'   => $this->fridge_code,
            'serial_number' => $this->serial_number,
            'asset_number'  => $this->asset_number,
            'model_number'  => $this->model_number,
            'description'   => $this->description,
            'acquisition'   => $this->acquisition,
            'vender_details' => $vendors, // array of related vendor objects
            'manufacturer'  => $this->manufacturer,
            'country_id'    => $this->country_id,
            'country' => $this->country ? [
                'id' => $this->country->id,
                'code' => $this->country->country_code ?? null,
                'name' => $this->country->country_name ?? null,
            ] : null,
            'type_name'     => $this->type_name,
            'sap_code'      => $this->sap_code,
            'status'        => $this->status,
            'is_assign'     => $this->is_assign,
            'customer_id'   => $this->customer_id,
            'agreement_id'  => $this->agreement_id,
            'document_type' => $this->document_type,
            'document_id'   => $this->document_id,
        ];
    }
}
