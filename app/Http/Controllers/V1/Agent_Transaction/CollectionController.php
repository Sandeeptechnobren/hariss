<?php

namespace App\Http\Controllers\V1\Agent_Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Agent_Transaction\StoreCollectionRequest;
use App\Http\Resources\V1\Agent_Transaction\CollectionResource;
use App\Services\V1\Agent_Transaction\CollectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Exports\CollectionFullExport;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @OA\Tag(
 *     name="Collections",
 *     description="API Endpoints for managing collection records"
 * )
 */
class CollectionController extends Controller
{
    protected CollectionService $collectionService;

    public function __construct(CollectionService $collectionService)
    {
        $this->collectionService = $collectionService;
    }

    /**
     * @OA\Post(
     *     path="/api/agent_transaction/collections/create",
     *     tags={"Collections"},
     *     security={{"bearerAuth":{}}},
     *     summary="Create a new collection record",
     *     description="Creates a new collection entry with the provided details.",
     *     operationId="storeCollection",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Collection creation payload",
     *         @OA\JsonContent(
     *             example={
     *                 "invoice_id": "INV-1001",
     *                 "customer_id": 1,
     *                 "salesman_id": 2,
     *                 "route_id": 3,
     *                 "warehouse_id": 4,
     *                 "collection_no": 101,
     *                 "ammount": 5000,
     *                 "outstanding": 200
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Collection created successfully",
     *         @OA\JsonContent(
     *             example={
     *                 "message": "Collection created successfully.",
     *                 "data": {
     *                     "id": 1,
     *                     "invoice_id": "INV-1001",
     *                     "customer_id": 1,
     *                     "salesman_id": 2,
     *                     "route_id": 3,
     *                     "warehouse_id": 4,
     *                     "collection_no": 101,
     *                     "ammount": 5000,
     *                     "outstanding": 200
     *                 }
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Validation or input error",
     *         @OA\JsonContent(
     *             example={
     *                 "status": "error",
     *                 "code": 400,
     *                 "message": "Invalid input data"
     *             }
     *         )
     *     )
     * )
     */
    public function store(StoreCollectionRequest $request): JsonResponse
    {
        $collection = $this->collectionService->store($request->validated());

        return response()->json([
            'message' => 'Collection created successfully.',
            'data' => new CollectionResource($collection),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/agent_transaction/collections/list",
     *     tags={"Collections"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get list of collections",
     *     description="Fetches paginated list of collections with optional filters.",
     *     operationId="getCollections",
     *     @OA\Response(
     *         response=200,
     *         description="Collections fetched successfully",
     *         @OA\JsonContent(
     *             example={
     *                 "status": "success",
     *                 "code": 200,
     *                 "message": "Collections fetched successfully",
     *                 "data": {
     *                     {
     *                         "id": 1,
     *                         "invoice_id": "INV-1001",
     *                         "customer_id": 1,
     *                         "salesman_id": 2,
     *                         "route_id": 3,
     *                         "warehouse_id": 4,
     *                         "collection_no": 101,
     *                         "ammount": 5000,
     *                         "outstanding": 200
     *                     }
     *                 },
     *                 "pagination": {
     *                     "page": 1,
     *                     "limit": 50,
     *                     "totalPages": 1,
     *                     "totalRecords": 10
     *                 }
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Failed to fetch collections",
     *         @OA\JsonContent(
     *             example={
     *                 "status": "error",
     *                 "message": "Server error"
     *             }
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 50);

        $filters = $request->only([
            'invoice_id',
            'salesman_id',
            'warehouse_id',
            'route_id',
            'customer_id',
            'ammount',
            'outstanding',
        ]);

        $data = $this->collectionService->list(
            [
                'invoice_id' => $filters['invoice_id'] ?? null,
                'salesman_id' => $filters['salesman_id'] ?? null,
                'warehouse_id' => $filters['warehouse_id'] ?? null,
                'route_id' => $filters['route_id'] ?? null,
                'customer_id' => $filters['customer_id'] ?? null,
                'ammount' => $filters['ammount'] ?? null,
                'outstanding' => $filters['outstanding'] ?? null,
            ],
            $perPage
        );

        return response()->json([
            'status'     => 'success',
            'code'       => 200,
            'message'    => 'Collections fetched successfully',
            'data'       => CollectionResource::collection($data->items()),
            'pagination' => [
                'page'         => $data->currentPage(),
                'limit'        => $data->perPage(),
                'totalPages'   => $data->lastPage(),
                'totalRecords' => $data->total(),
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/agent_transaction/collections/show/{uuid}",
     *     tags={"Collections"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get a single collection record by UUID",
     *     description="Fetches the details of a specific collection entry.",
     *     operationId="getCollectionByUuid",
     *
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="UUID of the collection record",
     *         @OA\Schema(type="string", example="c41b4f6e-98b2-4e8e-8a41-3f8c21ef8c44")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Collection fetched successfully",
     *         @OA\JsonContent(
     *             example={
     *                 "status": "success",
     *                 "code": 200,
     *                 "message": "Collection details fetched successfully",
     *                 "data": {
     *                     "id": 1,
     *                     "invoice_id": "INV-1001",
     *                     "customer_id": 1,
     *                     "salesman_id": 2,
     *                     "route_id": 3,
     *                     "warehouse_id": 4,
     *                     "collection_no": 101,
     *                     "ammount": 5000,
     *                     "outstanding": 200
     *                 }
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Collection not found",
     *         @OA\JsonContent(
     *             example={
     *                 "status": "error",
     *                 "code": 404,
     *                 "message": "Collection not found"
     *             }
     *         )
     *     )
     * )
     */
    public function show($uuid)
    {
        $payment = $this->collectionService->getByUuid($uuid);

        if (!$payment) {
            return response()->json([
                'status'  => 'error',
                'code'    => 404,
                'message' => 'Collection not found',
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Collection details fetched successfully',
            'data'    => new CollectionResource($payment),
        ]);
    }

public function exportCollection(Request $request)
{
    $uuid = $request->input('uuid');
    $format = strtolower($request->input('format', 'xlsx'));
    $extension = $format === 'csv' ? 'csv' : 'xlsx';
    $filename = 'collections_export_' . now()->format('Ymd_His') . '.' . $extension;
    $path = 'collectionexports/' . $filename;

    $export = new CollectionFullExport($uuid); 

    if ($format === 'csv') {
        Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
    } else {
        Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
    }

    $appUrl = rtrim(config('app.url'), '/');
    $fullUrl = $appUrl . '/storage/app/public/' . $path;

    return response()->json([
        'status' => 'success',
        'uuid' => $uuid,
        'download_url' => $fullUrl,
    ]);
}
}
