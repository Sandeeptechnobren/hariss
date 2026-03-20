<?php

namespace App\Http\Controllers\V1\Assets\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Assets\Web\ChillerRequest;
use App\Http\Resources\V1\Assets\Web\ChillerResource;
use App\Services\V1\Assets\Web\ChillerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Traits\RoleAuthorization;

/**
 * @OA\Schema(
 *     schema="Chiller",
 *     type="object",
 *     title="Chiller",
 *     description="Schema for Chiller object",
 *     @OA\Property(property="serial_number", type="string", example="SN123456"),
 *     @OA\Property(property="asset_number", type="string", example="ASSET789"),
 *     @OA\Property(property="model_number", type="string", example="MDL-2025"),
 *     @OA\Property(property="description", type="string", example="Chiller for storing beverages"),
 *     @OA\Property(property="acquisition", type="string", format="date", example="2025-01-10"),
 *     @OA\Property(
 *         property="vender_details",
 *         type="array",
 *         @OA\Items(type="integer"),
 *         example={3,4,5}
 *     ),
 *     @OA\Property(property="manufacturer", type="string", example="CoolTech Manufacturing"),
 *     @OA\Property(property="country_id", type="integer", example=1),
 *     @OA\Property(property="type_name", type="string", example="Double Door"),
 *     @OA\Property(property="sap_code", type="string", example="SAP-CH-001"),
 *     @OA\Property(property="status", type="integer", enum={0,1}, example=1),
 *     @OA\Property(property="is_assign", type="integer", enum={0,1,2}, example=0),
 *     @OA\Property(property="customer_id", type="integer", example=1),
 *     @OA\Property(property="agreement_id", type="integer", example=55),
 *     @OA\Property(property="document_type", type="string", enum={"ACF","CRF"}, example="ACF"),
 *     @OA\Property(property="document_id", type="integer", example=2001),
 * )
 */
class ChillerController extends Controller
{
    use ApiResponse, RoleAuthorization;

    protected ChillerService $service;

    public function __construct(ChillerService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/assets/chiller/list_chillers",
     *     tags={"Chiller"},
     *     summary="Get all chillers with pagination and optional filters",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="fridge_code", in="query", required=false, @OA\Schema(type="string", example="CH001")),
     *     @OA\Response(
     *         response=200,
     *         description="List of chillers",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Chillers fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Chiller")
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="page", type="integer", example=1),
     *                 @OA\Property(property="limit", type="integer", example=10),
     *                 @OA\Property(property="totalPages", type="integer", example=5),
     *                 @OA\Property(property="totalRecords", type="integer", example=50)
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        if ($resp = $this->authorizeRoleAccess(__FUNCTION__)) return $resp;

        $perPage = $request->get('limit', 10);
        $filters = $request->only(['fridge_code', 'serial_number', 'asset_number', 'model_number']);
        $chillers = $this->service->all($perPage, $filters);

        return response()->json([
            'status'     => 'success',
            'code'       => 200,
            'message'    => 'Chillers fetched successfully',
            'data'       => ChillerResource::collection($chillers->items()),
            'pagination' => [
                'page'         => $chillers->currentPage(),
                'limit'        => $chillers->perPage(),
                'totalPages'   => $chillers->lastPage(),
                'totalRecords' => $chillers->total(),
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/assets/chiller/{uuid}",
     *     tags={"Chiller"},
     *     summary="Get a single chiller by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Chiller details", @OA\JsonContent(ref="#/components/schemas/Chiller")),
     *     @OA\Response(response=404, description="Chiller not found")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        if ($resp = $this->authorizeRoleAccess(__FUNCTION__)) return $resp;

        $chiller = $this->service->findByUuid($uuid);
        if (!$chiller) {
            return $this->fail('Chiller not found', 404);
        }
        return $this->success(new ChillerResource($chiller), 'Chiller fetched successfully');
    }

    /**
     * @OA\Get(
     *     path="/api/assets/chiller/generate-code",
     *     tags={"Chiller"},
     *     security={{"bearerAuth":{}}},
     *     summary="Generate unique chiller code",
     *     @OA\Response(
     *         response=200,
     *         description="Unique chiller code generated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Unique chiller code generated successfully"),
     *             @OA\Property(property="data", type="object", @OA\Property(property="fridge_code", type="string", example="CH001"))
     *         )
     *     )
     * )
     */
    public function generateCode(): JsonResponse
    {
        if ($resp = $this->authorizeRoleAccess(__FUNCTION__)) return $resp;

        try {
            $fridge_code = $this->service->generateCode();
            return $this->success(['fridge_code' => $fridge_code], 'Unique Chiller code generated successfully');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/assets/chiller/add_chiller",
     *     tags={"Chiller"},
     *     summary="Create a new chiller",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Chiller")),
     *     @OA\Response(response=200, description="Chiller created successfully")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        if ($resp = $this->authorizeRoleAccess(__FUNCTION__)) return $resp;

        $chiller = $this->service->create($request->all());
        return $this->success(new ChillerResource($chiller), 'Chiller created successfully', 200);
    }

    /**
     * @OA\Put(
     *     path="/api/assets/chiller/{uuid}",
     *     tags={"Chiller"},
     *     summary="Update a chiller by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Chiller")),
     *     @OA\Response(response=200, description="Chiller updated successfully"),
     *     @OA\Response(response=404, description="Chiller not found")
     * )
     */
    public function update(Request $request, string $uuid): JsonResponse
    {
        if ($resp = $this->authorizeRoleAccess(__FUNCTION__)) return $resp;

        try {

            $updated = $this->service->updateByUuid($uuid, $request->all());
            return $this->success(new ChillerResource($updated), 'Chiller updated successfully');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/assets/chiller/{uuid}",
     *     tags={"Chiller"},
     *     summary="Delete a chiller by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Chiller deleted successfully"),
     *     @OA\Response(response=404, description="Chiller not found")
     * )
     */
    public function destroy(string $uuid): JsonResponse
    {
        if ($resp = $this->authorizeRoleAccess(__FUNCTION__)) return $resp;

        try {
            $this->service->deleteByUuid($uuid);
            return $this->success(null, 'Chiller deleted successfully');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 404);
        }
    }
}
