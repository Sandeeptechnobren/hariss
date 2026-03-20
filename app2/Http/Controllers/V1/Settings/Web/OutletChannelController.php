<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\OutletChannelRequest;
use App\Services\V1\Settings\Web\OutletChannelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
/**
 * @OA\Schema(
 *     schema="OutletChannel",
 *     type="object",
 *     required={"code", "name", "status"},
 *     @OA\Property(property="id", type="integer", readOnly=true, example=1),
 *     @OA\Property(property="outlet_channel", type="string", example="Modern Trade"),
 *     @OA\Property(property="status", type="integer", enum={0,1}, example=0, description="0=Active, 1=Inactive"),
 * )
 */
class OutletChannelController extends Controller
{
     use ApiResponse;
    protected $service;

    public function __construct(OutletChannelService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/settings/outlet-channels/list",
     *     summary="Get all outlet channels",
     *     tags={"Outlet Channels"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of outlet channels",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/OutletChannel"))
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        return response()->json($this->service->getAll());
    }

    /**
     * @OA\Get(
     *     path="/api/settings/outlet-channels/{id}",
     *     summary="Get outlet channel by ID",
     *     tags={"Outlet Channels"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Outlet channel details", @OA\JsonContent(ref="#/components/schemas/OutletChannel")),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show($id): JsonResponse
    {
        return $this->service->getById($id); 
    }

    /**
     * @OA\Post(
     *     path="/api/settings/outlet-channels",
     *     summary="Create a new outlet channel",
     *     tags={"Outlet Channels"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "status"},
     *             @OA\Property(property="outlet_channel", type="string", example="Modern Trade"),
     *             @OA\Property(property="status", type="integer", enum={0,1}, example=0)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/OutletChannel"))
     * )
     */
public function store(OutletChannelRequest $request): JsonResponse
{
    $response = $this->service->create($request->validated());
    return response()->json($response, $response['code']);
}


    /**
     * @OA\Put(
     *     path="/api/settings/outlet-channels/{id}",
     *     summary="Update outlet channel",
     *     tags={"Outlet Channels"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "status"},
     *             @OA\Property(property="outlet_channel", type="string", example="Modern Trade"),
     *             @OA\Property(property="status", type="integer", enum={0,1}, example=1)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/OutletChannel"))
     * )
     */
public function update(Request $request, $id): JsonResponse
{
    $outletChannel = $this->service->update($id, $request->all());

    if ($outletChannel) {
        return $this->success($outletChannel, 'Outlet Channel updated successfully', 200);
    }

    return $this->fail('Failed to update outlet channel', 500);
}
    /**
     * @OA\Delete(
     *     path="/api/settings/outlet-channels/{id}",
     *     summary="Delete outlet channel",
     *     tags={"Outlet Channels"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Deleted")
     * )
     */
     public function destroy(int $id): JsonResponse
    {
        return $this->service->delete($id);
    }
}
