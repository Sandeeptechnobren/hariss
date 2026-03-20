<?php

// namespace App\Http\Resources\V1\Master\Web;

// use Illuminate\Http\Resources\Json\JsonResource;

// class SalesmanResource extends JsonResource
// {
//     public function toArray($request): array
//     {
//         return [
//             'id'             => $this->id,
//             'uuid'  => $this->uuid,
//             'osa_code'  => $this->osa_code,
//             'name'  => $this->name,
//             'salesman_type' => $this->salesmanType ? [
//                 'id' => $this->salesmanType->id,
//                 'salesman_type_code' => $this->salesmanType->salesman_type_code,
//                 'salesman_type_name' => $this->salesmanType->salesman_type_name
//             ] : null,
//             // 'sub_type'       => $this->sub_type,
//             'designation'    => $this->designation,
//             // 'device_no'      => $this->device_no,
//             'route'          => $this->route ? [
//                 'id' => $this->route->id,
//                 'route_code' => $this->route->route_code,
//                 'route_name' => $this->route->route_name
//             ] : null,
//             'block_date_from' => $this->block_date_from,
//             'block_date_to'  => $this->block_date_to,
//             // 'salesman_role'  => $this->salesman_role,
//             // 'username'       => $this->username,
//             'contact_no'     => $this->contact_no,
//             'warehouse'      => $this->warehouse ? [
//                 'id' => $this->warehouse->id,
//                 'warehouse_code' => $this->warehouse->warehouse_code,
//                 'warehouse_name' => $this->warehouse->warehouse_name
//             ] : null,
//             // 'token_no'       => $this->token_no,
//             // 'sap_id'         => $this->sap_id,
//             'email'          => $this->email,
//             'is_login'       => $this->is_login,
//             // 'security_code'  => $this->security_code,
//             'password'       => $this->password,
//             'is_block'       => $this->is_block,
//             'forceful_login' => $this->forceful_login,
//             'status'         => $this->status,
//             'is_block_reason'=> $this->is_block_reason,
//         ];
//     }
// }


namespace App\Http\Resources\V1\Master\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class SalesmanResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'    => $this->id,
            'uuid'  => $this->uuid,
            'osa_code'  => $this->osa_code,
            'name'  => $this->name,
            'salesman_type' => $this->salesmanType ? [
                'id' => $this->salesmanType->id,
                'salesman_type_code' => $this->salesmanType->salesman_type_code,
                'salesman_type_name' => $this->salesmanType->salesman_type_name
            ] : null,
            'project_type' => $this->subtype ? [
                'id' => $this->subtype->id,
                'code' => $this->subtype->osa_code,
                'name' => $this->subtype->name
            ] : null,
            'designation'    => $this->designation,
            'route'          => $this->route ? [
                'id' => $this->route->id,
                'route_code' => $this->route->route_code,
                'route_name' => $this->route->route_name
            ] : null,
            'block_date_from' => $this->block_date_from,
            'block_date_to'  => $this->block_date_to,
            'contact_no'     => $this->contact_no,
            // 'warehouse'      => $this->warehouse ? [
            //     'id' => $this->warehouse->id,
            //     'warehouse_code' => $this->warehouse->warehouse_code,
            //     'warehouse_name' => $this->warehouse->warehouse_name
            // ] : null,
            'warehouses'     => $this->warehouses_data ? $this->warehouses_data->map(function ($w) {
                                    return [
                                        'id' => $w->id,
                                        'warehouse_code' => $w->warehouse_code,
                                        'warehouse_name' => $w->warehouse_name
                                    ];
                                }) : null,
            'email'          => $this->email,
            'is_login'       => $this->is_login,
            'password'       => $this->password,
            'is_block'       => $this->is_block,
            'forceful_login' => $this->forceful_login,
            'status'         => $this->status,
            'is_take'         => $this->is_take,
            'reason'         => $this->reason,
            'cashier_description_block' => $this->cashier_description_block,
            'invoice_block' => $this->invoice_block
            // 'security_code'  => $this->security_code,
            // 'token_no'       => $this->token_no,
            // 'sap_id'         => $this->sap_id,
            // 'salesman_role'  => $this->salesman_role,
            // 'device_no'      => $this->device_no,
            // 'sub_type'       => $this->sub_type,
        ];
    }
}
