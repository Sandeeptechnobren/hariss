<?php

namespace App\Http\Resources\V1\Assets\Mob;

use Illuminate\Http\Resources\Json\JsonResource;

class ChillerRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                     => $this->id,
            'uuid'                   => $this->uuid,
            'osa_code'               => $this->osa_code,
            'owner_name'             => $this->owner_name,
            'contact_number'         => $this->contact_number,
            'landmark'               => $this->landmark,
            'location'               => $this->location,
            'postal_address'               => $this->postal_address,
            'existing_coolers'       => $this->existing_coolers,
            'outlet_weekly_sale_volume' => $this->outlet_weekly_sale_volume,
            'display_location'       => $this->display_location,
            'chiller_safty_grill'    => $this->chiller_safty_grill,

            'customer' => $this->customer ? [
                'id'   => $this->customer->id,
                'code' => $this->customer->osa_code ?? null,
                'name' => $this->customer->name ?? null,
                'district' => $this->customer->district ?? null
            ] : null,

            'warehouse' => $this->warehouse ? [
                'id'   => $this->warehouse->id,
                'code' => $this->warehouse->warehouse_code ?? null,
                'name' => $this->warehouse->warehouse_name ?? null,
            ] : null,

            'outlet' => $this->outlet ? [
                'id'   => $this->outlet->id,
                'code' => $this->outlet->outlet_channel_code ?? null,
                'name' => $this->outlet->outlet_channel ?? null,
            ] : null,

            'salesman' => $this->salesman ? [
                'id'   => $this->salesman->id,
                'code' => $this->salesman->osa_code ?? null,
                'name' => $this->salesman->name ?? null,
            ] : null,

            'route' => $this->route ? [
                'id'   => $this->route->id,
                'code' => $this->route->route_code ?? null,
                'name' => $this->route->route_name ?? null,
            ] : null,

            'manager_sales_marketing'   => $this->manager_sales_marketing,
            'national_id'               => $this->national_id,
            'outlet_stamp'              => $this->outlet_stamp,
            'model'                     => $this->model,
            'hil'                       => $this->hil,
            'ir_reference_no'           => $this->ir_reference_no,
            'installation_done_by'      => $this->installation_done_by,
            'date_lnitial'              => $this->date_lnitial,
            'date_lnitial2'             => $this->date_lnitial2,
            'contract_attached'         => $this->contract_attached,
            'machine_number'            => $this->machine_number,
            'brand'                     => $this->brand,
            'asset_number'              => $this->asset_number,
            'lc_letter'                 => $this->lc_letter,
            'trading_licence'           => $this->trading_licence,
            'password_photo'            => $this->password_photo,
            'outlet_address_proof'      => $this->outlet_address_proof,
            'chiller_asset_care_manager' => $this->chiller_asset_care_manager,
            
            // File Fields
            'national_id_file'          => $this->national_id_file,
            'password_photo_file'       => $this->password_photo_file,
            'outlet_address_proof_file' => $this->outlet_address_proof_file,
            'trading_licence_file'      => $this->trading_licence_file,
            'lc_letter_file'            => $this->lc_letter_file,
            'outlet_stamp_file'         => $this->outlet_stamp_file,
            'sign_customer_file'       => $this->sign__customer_file,
            
            // Other Fields
            'chiller_manager_id'        => $this->chiller_manager_id,
            'is_merchandiser'           => $this->is_merchandiser,
            'status'                    => $this->status,
            'fridge_status'             => $this->fridge_status,
            'iro_id'                    => $this->iro_id,
            'stock_share_with_competitor'   => $this->stock_share_with_competitor,
            'remark'                    => $this->remark,
            // 'created_user'              => $this->created_user,
            // 'updated_user'              => $this->updated_user,
            // 'deleted_user'              => $this->deleted_user,
            // 'deleted_at'                => $this->deleted_at,
            // 'created_at'                => $this->created_at,
            // 'updated_at'                => $this->updated_at,
        ];
    }
}