<?php

namespace App\Http\Resources\V1\Assets\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class GetCRFData extends JsonResource
{
    public function toArray($request): array
    {
        $item = $this->resource['chiller_request'];
        $approved = $this->resource['approved_steps'] ?? [];
        $pending = $this->resource['pending_steps'] ?? [];
        $workflowId = $this->resource['workflow_request_id'] ?? null;

        return [
            'workflow_request_id' => $workflowId,
            'approved_steps'      => $approved,
            'pending_steps'       => $pending,

            // include original chiller fields
            'id'                     => $item->id,
            'uuid'                   => $item->uuid,
            'osa_code'               => $item->osa_code,
            'owner_name'             => $item->owner_name,
            'contact_number'         => $item->contact_number,
            'landmark'               => $item->landmark,
            'existing_coolers'       => $item->existing_coolers,
            'outlet_weekly_sale_volume' => $item->outlet_weekly_sale_volume,
            'display_location'       => $item->display_location,
            'chiller_safty_grill'    => $item->chiller_safty_grill,

            'customer' => $item->customer ? [
                'id'   => $item->customer->id,
                'code' => $item->customer->osa_code ?? null,
                'name' => $item->customer->name ?? null,
            ] : null,

            'warehouse' => $item->warehouse ? [
                'id'   => $item->warehouse->id,
                'code' => $item->warehouse->warehouse_code ?? null,
                'name' => $item->warehouse->warehouse_name ?? null,
            ] : null,

            'outlet' => $item->outlet ? [
                'id'   => $item->outlet->id,
                'code' => $item->outlet->outlet_channel_code ?? null,
                'name' => $item->outlet->outlet_channel ?? null,
            ] : null,

            'salesman' => $item->salesman ? [
                'id'   => $item->salesman->id,
                'code' => $item->salesman->osa_code ?? null,
                'name' => $item->salesman->name ?? null,
            ] : null,

            // Continue with all fields
            'manager_sales_marketing'   => $item->manager_sales_marketing,
            'national_id'               => $item->national_id,
            'outlet_stamp'              => $item->outlet_stamp,
            'model'                     => $item->model,
            'hil'                       => $item->hil,
            'ir_reference_no'           => $item->ir_reference_no,
            'installation_done_by'      => $item->installation_done_by,
            'date_lnitial'              => $item->date_lnitial,
            'date_lnitial2'             => $item->date_lnitial2,
            'contract_attached'         => $item->contract_attached,
            'machine_number'            => $item->machine_number,
            'brand'                     => $item->brand,
            'asset_number'              => $item->asset_number,
            'lc_letter'                 => $item->lc_letter,
            'trading_licence'           => $item->trading_licence,
            'password_photo'            => $item->password_photo,
            'outlet_address_proof'      => $item->outlet_address_proof,
            'chiller_asset_care_manager' => $item->chiller_asset_care_manager,

            'national_id_file'          => $item->national_id_file,
            'password_photo_file'       => $item->password_photo_file,
            'outlet_address_proof_file' => $item->outlet_address_proof_file,
            'trading_licence_file'      => $item->trading_licence_file,
            'lc_letter_file'            => $item->lc_letter_file,
            'outlet_stamp_file'         => $item->outlet_stamp_file,
            'sign_customer_file'        => $item->sign_customer_file,

            'chiller_manager_id'        => $item->chiller_manager_id,
            'is_merchandiser'           => $item->is_merchandiser,
            'status'                    => $item->status,
            'fridge_status'             => $item->fridge_status,
            'iro_id'                    => $item->iro_id,
            'stock_share_with_competitor' => $item->stock_share_with_competitor,
            'remark'                    => $item->remark,
            'created_user'              => $item->created_user,
            'updated_user'              => $item->updated_user,
            'deleted_user'              => $item->deleted_user,
            'deleted_at'                => $item->deleted_at,
            'created_at'                => $item->created_at,
            'updated_at'                => $item->updated_at,
        ];
    }
}
