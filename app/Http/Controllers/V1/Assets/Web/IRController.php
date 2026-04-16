<?php

namespace App\Http\Controllers\V1\Assets\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Assets\Web\IRHeaderStoreRequest;
use App\Http\Resources\V1\Assets\Web\IRHeaderResource;
use App\Services\V1\Assets\Web\IRService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\IRFullDetailExport;
use Illuminate\Support\Facades\Storage;

class IRController extends Controller
{
    protected IRService $service;

    public function __construct(IRService $service)
    {
        $this->service = $service;
    }


    /**
     * @OA\Post(
     *     path="/api/ir",
     *     tags={"IR"},
     *     summary="Create IR (header + multiple details)",
     *     description="Single request creates tbl_ir_headers and tbl_ir_details using header_id",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="iro_id", type="integer", example=12),
     *             @OA\Property(property="osa_code", type="string", example="OS123"),
     *             @OA\Property(property="salesman_id", type="integer", example=44),
     *             @OA\Property(property="schedule_date", type="string", example="2025-12-04"),
     *             @OA\Property(
     *                 property="details",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="fridge_id", type="integer", example=1),
     *                     @OA\Property(property="agreement_id", type="integer", example=10),
     *                     @OA\Property(property="crf_id", type="integer", example=22)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="IR created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="IR created successfully")
     *         )
     *     ),
     *     @OA\Response(response=500, description="Error creating record")
     * )
     */
    public function store(IRHeaderStoreRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());

        return response()->json([
            'status'  => 200,
            'message' => 'IR created successfully',
            'data'    => new IRHeaderResource($result)
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/ir",
     *     tags={"IR"},
     *     summary="List all IR records with pagination",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Records fetched successfully"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('limit', 50);
        $page    = $request->get('page', 1);

        // ✅ IMPORTANT (filters pass karna)
        $filters = $request->only([
            'warehouse_id',
            'osa_code',
            'iro_id',
            'status'
        ]);

        $records = $this->service->list($perPage, $filters);

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Records fetched successfully',
            'data'    => IRHeaderResource::collection($records),

            'pagination' => [
                'page'         => (int) $page,
                'limit'        => (int) $perPage,
                'totalPages'   => $records->lastPage(),
                'totalRecords' => $records->total(),
            ]
        ], 200);
    }


    /**
     * @OA\Get(
     *     path="/api/ir/{id}",
     *     tags={"IR"},
     *     summary="View single IR (header + details)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="IR Header ID",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Record fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Record fetched successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Record not found")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $result = $this->service->show($uuid);

            return response()->json([
                'status'  => true,
                'message' => 'Record fetched successfully',
                'data'    => new IRHeaderResource($result)
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Record not found',
                'data'    => null
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }



    public function getAllIRO(): JsonResponse
    {
        $records = $this->service->getAllIRO();

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'IR headers fetched successfully',
            'data'    => $records
        ]);
    }

    public function getAllSalesman(): JsonResponse
    {
        $records = $this->service->getAllSalesman();

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Salesman fetched successfully',
            'data'    => $records
        ]);
    }


    public function header(Request $request): JsonResponse
    {
        try {
            // Remove non-filter params
            $filters = collect($request->all())->except([
                'per_page',
                'page',
                'status'
            ])->toArray();

            // status handling
            $status = $request->get('status', []);
            $status = is_array($status) ? $status : explode(',', $status);

            // pagination
            $perPage = $request->get('per_page', 20);

            // Laravel automatically reads '?page=1'
            $result = $this->service->header($perPage, $filters, $status);

            return response()->json([
                'status'     => 'success',
                'message'    => 'IR headers fetched successfully',
                'data'       => $result->items(),
                'pagination' => [
                    'total'        => $result->total(),
                    'current_page' => $result->currentPage(),
                    'per_page'     => $result->perPage(),
                    'last_page'    => $result->lastPage(),
                ]
            ]);
        } catch (Throwable $e) {

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to fetch IR headers',
                'error'   => $e->getMessage(),
                'trace'   => config('app.debug') ? $e->getTrace() : null
            ], 500);
        }
    }

    public function closeIr(int $id): JsonResponse
    {
        try {
            $this->service->closeIrProcess($id);

            return response()->json([
                'status' => true,
                'message' => 'IR and IRO status updated successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function reverse($id)
    {
        try {
            $userId = Auth::id(); // same login user

            $headerId = $this->service->reverseIRData($id, $userId);

            return response()->json([
                'status' => true,
                'message' => 'IR reversed successfully',
                'header_id' => $headerId
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function reassign(Request $request, $headerId)
    {
        try {

            $request->validate([
                'id' => 'required|integer|exists:salesman,id'
            ]);

            $salesmanId = $request->query('id');
            $userId = auth()->id();

            $this->service->reassignSalesman($headerId, $salesmanId, $userId);

            return response()->json([
                'status' => true,
                'message' => 'Salesman reassigned successfully'
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }



    public function exportIR(Request $request)
    {
        try {

            $uuid = $request->input('filter.uuid');

            if (!$uuid) {
                return response()->json([
                    'status' => false,
                    'message' => 'UUID is required'
                ], 422);
            }

            $format = strtolower($request->get('format', 'xlsx'));

            if (!in_array($format, ['xlsx', 'csv'])) {
                $format = 'xlsx';
            }

            $filename = 'IR_Export_' . now()->format('Ymd_His') . '.' . $format;
            $path = 'exports/ir/' . $filename;

            Storage::disk('public')->makeDirectory('exports/ir');

            Excel::store(
                new IRFullDetailExport($uuid),
                $path,
                'public'
            );

            return response()->json([
                'status' => true,
                'message' => 'Export generated successfully',
                'download_url' => asset('storage/' . $path)
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null
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
                'data'       => IRHeaderResource::collection($records),
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
