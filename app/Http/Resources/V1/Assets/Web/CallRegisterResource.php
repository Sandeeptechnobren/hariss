<?php

namespace App\Http\Resources\V1\Assets\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class CallRegisterResource extends JsonResource
{
    private function acf_status($status)
    {
        return [
            1 => "Sales Team Requested",
            2 => "Area Sales Manager Accepted",
            3 => "Area Sales Manager Rejected",
            4 => "Chiller officer Accepted",
            5 => "Chiller officer Rejected",
            6 => "Completed",
            7 => "Chiller Manager Rejected",
        ][$status] ?? "Unknown";
    }

    private function mapStatus($ctc_status)
    {
        return [
            0  => "Missmatch Outlet",
            1  => "Same Outlet",
        ][$ctc_status] ?? "Unknown";
    }
    public function toArray($request)
    {
        $area   = $this->warehouse?->area;
        $region = $area?->region;
        $asm = $area?->getAsmUser();     // ✅ helper method
        $rm  = $region?->getRmUser();
        // dd($rm);

        return [
            'id'              => $this->id,
            'uuid'            => $this->uuid,
            'osa_code'        => $this->osa_code,

            'ticket_type'     => $this->ticket_type,
            'ticket_date'     => $this->ticket_date,
            'call_category'   => $this->call_category,
            'status'          => $this->status,
            'followup_status' => $this->followup_status,
            'reason_for_cancelled' => $this->reason_for_cancelled,

            'technician_id'   => $this->technician_id,
            'technician_name'   => $this->technician->name ?? NULL,
            'technician_code'   => $this->technician->osa_code ?? NULL,
            'ctc_status'     => $this->mapStatus($this->ctc_status),
            'sales_valume'    => $this->sales_valume,

            'asset_number' => $this->asset_number,
            'chiller_code' => $this->asset?->osa_code ?? '',
            'chiller_serial_number' => $this->asset?->serial_number ?? '',
            'assign_asm' => [
                'id'   => optional(optional($this->asset->warehouse)?->area?->getAsmUser())->id,
                'name' => optional(optional($this->asset->warehouse)?->area?->getAsmUser())->name,
            ],

            'assign_rm' => [
                'id'   => optional(optional($this->asset->warehouse)?->area?->region?->getRmUser())->id,
                'name' => optional(optional($this->asset->warehouse)?->area?->region?->getRmUser())->name,
            ],

            'model_number' => $this->model_number,

            'branding' => $this->branding,

            'assigned_customer' => $this->assignedCustomer ? [
                'id'            => $this->assignedCustomer->id,
                'name'          => $this->assignedCustomer->name,
                'code'          => $this->assignedCustomer->osa_code,
                'owner_name'    => $this->assignedCustomer->owner_name,
                'district'      => $this->assignedCustomer->district,
                'town'          => $this->assignedCustomer->town,
                'landmark'          => $this->assignedCustomer->landmark,
                'street'          => $this->assignedCustomer->street,
                'contact_no_1'  => $this->assignedCustomer->contact_no,
                'contact_no_2'  => $this->assignedCustomer->contact_no2,
                'warehouse_code' => optional($this->assignedCustomer->getWarehouse)->warehouse_code,
                'warehouse_name' => optional($this->assignedCustomer->getWarehouse)->warehouse_name,
            ] : null,

            'acf_data' => $this->acf_data ? [
                'id'            => $this->acf_data->id,
                'code'          => $this->acf_data->osa_code,
                'status'    => $this->acf_status($this->acf_data->status),
                'warehouse_code' => optional($this->acf_data->warehouse)->warehouse_code,
                'warehouse_name' => optional($this->acf_data->warehouse)->warehouse_name,
                'warehouse_asm' => [
                    'id'   => optional(optional($this->acf_data->warehouse)?->area?->getAsmUser())->id,
                    'name' => optional(optional($this->acf_data->warehouse)?->area?->getAsmUser())->name,
                ],
            ] : null,

            'current_outlet_code'     => $this->current_outlet_code,
            'current_outlet_name'     => $this->current_outlet_name,
            'current_owner_name'     => $this->current_owner_name,
            'current_road_street'    => $this->current_road_street,
            'current_town'           => $this->current_town,
            'current_landmark'       => $this->current_landmark,
            'current_district'       => $this->current_district,
            'current_contact_no1'    => $this->current_contact_no1,
            'current_contact_no2'    => $this->current_contact_no2,
            'current_latitude'  => optional($this->currentCustomer)->latitude,
            'current_longitude' => optional($this->currentCustomer)->longitude,
            'current_warehouse_id'      => $this->warehouse->id ?? '',
            'current_warehouse_code'      => $this->warehouse->warehouse_code ?? '',
            'current_warehouse_name'      => $this->warehouse->warehouse_name ?? '',

            'current_asm' => [
                'id' => $asm?->id,
                'name' => $asm?->name ?? $asm?->name,
            ],

            'current_rm' => [
                'id' => $rm?->id,
                'name' => $rm?->name ?? $rm?->name,
            ],

            // 'current_asm'            => $this->current_asm,
            // 'current_rm'             => $this->current_rm,

            // Complaint
            'service_visits' => $this->serviceVisits
                ? $this->serviceVisits->map(function ($visit) {
                    return [
                        'id'   => $visit->id,
                        'uuid' => $visit->uuid,
                        'code' => $visit->osa_code,
                        'created_at' => $visit->created_at,
                        'resolution_status' => $visit->work_done_type,
                        'spare_request' => $visit->spare_request,
                        'spare_details' => $visit->spare_details,
                    ];
                })->values()
                : [],
            'nature_of_call'  => $this->nature_of_call,
            'follow_up_action' => $this->follow_up_action,
            'created_at'      => $this->created_at,
            // 'updated_at'      => $this->updated_at,
            // 'deleted_at'      => $this->deleted_at,
            'approval_status' => $this->approval_status,
            'current_step'    => $this->current_step,
            'request_step_id' => $this->request_step_id,
            'progress'        => $this->progress,
        ];
    }
}
