<?php
namespace App\Services\V1\Assets\Mob;

use App\Models\ServiceVisit;
use App\Http\Requests\V1\Assets\Mob\ServiceVisitRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\AssetBranding;
use App\Models\Spare;

class ServiceVisitService
{
public function create(ServiceVisitRequest $request): ServiceVisit
{
    return DB::transaction(function () use ($request) {

        $data = $request->validated();

        $imageFields = [
            'scan_image',
            'is_machine_in_working_img',
            'cleanliness_img',
            'condensor_coil_cleand_img',
            'gaskets_img',
            'light_working_img',
            'branding_no_img',
            'propper_ventilation_available_img',
            'leveling_positioning_img',
            'stock_availability_in_img',
            'cooler_image',
            'cooler_image2',
            'type_details_photo1',
            'type_details_photo2',
            'customer_signature'
        ];
        $now = Carbon::now('Africa/Kampala');
        foreach ($imageFields as $field) {
            if ($request->hasFile($field)) {

                $file = $request->file($field);

                $filename = uniqid() . '.' . $file->getClientOriginalExtension();

                $file->move(storage_path('app/public/service_visit'), $filename);

                $data[$field] = 'storage/service_visit/' . $filename;
            }
        }
        $serviceVisit = ServiceVisit::create($data);
        if (
            $data['ticket_type'] === 'BD' &&
            in_array($data['work_status'], ['Closed'])
        ) {
            DB::table('tbl_call_register')
                ->where('id', $data['nature_of_call_id'])
                ->update([
                    'status' => 'Closed By Technician', 
                    'completion_date' => $now,
                ]);
        }
        return $serviceVisit;
    });
}
public function getAll()
    {
        return AssetBranding::select('id', 'osa_code','name','status')
            ->get();
    }
 public function spare()
    {
        return Spare::select('id', 'osa_code','spare_name','plant')
            ->get();
    }
}