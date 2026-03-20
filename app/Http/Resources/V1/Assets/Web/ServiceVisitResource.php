<?php

namespace App\Http\Resources\V1\Assets\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceVisitResource extends JsonResource
{
    private function mapStatus($ct_status)
    {
        return [
            0  => "Missmatch Outlet",
            1  => "Same Outlet",
        ][$ct_status] ?? "Unknown";
    }
    public function toArray($request)
    {
        $assignedWarehouse = $this->customer?->getWarehouse;

        $assignedArea   = $assignedWarehouse?->area;
        $assignedRegion = $assignedArea?->region;

        $assignedAsm = $assignedArea?->getAsmUser();
        $assignedRm  = $assignedRegion?->getRmUser();
        // $customer = optional(optional($this->natureOfCall)->assignedCustomer);
        $customer = $this->customer;
        return [

            // PRIMARY
            'id'               => $this->id,
            'uuid'               => $this->uuid,
            'osa_code'           => $this->osa_code,

            // BASIC INFO
            'ticket_type'        => $this->ticket_type,
            'ticket_status'        => $this->ticket_status,
            'time_in'            => $this->time_in,
            'time_out'           => $this->time_out,
            'ct_status'     => $this->mapStatus($this->ct_status) ?? NULL,
            // 'ct_status'          => $this->ct_status,

            // ASSET INFORMATION
            'model_no'           => $this->model_no,
            'asset_no'           => $this->asset_no,
            'serial_no'          => $this->serial_no,
            'branding'           => $this->branding,
            'chiller_id' => [
                'id'       => $this->fridge->id ?? NULL,
                'code'     => optional($this->fridge)->osa_code ?? null,
            ],
            // 'chiller_id'           => $this->fridge->id ?? Null,
            // 'chiller_code'           => $this->fridge->osa_code ?? Null,
            'scan_image'         => $this->scan_image,

            'outlet_id'     => $this->outlet_id,
            'outlet_code'   => $this->outlet_code,
            'outlet_name'   => $this->outlet_name,
            'owner_name'         => $this->owner_name,
            'landmark'           => $this->landmark,
            'location'           => $this->location,
            'town_village'       => $this->town_village,
            'street'           => $this->street,
            'district'           => $this->district,
            // 'warehouse_code' => $this->customer->getWarehouse->warehouse_code ?? NULL,
            // 'warehouse_name' => $this->customer->getWarehouse->warehouse_name ?? NULL,
            // ✅ Warehouse from Customer
            'warehouse_code' => $assignedWarehouse?->warehouse_code,
            'warehouse_name' => $assignedWarehouse?->warehouse_name,

            // ✅ ASM (Area based)
            'current_asm' => [
                'id'   => $assignedAsm?->id,
                'name' => $assignedAsm?->name,
                'code' => $assignedAsm?->username,
            ],

            // ✅ RM (Region based)
            'current_rm' => [
                'id'   => $assignedRm?->id,
                'name' => $assignedRm?->name,
                'code' => $assignedRm?->username,
            ],
            // CONTACTS
            'contact_no'         => $this->contact_no,
            'contact_no2'        => $this->contact_no2,
            'contact_person'     => $this->contact_person,

            // GEO
            'longitude'          => $this->longitude,
            'latitude'           => $this->latitude,

            // TECHNICIAN (RELATION)
            'technician' => [
                'id'       => $this->technician_id,
                'name'     => optional($this->technician)->name ?? null,
                'code'     => optional($this->technician)->osa_code ?? null,
            ],

            // MACHINE CHECKS (YES/NO FIELDS)
            'is_machine_in_working'          => $this->is_machine_in_working,
            'cleanliness'                    => $this->cleanliness,
            'condensor_coil_cleand'          => $this->condensor_coil_cleand,
            'gaskets'                        => $this->gaskets,
            'light_working'                  => $this->light_working,
            'branding_no'                    => $this->branding_no,
            'propper_ventilation_available'  => $this->propper_ventilation_available,
            'leveling_positioning'           => $this->leveling_positioning,
            'stock_availability_in'          => $this->stock_availability_in,


            'is_machine_in_working_img'       => $this->is_machine_in_working_img,
            'cleanliness_img'                 => $this->cleanliness_img,
            'condensor_coil_cleand_img'       => $this->condensor_coil_cleand_img,
            'gaskets_img'                     => $this->gaskets_img,
            'light_working_img'               => $this->light_working_img,
            'branding_no_img'                 => $this->branding_no_img,
            'propper_ventilation_available_img' => $this->propper_ventilation_available_img,
            'leveling_positioning_img'        => $this->leveling_positioning_img,
            'stock_availability_in_img'       => $this->stock_availability_in_img,
            'cooler_image'                    => $this->cooler_image,
            'cooler_image2'                   => $this->cooler_image2,
            'type_details_photo1'             => $this->type_details_photo1,
            'type_details_photo2'             => $this->type_details_photo2,
            'customer_signature'              => $this->customer_signature,

            // COMPLAINT DETAILS
            // 'complaint_type'      => $this->complaint_type,
            'comment'             => $this->comment,
            'cts_comment'         => $this->cts_comment,
            'spare_part_used'     => $this->spare_part_used,
            'pending_other_comment' => $this->pending_other_comment,
            'any_dispute'         => $this->any_dispute,

            // ELECTRIC DATA
            'current_voltage'     => $this->current_voltage,
            'amps'                => $this->amps,
            'cabin_temperature'   => $this->cabin_temperature,

            // WORK STATUS
            'work_status'          => $this->work_status,
            'wrok_status_pending_reson' => $this->wrok_status_pending_reson,
            'spare_request'       => $this->spare_request,
            'work_done_type'      => $this->work_done_type,
            'spare_details'       => $this->spare_details,

            // SATISFACTION
            'technical_behavior'  => $this->technical_behavior,
            'service_quality'     => $this->service_quality,

            // NATURE OF CALL (RELATION)
            // 'nature_of_call' => $this->nature_of_call_id,

            'call_register' => [
                'id'       => $this->nature_of_call_id,
                'code'     => optional($this->natureOfCall)->osa_code ?? null,
            ],


            'assign_customer' => $customer ? [
                'id'   => $customer->id,
                'code' => $customer->osa_code,
                'name' => $customer->name,
                'owner_name'    => $customer->owner_name,
                'district'      => $customer->district,
                'town'          => $customer->town,
                'landmark'          => $customer->landmark,
                'street'          => $customer->street,
                'contact_no_1'  => $customer->contact_no,
                'contact_no_2'  => $customer->contact_no2,
            ] : null,

            // AUDIT
            'created_user'     => $this->created_user,
            'updated_user'     => $this->updated_user,
            'deleted_user'     => $this->deleted_user,

            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
            'deleted_at'       => $this->deleted_at,
            'approval_status' => $this->approval_status ?? null,
            'current_step'    => $this->current_step ?? null,
            'request_step_id' => $this->request_step_id ?? null,
            'progress'        => $this->progress ?? null,
        ];
    }
}
