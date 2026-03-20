<?php
namespace App\Http\Controllers\V1\Assets\Mob;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Assets\Mob\ServiceVisitRequest;
use App\Services\V1\Assets\Mob\ServiceVisitService;
use Illuminate\Http\JsonResponse;

class ServiceVisitController extends Controller
{
    protected ServiceVisitService $service;

    public function __construct(ServiceVisitService $service)
    {
        $this->service = $service;
    }
    /**
 * @OA\Post(
 *     path="/mob/technician_mob/service-visit/create",
 *     tags={"Service Visit"},
 *     summary="Create Service Visit",
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"ticket_type","work_status"},
 *
 *                 @OA\Property(property="osa_code", type="string"),
 *                 @OA\Property(property="ticket_type", type="string"),
 *                 @OA\Property(property="time_in", type="string"),
 *                 @OA\Property(property="time_out", type="string"),
 *                 @OA\Property(property="ct_status", type="string"),
 *                 @OA\Property(property="model_no", type="string"),
 *                 @OA\Property(property="asset_no", type="string"),
 *                 @OA\Property(property="serial_no", type="string"),
 *                 @OA\Property(property="branding", type="string"),
 *
 *                 @OA\Property(property="scan_image", type="string", format="binary"),
 *
 *                 @OA\Property(property="outlet_code", type="string"),
 *                 @OA\Property(property="outlet_name", type="string"),
 *                 @OA\Property(property="owner_name", type="string"),
 *                 @OA\Property(property="landmark", type="string"),
 *                 @OA\Property(property="location", type="string"),
 *                 @OA\Property(property="town_village", type="string"),
 *                 @OA\Property(property="street", type="string"),
 *                 @OA\Property(property="district", type="string"),
 *
 *                 @OA\Property(property="contact_no", type="string"),
 *                 @OA\Property(property="contact_no2", type="string"),
 *                 @OA\Property(property="contact_person", type="string"),
 *
 *                 @OA\Property(property="longitude", type="string"),
 *                 @OA\Property(property="latitude", type="string"),
 *                 @OA\Property(property="technician_id", type="integer"),
 *
 *                 @OA\Property(property="is_machine_in_working", type="integer", example=1),
 *                 @OA\Property(property="cleanliness", type="integer", example=1),
 *                 @OA\Property(property="condensor_coil_cleand", type="integer", example=0),
 *                 @OA\Property(property="gaskets", type="integer", example=1),
 *                 @OA\Property(property="light_working", type="integer", example=1),
 *
 *                 @OA\Property(property="cooler_image", type="string", format="binary"),
 *                 @OA\Property(property="cooler_image2", type="string", format="binary"),
 *
 *                 @OA\Property(property="complaint_type", type="string"),
 *                 @OA\Property(property="comment", type="string"),
 *
 *                 @OA\Property(property="any_dispute", type="integer", example=0),
 *                 @OA\Property(property="current_voltage", type="string"),
 *                 @OA\Property(property="amps", type="string"),
 *                 @OA\Property(property="cabin_temperature", type="integer"),
 *
 *                 @OA\Property(property="work_status", type="string", example="pending"),
 *                 @OA\Property(property="wrok_status_pending_reson", type="string"),
 *
 *                 @OA\Property(property="spare_request", type="string"),
 *                 @OA\Property(property="work_done_type", type="string"),
 *                 @OA\Property(property="spare_details", type="string"),
 *
 *                 @OA\Property(property="type_details_photo1", type="string", format="binary"),
 *                 @OA\Property(property="type_details_photo2", type="string", format="binary"),
 *
 *                 @OA\Property(property="technical_behavior", type="string"),
 *                 @OA\Property(property="service_quality", type="string"),
 *                 @OA\Property(property="customer_signature", type="string", format="binary"),
 *
 *                 @OA\Property(property="nature_of_call_id", type="integer")
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Service visit created successfully"
 *     ),
 *
 *     @OA\Response(
 *         response=422,
 *         description="Validation error"
 *     )
 * )
 */
public function store(ServiceVisitRequest $request): JsonResponse
    {
        $serviceVisit = $this->service->create($request);
        return response()->json([
            'status' => true,
            'message' => 'Service visit created successfully',
            'data' => $serviceVisit
        ], 201);
    }
    /**
 * @OA\Get(
 *     path="/mob/technician_mob/service-visit/asset-brand",
 *     tags={"Service Visit"},
 *     summary="Get list of reson types",
 *     description="Returns list of reson types with return information",
 *     @OA\Response(
 *         response=200,
 *         description="Successful response",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="name", type="string", example="Reason One"),
 *                     @OA\Property(property="return_id", type="integer", example=1),
 *                     @OA\Property(property="return_name", type="string", example="Good")
 *                 )
 *             )
 *         )
 *     )
 * )
 */    
public function index(): JsonResponse
    {
        $resonTypes = $this->service->getAll();
        return response()->json([
            'status' => true,
            'data'   => $resonTypes
        ], 200);
    }
    /**
 * @OA\Get(
 *     path="/mob/technician_mob/service-visit/spare-details",
 *     tags={"Service Visit"},
 *     summary="Get list of reson types",
 *     description="Returns list of reson types with return information",
 *     @OA\Response(
 *         response=200,
 *         description="Successful response",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="name", type="string", example="Reason One"),
 *                     @OA\Property(property="return_id", type="integer", example=1),
 *                     @OA\Property(property="return_name", type="string", example="Good")
 *                 )
 *             )
 *         )
 *     )
 * )
 */    
public function spare(): JsonResponse
    {
        $resonTypes = $this->service->spare();
        return response()->json([
            'status' => true,
            'data'   => $resonTypes
        ], 200);
    }    
}