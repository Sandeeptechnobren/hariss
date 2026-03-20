<?php

namespace App\Http\Controllers\V1\Master\Mob;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\V1\MasterRequests\Mob\LoadRequest;
use App\Http\Requests\V1\MasterRequests\Mob\UpdateLoadRequest;
use App\Http\Resources\V1\Master\Mob\LoadHeaderResource;
use App\Http\Resources\V1\Master\Mob\LoadListResource;
use App\Services\V1\MasterServices\Mob\LoadService;

class LoadController extends Controller
{
    protected $service;

    public function __construct(LoadService $service)
    {
        $this->service = $service;
    }

 /**
 * @OA\Post(
 *     path="/mob/master_mob/Load/create",
 *     tags={"Load"},
 *     summary="Create Load Header and Details (auto-generate osa_code like SLH001, SLD001)",
 *     description="If header_osa_code or details[].osa_code are not provided, system auto-generates them sequentially.",
 *     operationId="createLoadSequentialCode",
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="header_osa_code", type="string", example="SLH001", description="Optional - auto-generated if not passed"),
 *             @OA\Property(property="warehouse_id", type="integer", example=113),
 *             @OA\Property(property="route_id", type="integer", example=60),
 *             @OA\Property(property="salesman_id", type="integer", example=89),
 *             @OA\Property(property="is_confirmed", type="boolean", example=false),
 *             @OA\Property(
 *                 property="details",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="osa_code", type="string", example="SLD001", description="Optional - auto-generated if not passed"),
 *                     @OA\Property(property="item_id", type="integer", example=77),
 *                     @OA\Property(property="uom", type="string", example=27),
 *                     @OA\Property(property="qty", type="number", example=10),
 *                     @OA\Property(property="price", type="number", example=250)
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Load created successfully"
 *     )
 * )
 */

    public function store(LoadRequest $request)
    {
        $header = $this->service->create($request->validated());
        return new LoadHeaderResource($header);
    }
/**
 * @OA\Post(
 *     path="/mob/master_mob/Load/update/{uuid}",
 *     tags={"Load"},
 *     summary="Update Load Header (UUID-based) with signature image and other fields",
 *     description="This endpoint updates a Load Header record using its UUID. It allows uploading a salesman signature image along with optional fields like accept_time, load_id, latitude, longitude, and sync_time.",
 *
 *     @OA\Parameter(
 *         name="uuid",
 *         in="path",
 *         required=true,
 *         description="UUID of the Load Header record",
 *         @OA\Schema(type="string", format="uuid", example="a0deeb0d-9bdc-4832-816f-d8e0bdbad840")
 *     ),
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"salesman_sign"},
 *                 @OA\Property(
 *                     property="salesman_sign",
 *                     type="string",
 *                     format="binary",
 *                     description="Salesman signature image (jpg, jpeg, png)"
 *                 ),
 *                 @OA\Property(
 *                     property="accept_time",
 *                     type="string",
 *                     format="date-time",
 *                     example="2025-10-29 10:35:00",
 *                     description="Time when salesman accepted the load"
 *                 ),
 *                 @OA\Property(
 *                     property="load_id",
 *                     type="integer",
 *                     example=12,
 *                     description="Load ID associated with the header"
 *                 ),
 *                 @OA\Property(
 *                     property="latitude",
 *                     type="number",
 *                     format="float",
 *                     example=28.6139,
 *                     description="Latitude of the salesman location"
 *                 ),
 *                 @OA\Property(
 *                     property="longitude",
 *                     type="number",
 *                     format="float",
 *                     example=77.2090,
 *                     description="Longitude of the salesman location"
 *                 ),
 *                 @OA\Property(
 *                     property="sync_time",
 *                     type="string",
 *                     format="date-time",
 *                     example="2025-10-29 11:00:00",
 *                     description="Time when data was synced to the server"
 *                 ),
 *                 @OA\Property(
 *                     property="is_confirmed",
 *                     type="integer",
 *                     example="1",
 *                     description="Status to confirm the load"
 *                 )
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Load header updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Load header updated successfully."),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="uuid", type="string", example="a0deeb0d-9bdc-4832-816f-d8e0bdbad840"),
 *                 @OA\Property(property="osa_code", type="string", example="SLH013"),
 *                 @OA\Property(property="salesman_sign", type="string", example="signature_images/2025/october/xyz.jpg"),
 *                 @OA\Property(property="accept_time", type="string", example="2025-10-29 10:35:00"),
 *                 @OA\Property(property="latitude", type="number", example=28.6139),
 *                 @OA\Property(property="longtitude", type="number", example=77.2090),
 *                 @OA\Property(property="sync_time", type="string", example="2025-10-29T11:00:00.000000Z")
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(response=400, description="Bad Request"),
 *     @OA\Response(response=404, description="Load header not found"),
 *     @OA\Response(response=500, description="Internal Server Error")
 * )
 */

public function update(UpdateLoadRequest $request, $uuid)
{
    $data = $request->validated();
    if ($request->hasFile('salesman_sign')) {
        $data['salesman_sign'] = $request->file('salesman_sign');
    }
    $header = $this->service->updateByUuid($uuid, $data);
     $responseData = [
        'id'            => $header->id,
        'uuid'          => $header->uuid,
        'osa_code'      => $header->osa_code,
        'salesman_sign' => $header->salesman_sign,
        'accept_time'   => $header->accept_time,
        'latitude'      => $header->latitude,
        'longitude'     => $header->longtitude, 
        'load_id'       => $header->load_id,
        'sync_time'     => $header->sync_time,
        'is_confirmed' => $header->is_confirmed,
    ];
    return response()->json([
        'status'  => true,
        'message' => 'Load header updated successfully.',
        'data'    => $responseData,
    ]);
}

/**
     * @OA\Get(
     *     path="/mob/master_mob/Load/list",
     *     tags={"Load"},
     *     summary="Get list of loads by salesman ID",
     *     @OA\Parameter(
     *         name="salesman_id",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="List of loads")
     * )
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'salesman_id' => 'required|integer|exists:salesman,id',
        ]);

        $loads = $this->service->getLoadList($validated['salesman_id']);

        return response()->json([
            'status' => true,
            'message' => 'Load list fetched successfully',
            'data' => LoadListResource::collection($loads),
        ]);
    }
}