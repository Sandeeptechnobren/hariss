<?php

namespace App\Http\Resources\V1\Assets\Web;

use App\Models\Area;
use Illuminate\Http\Resources\Json\JsonResource;

class ChillerRequestResource extends JsonResource
{
    private function mapStatus($statusId)
    {
        return [
            1  => "Sales Team Requested",
            2  => "IRO Created",
            3  => "IR Created",
            4  => "Closed",
            // 2  => "Area Sales Manager Accepted",
            // 3  => "Area Sales Manager Rejected",
            // 4  => "Chiller Officer Accepted",
            // 5  => "Chiller Officer Rejected",
            // 6  => "Completed",
            // 7  => "Chiller Manager Rejected",
            // 8  => "Sales/Key Manager Rejected",
            // 9  => "Refused by Customer",
            // 10 => "Fridge Manager Accepted",
            // 11 => "Fridge Manager Rejected",
        ][$statusId] ?? "Unknown";
    }
    private function resolveWorkflowStatus(): string
    {
        if (is_null($this->progress)) {
            return $this->approval_status ?? 'Pending Approval';
        }

        $approved = 0;
        $total = 0;

        if (!empty($this->progress) && str_contains($this->progress, '/')) {
            [$approved, $total] = array_map('intval', explode('/', $this->progress));
        }

        if ($this->status == 1) {

            if ($approved == 0) {
                return $this->mapStatus(1) ?? 'Pending Approval';
            }

            return $this->approval_status ?? 'Under Approval';
        }

        if ($total > 0 && $approved == $total) {
            return $this->mapStatus($this->status) ?? 'Approved';
        }

        return $this->approval_status ?? 'Pending Approval';
    }

    private function getAsmRsmFromWarehouse()
    {
        if (!$this->warehouse || !$this->warehouse->area_id) {
            return [
                'asm' => null,
                'rsm' => null
            ];
        }

        $area = Area::find($this->warehouse->area_id);

        if (!$area) {
            return [
                'asm' => null,
                'rsm' => null
            ];
        }
        $asm = $area->getAsmUser();

        $region = $area->region;

        $rsm = $region ? $region->getRmUser() : null;

        return [
            'asm' => $asm,
            'rsm' => $rsm
        ];
    }

    public function toArray($request): array
    {
        $users = $this->getAsmRsmFromWarehouse();
        return [
            'id'                     => $this->id,
            'uuid'                   => $this->uuid,
            'osa_code'               => $this->osa_code,
            'owner_name'             => $this->owner_name,
            'contact_number'         => $this->contact_number,
            'landmark'               => $this->landmark,
            'location'               => $this->location,
            'address'         => $this->postal_address,
            'existing_coolers'       => $this->existing_coolers,
            'outlet_weekly_sale_volume' => $this->outlet_weekly_sale_volume,
            'outlet_weekly_sale_volume_current' => $this->outlet_weekly_sale_volume_current,
            'display_location'       => $this->display_location,
            'chiller_safty_grill'    => $this->chiller_safty_grill,
            'chiller_size_requested'    => $this->chiller_size_requested,
            'model' => $this->modelNumber ? [
                'id'       => $this->modelNumber->id,
                'name'     => $this->modelNumber->name ?? null
            ] : null,
            'customer' => $this->customer ? [
                'id'       => $this->customer->id,
                'code'     => $this->customer->osa_code ?? null,
                'name'     => $this->customer->name ?? null,
                'district' => $this->customer->district ?? null
            ] : null,
            "agreement_id" => $this->agreement?->id,
            'customer_last_3_month_sales' => $this->last_three_month_sales ?? 0,
            'warehouse' => $this->warehouse ? [
                'id'   => $this->warehouse->id,
                'code' => $this->warehouse->warehouse_code ?? null,
                'name' => $this->warehouse->warehouse_name ?? null,
            ] : null,
            'asm' => $users['asm'] ? [
                'id'   => $users['asm']->id,
                'name' => $users['asm']->name,
            ] : null,

            'rsm' => $users['rsm'] ? [
                'id'   => $users['rsm']->id,
                'name' => $users['rsm']->name,
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
                'contact_no' => $this->salesman->contact_no ?? null,
            ] : null,
            'manager_sales_marketing'    => $this->manager_sales_marketing,
            'national_id'                => $this->national_id,
            'outlet_stamp'               => $this->outlet_stamp,
            'hil'                        => $this->hil,
            'ir_reference_no'            => $this->ir_reference_no,
            'installation_done_by'       => $this->installation_done_by,
            'date_lnitial'               => $this->date_lnitial,
            'date_lnitial2'              => $this->date_lnitial2,
            'contract_attached'          => $this->contract_attached,
            'machine_number'             => $this->machine_number,
            'asset_number'               => $this->asset_number,
            'lc_letter'                  => $this->lc_letter,
            'trading_licence'            => $this->trading_licence,
            'password_photo'             => $this->password_photo,
            'outlet_address_proof'       => $this->outlet_address_proof,
            'chiller_asset_care_manager' => $this->chiller_asset_care_manager,
            'national_id_file'           => $this->national_id_file,
            'password_photo_file'        => $this->password_photo_file,
            'outlet_address_proof_file'  => $this->outlet_address_proof_file,
            'trading_licence_file'       => $this->trading_licence_file,
            'lc_letter_file'             => $this->lc_letter_file,
            'outlet_stamp_file'          => $this->outlet_stamp_file,
            'sign_customer_file'         => $this->sign__customer_file,
            'chiller_manager_id'         => $this->chiller_manager_id,
            'is_merchandiser'            => $this->is_merchandiser,
            'status' => $this->resolveWorkflowStatus(),
            'fridge_status'              => $this->fridge_status,
            'iro_id'                     => $this->iro_id,
            'stock_share_with_competitor' => $this->stock_share_with_competitor,
            'remark'                     => $this->remark,
            'created_user'               => $this->created_user,
            'updated_user'               => $this->updated_user,
            'deleted_user'               => $this->deleted_user,
            'deleted_at'                 => $this->deleted_at,
            'created_at'                 => $this->created_at,
            'updated_at'                 => $this->updated_at,
            'approval_status' => $this->approval_status ?? null,
            'current_step'    => $this->current_step ?? null,
            'request_step_id' => $this->request_step_id ?? null,
            'progress'        => $this->progress ?? null,
        ];
    }
}
