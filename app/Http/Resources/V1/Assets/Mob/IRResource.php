<?php

namespace App\Http\Resources\V1\Assets\Mob;

use Illuminate\Http\Resources\Json\JsonResource;

class IRResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                     => $this->id,
            'uuid'                   => $this->uuid,
            'osa_code'               => $this->osa_code,
            'schedule_date'          => $this->schedule_date,
            'salesman_id'            => $this->salesman_id,
            'status'                 => $this->status,
            'iro_id'                 => $this->iro_id,
            'crf' => $this->iroHeader?->details?->map(function ($detail) {
                $crf = $detail->chillerRequest;
                if (!$crf) return null;
                return [
                            'id'                          => $crf->id,
                            'warehouse_id'                 => $crf->warehouse_id,
                            'owner_name'                   => $crf->owner_name,
                            'salesman_id'                  => $crf->salesman_id,
                            'customer_id'                  => $crf->customer_id,
                            'contact_number'               => $crf->contact_number,
                            'postal_address'               => $crf->postal_address,
                            'landmark'                     => $crf->landmark,
                            'location'                     => $crf->location,
                            'outlet_id'                    => $crf->outlet_id,
                            'existing_coolers'             => $crf->existing_coolers,
                            'stock_share_with_competitor'  => $crf->stock_share_with_competitor,
                            'outlet_weekly_sale_volume'    => $crf->outlet_weekly_sale_volume,
                            'display_location'             => $crf->display_location,
                            'chiller_size_requested'       => $crf->chiller_size_requested,
                            'sign_customer_file'           => $crf->sign__customer_file,
                            'chiller_safty_grill'          => $crf->chiller_safty_grill,
                            'outlet_stamp_file'            => $crf->outlet_stamp_file,
                            'lc_letter_file'               => $crf->lc_letter_file,
                            'trading_licence_file'         => $crf->trading_licence_file,
                            'outlet_address_proof_file'    => $crf->outlet_address_proof_file,
                            'password_photo_file'          => $crf->password_photo_file,
                            'national_id_file'             => $crf->national_id_file,
                            'national_id1_file'            => $crf->national_id1_file,
                            'national_id'                  => $crf->national_id,
                            'password_photo'               => $crf->password_photo,
                            'outlet_address_proof'         => $crf->outlet_address_proof,
                            'outlet_stamp'                 => $crf->outlet_stamp,
                            'lc_letter'                    => $crf->lc_letter,
                            'trading_licence'              => $crf->trading_licence,
                            'is_merchandiser'              => $crf->is_merchandiser,
                            'status'                       => $crf->status,
                            'model'                        => $crf->model,
                ];
            })->filter()->values(),
            'details' => $this->details? $this->details->map(function ($detail) {
                    return [
                        'id'        => $detail->id,
                        'header_id' => $detail->header_id,
                        'fridge' => $detail->fridge ? [
                            'id'             => $detail->fridge->id,
                            'osa_code'       => $detail->fridge->osa_code,
                            'sap_code'       => $detail->fridge->sap_code,
                            'serial_number'  => $detail->fridge->serial_number,
                            'model_number'   => $detail->fridge->model_number,
                            'assets_category' => $detail->fridge->assetsCategory->name ?? null,
                            'assets_type'    => $detail->fridge->assets_type,
                            'branding'       => $detail->fridge->branding,
                            'acquisition'    => $detail->fridge->acquisition,
                            'capacity'       => $detail->fridge->capacity,
                            'status'         => $detail->fridge->status,
                            // 'warehouse_id'   => $detail->fridge->warehouse_id,
                            // 'customer_id'    => $detail->fridge->customer_id,
                        ] : null,
                    ];
                })
                : [],
        ];
    }
}