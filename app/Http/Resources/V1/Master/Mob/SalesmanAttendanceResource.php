<?php

namespace App\Http\Resources\V1\Master\Mob;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesmanAttendanceResource extends JsonResource
{
     public function toArray($request)
    {
        return [
            'id'              => $this->id,
            'uuid'            => $this->uuid,
            'salesman_id'     => $this->salesman_id,
            'route_id'        => $this->route_id,
            'warehouse_id'    => $this->warehouse_id,
            'attendance_date' => $this->attendance_date,
            'time_in'         => $this->time_in,
            'latitude_in'     => $this->latitude_in,
            'longitude_in'    => $this->longitude_in,
            'in_img'          => $this->in_img,
            'time_out'        => $this->time_out,
            'latitude_out'    => $this->latitude_out,
            'longitude_out'   => $this->longitude_out,
            'out_img'         => $this->out_img,
            'check_in'        => $this->check_in,
            'check_out'       => $this->check_out,
            
        ];
    }
}
