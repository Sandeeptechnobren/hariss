<?php

namespace App\Http\Controllers\V1\Assets\Web;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Assets\Web\IROHeaderRequest;
use App\Http\Resources\V1\Assets\Web\IROHeaderResource;
use App\Services\V1\Assets\Web\IROHeaderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
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
class IROHeaderController extends Controller
{
    use ApiResponse;

    protected IROHeaderService $service;

    public function __construct(IROHeaderService $service)
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
            IROHeaderResource::class,
            $records
        );
    }
    // public function getAll(int $perPage = 10, array $filters = [])
    //     {
    //         $query = IROHeader::query()->latest();
    //         foreach ($filters as $key => $value) {
    //             if (empty($value)) continue;
    //             if (in_array($key, ['osa_code'])) {
    //                 $query->whereRaw("LOWER({$key}) LIKE ?", ['%' . strtolower($value) . '%']);
    //             } else {
    //                 $query->where($key, $value);
    //             }
    //         }
    //         $result = $query->paginate($perPage);
    //         $result->getCollection()->transform(function ($item) {
    //             return \App\Helpers\ApprovalHelper::attach($item, 'IRO_Header');
    //         });
    //         return $result;
    //     }

    public function getDetailCount(Request $request): JsonResponse
    {
        $filters = array_filter([
            'header_id' => $request->query('header_id'),
            'iro_id'    => $request->query('iro_id'),
            'status'    => $request->query('status'),
            'warehouse_id' => $request->query('warehouse_id'),
        ], fn($v) => !is_null($v) && $v !== '');
        $perPage = max((int) $request->query('per_page', 10), 1);
        $page    = max((int) $request->query('page', 1), 1);
        $result  = $this->service->getDetailCountWithHeader($filters);
        $headers = collect($result['headers']);
        $total   = $headers->count();
        $items = $headers
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();
        $lastPage = (int) ceil($total / $perPage);
        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Detail count fetched successfully',
            'count'   => $result['count'],
            'data'    => IROHeaderResource::collection($items),
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => $lastPage,
            ],
        ]);
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
    public function show($id): JsonResponse
    {
        try {
            $records = $this->service->findByCrfId($id);

            return $this->success(
                IROHeaderResource::collection($records),
                'Records fetched successfully',
                200
            );
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 404);
        }
    }


    // /**
    //  * @OA\Post(
    //  *     path="/web/assets_web/io_headers/add",
    //  *     tags={"InstallationOrderHeader"},
    //  *     summary="Create a new Installation Order (Header + Details)",
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\RequestBody(
    //  *         required=true,
    //  *         @OA\JsonContent(ref="#/components/schemas/IROHeaderRequest")
    //  *     ),
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="Record created successfully",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="message", type="string"),
    //  *             @OA\Property(property="data", ref="#/components/schemas/IROHeaderResource")
    //  *         )
    //  *     )
    //  * )
    //  */
    public function store(IROHeaderRequest $request): JsonResponse
    {
        // dd($request);
        try {

            // Create header + detail using service
            $record = $this->service->store($request->validated());

            return response()->json([
                'status'  => 'success',
                'message' => 'Installation Order created successfully',
                'data'    => new IROHeaderResource($record)
            ], 200);
        } catch (\Throwable $e) {

            Log::error("IO Header creation failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create Installation Order. Please check your data.',
            ], 500);
        }
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
    public function update(IROHeaderRequest $request, string $uuid): JsonResponse
    {
        try {
            $record = $this->service->update($uuid, $request->validated());
            return $this->success(new IROHeaderResource($record), 'Record updated successfully', 200);
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
                IROHeaderResource::class,
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



    public function getChillers(int $header_id, int $warehouse_id): JsonResponse
    {
        $data = $this->service->getChillers($header_id, $warehouse_id);

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Available chillers fetched successfully',
            'data'    => $data
        ]);
    }

    public function reverse(int $id): JsonResponse
    {
        try {
            $this->service->reverseIroData($id);

            return response()->json([
                'status' => true,
                'message' => 'IRO reversed successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function globalFilter(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('limit', 50);
            $filters = $request->except(['limit']);

            $records = $this->service->globalFilter($perPage, $filters);

            $pagination = [
                'current_page' => $records->currentPage(),
                'last_page'    => $records->lastPage(),
                'per_page'     => $records->perPage(),
                'total'        => $records->total(),
            ];

            return response()->json([
                'status'     => 'success',
                'code'       => 200,
                'message'    => 'Records fetched successfully',
                'data'       => IROHeaderResource::collection($records),
                'pagination' => $pagination,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve invoices',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
