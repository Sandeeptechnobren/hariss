<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\LocationRequest;
use App\Http\Resources\V1\Settings\Web\LocationResource;
use App\Services\V1\Settings\Web\LocationService;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="LocationRequest",
 *     type="object",
 *     required={"name","code"},
 *     @OA\Property(property="name", type="string", example="New Location"),
 * )
 *
 * @OA\Schema(
 *     schema="LocationResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="uuid", type="string"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="code", type="string"),
 *     @OA\Property(property="status", type="integer"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class LocationController extends Controller
{
    protected $service;

    public function __construct(LocationService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/settings/locations/list",
     *     summary="Get list of locations",
     *     description="Retrieve all locations with optional filters and pagination",
     *     tags={"Locations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of records per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=50)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of locations",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/LocationResource")),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="page", type="integer"),
     *                 @OA\Property(property="limit", type="integer"),
     *                 @OA\Property(property="totalPages", type="integer"),
     *                 @OA\Property(property="totalRecords", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $isDropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);
        $perPage = (int) $request->get('limit', 50);
        $filters = $request->except(['limit', 'page', 'dropdown']);

        $locations = $this->service->all($perPage, $filters, $isDropdown);

        if ($isDropdown) {
            return response()->json([
                'data' => LocationResource::collection($locations)
            ]);
        }

        return response()->json([
            'data' => LocationResource::collection($locations),
            'pagination' => [
                'page' => $locations->currentPage(),
                'limit' => $locations->perPage(),
                'totalPages' => $locations->lastPage(),
                'totalRecords' => $locations->total(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/settings/locations/add",
     *     summary="Create a new location",
     *     description="Store a new location",
     *     tags={"Locations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/LocationRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Location created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/LocationResource")
     *     )
     * )
     */
    public function store(LocationRequest $request)
    {
        $location = $this->service->create($request->validated());

        return response()->json([
            'data' => new LocationResource($location)
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/settings/locations/{uuid}",
     *     summary="Get location by UUID",
     *     description="Retrieve a single location by its UUID",
     *     tags={"Locations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         description="UUID of the location",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Location data",
     *         @OA\JsonContent(ref="#/components/schemas/LocationResource")
     *     )
     * )
     */
    public function show($uuid)
    {
        $location = $this->service->getByUuid($uuid);

        return response()->json([
            'data' => new LocationResource($location)
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/settings/locations/update/{uuid}",
     *     summary="Update location by UUID",
     *     description="Update a location's details",
     *     tags={"Locations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         description="UUID of the location",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/LocationRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Location updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/LocationResource")
     *     )
     * )
     */
    public function update(LocationRequest $request, $uuid)
    {
        $location = $this->service->getByUuid($uuid);
        $updated = $this->service->update($location, $request->validated());

        return response()->json([
            'data' => new LocationResource($updated)
        ]);
    }

    // /**
    //  * @OA\Delete(
    //  *     path="/api/settings/locations/{uuid}",
    //  *     summary="Delete location by UUID",
    //  *     description="Soft delete a location",
    //  *     tags={"Locations"},
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Parameter(
    //  *         name="uuid",
    //  *         in="path",
    //  *         description="UUID of the location",
    //  *         required=true,
    //  *         @OA\Schema(type="string")
    //  *     ),
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="Location deleted successfully",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="message", type="string")
    //  *         )
    //  *     )
    //  * )
    //  */
    // public function destroy($uuid)
    // {
    //     $location = $this->service->getByUuid($uuid);
    //     $this->service->delete($location);

    //     return response()->json([
    //         'message' => 'Location deleted successfully.'
    //     ]);
    // }
   
}
