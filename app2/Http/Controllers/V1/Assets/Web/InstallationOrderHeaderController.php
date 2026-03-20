<?php

namespace App\Http\Controllers\V1\Assets\Web;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Assets\Web\InstallationOrderHeaderRequest;
use App\Http\Resources\V1\Assets\Web\InstallationOrderHeaderResource;
use App\Services\V1\Assets\Web\InstallationOrderHeaderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

/**
 * @OA\Schema(
 *     schema="InstallationOrderHeader",
 *     type="object",
 *     title="InstallationOrderHeader",
 *     description="Schema for Installation Order Header",
 *     @OA\Property(property="name", type="string", example="IRO001"),
 *     @OA\Property(property="status", type="integer", enum={0,1}, description="0=Inactive, 1=Active", example=1)
 * )
 */
class InstallationOrderHeaderController extends Controller
{
    use ApiResponse;

    protected InstallationOrderHeaderService $service;

    public function __construct(InstallationOrderHeaderService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/web/assets_web/io_headers/list",
     *     tags={"InstallationOrderHeader"},
     *     summary="Get all Installation Order Headers with pagination and filters",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="osa_code", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="List of Installation Order Headers",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Records fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/InstallationOrderHeader")
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
        $perPage = $request->get('limit', 10);
        $filters = $request->only(['status', 'osa_code', 'iro_id']);
        $records = $this->service->getAll($perPage, $filters);

        return ResponseHelper::paginatedResponse(
            'Records fetched successfully',
            InstallationOrderHeaderResource::class,
            $records
        );
    }

    /**
     * @OA\Get(
     *     path="/web/assets_web/io_headers/generate-osa-code",
     *     tags={"InstallationOrderHeader"},
     *     summary="Generate a unique OSA code",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Unique OSA code generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Unique OSA code generated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="osa_code", type="string", example="IRO001")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to generate OSA code",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="fail"),
     *             @OA\Property(property="code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Unable to generate unique OSA code")
     *         )
     *     )
     * )
     */
    public function generateOsaCode(): JsonResponse
    {
        try {
            $osa_code = $this->service->generateOsaCode();
            return $this->success(['osa_code' => $osa_code], 'Unique OSA code generated successfully', 200);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/web/assets_web/io_headers/{uuid}",
     *     tags={"InstallationOrderHeader"},
     *     summary="Get a single Installation Order Header by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Record fetched successfully",
     *         @OA\JsonContent(ref="#/components/schemas/InstallationOrderHeader")
     *     ),
     *     @OA\Response(response=404, description="Record not found")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        $record = $this->service->findByUuid($uuid);
        if (!$record) {
            return $this->fail('Record not found', 404);
        }
        return $this->success(new InstallationOrderHeaderResource($record), 'Record fetched successfully', 200);
    }

    /**
     * @OA\Post(
     *     path="/web/assets_web/io_headers/add",
     *     tags={"InstallationOrderHeader"},
     *     summary="Create a new Installation Order Header",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/InstallationOrderHeader")),
     *     @OA\Response(response=200, description="Record created successfully")
     * )
     */
    public function store(InstallationOrderHeaderRequest $request): JsonResponse
    {
        $record = $this->service->store($request->validated());
        return $this->success(new InstallationOrderHeaderResource($record), 'Record created successfully', 200);
    }

    /**
     * @OA\Put(
     *     path="/web/assets_web/io_headers/{uuid}",
     *     tags={"InstallationOrderHeader"},
     *     summary="Update an Installation Order Header by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/InstallationOrderHeader")),
     *     @OA\Response(response=200, description="Record updated successfully"),
     *     @OA\Response(response=404, description="Record not found")
     * )
     */
    public function update(InstallationOrderHeaderRequest $request, string $uuid): JsonResponse
    {
        try {
            $record = $this->service->update($uuid, $request->validated());
            return $this->success(new InstallationOrderHeaderResource($record), 'Record updated successfully', 200);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/web/assets_web/io_headers/{uuid}",
     *     tags={"InstallationOrderHeader"},
     *     summary="Delete an Installation Order Header by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Record deleted successfully"),
     *     @OA\Response(response=404, description="Record not found")
     * )
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->service->delete($uuid);
            return $this->success(null, 'Record deleted successfully', 200);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/web/assets_web/io_headers/global-search",
     *     tags={"InstallationOrderHeader"},
     *     summary="Global search Record with pagination",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         description="Search keyword for areas"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Record fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Record fetched successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items()),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="page", type="integer", example=1),
     *                 @OA\Property(property="limit", type="integer", example=10),
     *                 @OA\Property(property="totalPages", type="integer", example=5),
     *                 @OA\Property(property="totalRecords", type="integer", example=50),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to search areas"
     *     )
     * )
     */
    public function global_search(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 10);
            $searchTerm = $request->get('search');
            $record = $this->service->globalSearch($perPage, $searchTerm);

            return ResponseHelper::paginatedResponse(
                'Records fetched successfully',
                InstallationOrderHeaderResource::class,
                $record
            );
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "code" => 500,
                "message" => $e->getMessage(),
                "data" => null
            ], 500);
        }
    }
}
