<?php

namespace App\Http\Resources\V1\Assets\Mob;

use Illuminate\Http\Resources\Json\JsonResource;

class CallRegisterResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            // Primary
            'id'              => $this->id,
            'osa_code'        => $this->osa_code,

            // Ticket Information
            'ticket_type'     => $this->ticket_type,
            'ticket_date'     => $this->ticket_date,
            'call_category'   => $this->call_category,
            'status'          => $this->status,
            'followup_status' => $this->followup_status,
            'reason_for_cancelled' => $this->reason_for_cancelled,

            // Technician
            'technician_id'   => $this->technician_id,
            // 'technician_name'   => $this->technician->name ?? NULL,
            // 'technician_code'   => $this->technician->osa_code ?? NULL,
            'ctc_status'      => $this->ctc_status,
            'sales_valume'    => $this->sales_valume,
            // Chiller Information
            'asset_number' => $this->asset_number,
            'model_number' => $this->model_number,
            'serial_number' => $this->chiller_serial_number,
            'chiller_code'          => $this->chiller_code,
            'branding' => $this->branding,

            // Assigned Customer Detail sassignedCustomer
            'assigned_customer' => $this->assigned_customer_id,
            // 'outlet_code'     => $this->outlet_code,
            // 'outlet_name'     => $this->outlet_name,
            // 'owner_name'      => $this->owner_name,
            // 'road_street'     => $this->road_street,
            // 'town'            => $this->town,
            // 'landmark'        => $this->landmark,
            // 'district'        => $this->district,
            // 'contact_no1'     => $this->contact_no1,
            // 'contact_no2'     => $this->contact_no2,

            // Current Customer Details
            'current_outlet_code'    => $this->current_outlet_code,
            'current_outlet_name'    => $this->current_outlet_name,
            'current_owner_name'     => $this->current_owner_name,
            'current_road_street'    => $this->current_road_street,
            'current_town'           => $this->current_town,
            'current_landmark'       => $this->current_landmark,
            'current_district'       => $this->current_district,
            'current_contact_no1'    => $this->current_contact_no1,
            'current_contact_no2'    => $this->current_contact_no2,
            'current_warehouse'      => $this->current_warehouse,
            'current_asm'            => $this->current_asm,
            'current_rm'             => $this->current_rm,

            // Complaint
            'nature_of_call'  => $this->nature_of_call,
            'follow_up_action' => $this->follow_up_action,

            // // Audit Fields
            // 'created_user'    => $this->created_user,
            // 'updated_user'    => $this->updated_user,
            // 'deleted_user'    => $this->deleted_user,
            // 'created_at'      => $this->created_at,
            // 'updated_at'      => $this->updated_at,
            // 'deleted_at'      => $this->deleted_at,
            // 'approval_status' => $this->approval_status,
            // 'current_step'    => $this->current_step,
            // 'request_step_id' => $this->request_step_id,
            // 'progress'        => $this->progress,
        ];
    }
}
