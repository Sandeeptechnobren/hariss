<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\ItemRequest;
use App\Http\Resources\V1\Master\Web\ItemResource;
use App\Models\Item;
use App\Services\V1\MasterServices\Web\ItemService;
use Illuminate\Http\JsonResponse;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="Item",
 *     type="object",
 *     required={"category_id","sub_category_id","shelf_life","community_code","excise_code"},
 *     @OA\Property(property="name", type="string", maxLength=255, example="Sample Item"),
 *     @OA\Property(property="description", type="string", maxLength=255, example="This is a sample item"),
 *     @OA\Property(property="uom", type="integer", example=1),
 *     @OA\Property(property="upc", type="integer", example=123456),
 *     @OA\Property(property="category_id", type="integer", example=96),
 *     @OA\Property(property="sub_category_id", type="integer", example=28),
 *     @OA\Property(property="vat", type="number", format="double", example=18),
 *     @OA\Property(property="excies", type="number", format="double", example=5),
 *     @OA\Property(property="shelf_life", type="string", maxLength=50, example="12 Months"),
 *     @OA\Property(property="community_code", type="string", maxLength=100, example="COMM001"),
 *     @OA\Property(property="excise_code", type="string", maxLength=500, example="EXC001"),
 *     @OA\Property(property="status", type="integer", example=1)
 * )
 */
class ItemController extends Controller
{
    use ApiResponse;
    protected ItemService $service;

    public function __construct(ItemService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/master/items/list",
     *     tags={"Item"},
     *     summary="Get paginated list of items with filters",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="item_code", in="query", description="Filter by item code", @OA\Schema(type="string")),
     *     @OA\Parameter(name="item_name", in="query", description="Filter by item name", @OA\Schema(type="string")),
     *     @OA\Parameter(name="item_category_id", in="query", description="Filter by category ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="List of items",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Item")),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="page", type="integer"),
     *                 @OA\Property(property="limit", type="integer"),
     *                 @OA\Property(property="totalPages", type="integer"),
     *                 @OA\Property(property="totalRecords", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('limit', 50);
        $dropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);
        $filters = $request->except(['limit', 'dropdown']);

        $items = $this->service->getAll($perPage, $filters, $dropdown);

        if ($dropdown) {
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'data' => $items,
            ]);
        } else {
            $pagination = [
                'page' => $items->currentPage(),
                'limit' => $items->perPage(),
                'totalPages' => $items->lastPage(),
                'totalRecords' => $items->total(),
            ];
            return $this->success(
                ItemResource::collection($items),
                'Items fetched successfully',
                200,
                $pagination
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/master/items/{id}",
     *     tags={"Item"},
     *     summary="Get single item",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Item details", @OA\JsonContent(ref="#/components/schemas/Item")),
     *     @OA\Response(response=404, description="Item not found")
     * )
     */
    public function show($id): JsonResponse
    {
        $item = $this->service->getById($id);
        return response()->json(['status' => 'success', 'code' => '200',  'data' => new ItemResource($item)]);
    }

    /**
     * @OA\Post(
     *     path="/api/master/items/add",
     *     tags={"Item"},
     *     summary="Create a new item",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Item")),
     *     @OA\Response(response=201, description="Item created successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(ItemRequest $request): JsonResponse
    {
        $item = $this->service->create($request->validated());
        return response()->json(['status' => 'success', 'code' => '200','data'=>$item], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/master/items/update/{id}",
     *     tags={"Item"},
     *     summary="Update an existing item",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Item")),
     *     @OA\Response(response=200, description="Item updated successfully"),
     *     @OA\Response(response=404, description="Item not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(ItemRequest $request, $id): JsonResponse
    {
        $item = $this->service->getById($id);
        $updated = $this->service->update($item, $request->validated());
        return response()->json(['status' => 'success', 'code' => '200',  'data' => new ItemResource($updated)]);
    }

    /**
     * @OA\Delete(
     *     path="/api/master/items/{id}",
     *     tags={"Item"},
     *     summary="Soft delete an item",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Item deleted successfully"),
     *     @OA\Response(response=404, description="Item not found")
     * )
     */
    public function destroy($id): JsonResponse
    {
        $item = $this->service->getById($id);
        $this->service->delete($item);
        return response()->json(['status' => 'success', 'code' => '200',  'message' => 'Item deleted successfully']);
    }

    /**
     * @OA\Get(
     *     path="/api/master/items/global-search",
     *     tags={"Item"},
     *     summary="Global search across items",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Search term for items",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of results per page (default 10)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Global search results",
     *         @OA\JsonContent(type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Item")),
     *             @OA\Property(property="pagination", type="object")
     *         )
     *     ),
     *     @OA\Response(response=500, description="Failed to perform search")
     * )
     */
    public function globalSearch(Request $request)
    {
        $search = $request->query('search', null);
        $perPage = $request->query('per_page', 10);
        $items = $this->service->globalSearch($perPage, $search);
        return response()->json([
            'status' => 200,
            'message' => 'Items retrieved successfully',
            'data' => $items->items(),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'per_page'     => $items->perPage(),
                'total'        => $items->total(),
                'last_page'    => $items->lastPage(),
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/master/items/bulk_upload",
     *     tags={"Item"},
     *     summary="Upload bulk items via CSV file (no external library needed)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="CSV file containing item data"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Items uploaded successfully"),
     *     @OA\Response(response=400, description="Invalid file format or data"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function bulkUpload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx',
        ]);

        try {
            $result = $this->service->bulkUpload($request->file('file'));
            $data = json_decode($result->getContent(), true);
            $message = $data['message'] ?? null;
            return $this->success(null,$message);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/master/items/update-status",
     *     summary="Update status for multiple items",
     *     description="Updates the status of multiple items by their IDs.",
     *     operationId="updateMultipleItemStatus",
     *     tags={"Item"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"item_ids", "status"},
     *             @OA\Property(
     *                 property="item_ids",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={10, 20, 30}
     *             ),
     *             @OA\Property(
     *                 property="status",
     *                 type="integer",
     *                 example=1
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Item statuses updated.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Item statuses updated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={"item_ids.0": {"The selected item_ids.0 is invalid."}}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Update failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Update failed.")
     *         )
     *     )
     * )
     */
    public function updateMultipleItemStatus(Request $request)
    {
        $request->validate([
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'integer|exists:items,id',
            'status' => 'required|integer',
        ]);

        $itemIds = $request->input('item_ids');
        $status = $request->input('status');

        $result = $this->service->updateItemsStatus($itemIds, $status);

        if ($result) {
            return response()->json(['success' => true, 'message' => 'Item statuses updated.'], 200);
        } else {
            return response()->json(['success' => false, 'message' => 'Update failed.'], 500);
        }
    }
}
